<?php

declare(strict_types=1);

ob_start();

use Flexi\Infrastructure\Factories\ContainerFactory;
use Flexi\Infrastructure\Ui\Web\Application;

require_once __DIR__.'/../vendor/autoload.php';

if (PHP_SAPI === 'cli') {
    throw new RuntimeException('Entry point only for web request');
}

$container = ContainerFactory::createDefault();
$app = new Application($container);
$app->run();
