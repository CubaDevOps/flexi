<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\TestData\TestDoubles\Configuration;

use Psr\Container\NotFoundExceptionInterface;

final class ConfigurationNotFound extends \RuntimeException implements NotFoundExceptionInterface
{
}
