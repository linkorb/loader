<?php

use Loader\Loader;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require_once __DIR__ . '/../vendor/autoload.php';

$logger = new Logger('loader');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));



$flags =
    Loader::FLAG_JSON_REFERENCE|
    Loader::FLAG_JSON_REFERENCE_REMOTE|
    Loader::FLAG_VARIABLE_EXPANSION
;

$loader = Loader::create(
    [
        'flags' => $flags,
        'logger' => $logger
    ]
);

$data = $loader->load('file://' . __DIR__ . '/data/example.yaml');
print_r($data);
