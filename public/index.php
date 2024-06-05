<?php

declare(strict_types=1);

use CubaDevOps\Flexi\Infrastructure\Ui\Web\Application;

require_once __DIR__.'/../vendor/autoload.php';

if (PHP_SAPI === 'cli') {
    throw new RuntimeException('Entry point only for web request');
}
Application::run();
