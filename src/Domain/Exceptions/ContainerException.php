<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Exceptions;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class ContainerException extends Exception implements NotFoundExceptionInterface
{

}
