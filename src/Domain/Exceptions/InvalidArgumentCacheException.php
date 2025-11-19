<?php

declare(strict_types=1);

namespace Flexi\Domain\Exceptions;

use Psr\SimpleCache\InvalidArgumentException;

class InvalidArgumentCacheException extends \Exception implements InvalidArgumentException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
