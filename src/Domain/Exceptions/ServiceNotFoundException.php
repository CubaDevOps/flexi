<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

class ServiceNotFoundException extends \Exception implements NotFoundExceptionInterface
{
}
