<?php

namespace Loader;

use Symfony\Component\Yaml\Yaml;
use RuntimeException;

class Document
{
    protected $url;
    protected $data;

    public function __construct(string $url, string $content, string $format)
    {
        $this->url = $url;
        $this->content = $content;

        switch ($format) {
            case 'json':
                $data = json_decode($content, true);
                break;
            case 'json5':
                $data = json5_decode($content, true);
                break;
            case 'yml':
            case 'yaml':
                $data = Yaml::parse($content);
                break;
            case null:
                throw new RuntimeException("Required format not specified");
            default:
                throw new RuntimeException("Unsupported format: " . $format);
        }

        if (!$data) {
            $data = [];
        }

        $this->data = $data;
    }

    public function getBaseUrl()
    {
        return dirname($this->url);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
