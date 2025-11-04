<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\TestData\Handlers;

use CubaDevOps\Flexi\Contracts\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Contracts\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\HandlerInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\MessageInterface;

/**
 * Simple test handler for unit testing purposes.
 * This is a real implementation, not a test double.
 */
class TestQueryHandler implements HandlerInterface
{
    public function handle(DTOInterface $query): MessageInterface
    {
        return new PlainTextMessage('test-response');
    }
}