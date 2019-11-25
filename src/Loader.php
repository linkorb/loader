<?php

namespace Loader;

use GuzzleHttp\Client as GuzzleClient;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Loader
{
    public const FLAG_JSON_REFERENCE = 1;
    public const FLAG_JSON_REFERENCE_REMOTE = 2;
    public const FLAG_VARIABLE_EXPANSION = 3;

    protected $flags;
    protected $interpolator;
    protected $guzzle;
    protected $logger;

    public function __construct(int $flags, Interpolator $interpolator, GuzzleClient $guzzle, LoggerInterface $logger)
    {
        $this->flags = $flags;
        $this->interpolator = $interpolator;
        $this->guzzle = $guzzle;
        $this->logger = $logger;
    }

    public function getInterpolator()
    {
        return $this->interpolator;
    }

    public static function create(array $options)
    {
        $flags = $options['flags'] ?? 0;
        $logger = $options['logger'] ?? new \Psr\Log\NullLogger();
        $interpolator = $options['interpolator'] ?? Interpolator::createDefault($flags);
        $guzzle = $options['interpolator'] ?? new GuzzleClient();

        $obj = new self(
            $flags,
            $interpolator,
            $guzzle,
            $logger
        );
        return $obj;
    }

    public function extensionToFormat(string $extension): string
    {
        $format = strtolower($extension);
        return $format;
    }

    protected function contentTypeHeaderToFormat(string $contentType): string
    {
        $part = explode(';', $contentType);
        switch (strtolower($part[0])) {
            case 'application/json':
                return 'json';
            case 'text/json':
                return 'json';
            case 'text/yaml':
                return 'yaml';
            default:
                throw new RuntimeException("Unsupported content type response header: " . $contentType);
        }
    }

    public function getDocument(string $url, string $format = null)
    {
        $extension = pathinfo($url, PATHINFO_EXTENSION);
        if (!$format && $extension) {
            $format = $this->extensionToFormat($extension);
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        switch ($scheme) {
            case 'file':
                $content = file_get_contents($url);
                break;
            case 'http':
            case 'https':
                $options = [];
                $response = $this->guzzle->request('GET', $url, $options);
                if ($response->getStatusCode()!=200) {
                    throw new RuntimeException("Failed to retrieve: " . $url);
                }
                $content = (string)$response->getBody();

                if (!$format) {
                    $format = $this->contentTypeHeaderToFormat($response->getHeaderLine('Content-Type'));
                }
                break;
            default:
                throw new RuntimeException("Unsupported scheme: " . $url . ' (' . $scheme . ')');
        }

        if (!$format) {
            throw new RuntimeException("Can't determine format of " . $url);
        }

        $document = new Document($url, $content, $format);
        return $document;
    }

    public function load(string $url, $format = null, array $root = [])
    {
        $this->logger->info("LOADING: $url");

        $document = $this->getDocument($url, $format);
        $data = $document->getData();

        $this->postProcess($data, $document->getBaseUrl(), $root);

        return $data;
    }

    public function postProcess(array &$data, string $baseUrl, array $root): void
    {
        $merged = array_merge_recursive($data, $root);

        // Apply references twice, to support references to references
        $this->applyReferences($data, $baseUrl, $merged);
        $this->applyReferences($data, $baseUrl, $merged);

        $merged = array_merge_recursive($data, $root);
        $this->applyVariables($data, $merged);

        $this->applyStrip($data);
    }

    public function applyStrip(array &$data) {

        foreach ($data as $key => &$value) {
            if ($key==='$strip') {
                if (is_string($value)) {
                    $value = [$value];
                }
                foreach ($value as $name) {
                    unset($data[$name]);
                }
                unset($data['$strip']);
            }
            if (is_array($value)) {
                $this->applyStrip($value);
            }
        }
    }

    public function applyReferences(array &$data, string $baseUrl, array $root)
    {
        foreach ($data as $key => &$value) {
            if (is_object($value)) {
                // force objects to become arrays
                $value = json_decode(json_encode($value), true);
            }
            if (is_array($value)) {
                $this->applyReferences($value, $baseUrl, $root);
            } else {
                if ($key==='$ref') {
                    $value = $this->interpolator->interpolate($value, $root);
                    $part = parse_url($value);
                    if (isset($part['fragment'])) {
                        throw new RuntimeException("TODO: support fragments");
                    }
                    // print_r($part);

                    if ((!isset($part['scheme'])) || ($part['path'][0]!='/')) {
                        $value = $baseUrl . '/' . $value;
                    }
                    $data = $this->load($value, null, $root);
                }
            }
        }
    }


    protected function applyVariables(array &$data, $root)
    {
        foreach ($data as $key => &$value) {

            if (is_array($value)) {
                $this->applyVariables($value, $root);
            } else {
                if (is_string($value)) {
                    $value = $this->interpolator->interpolate($value, $root);
                }
                $data[$key] = $value;
            }
        }
    }

}
