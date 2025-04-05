<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Exceptions;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class ServiceNotFoundException extends Exception implements NotFoundExceptionInterface
{

}
