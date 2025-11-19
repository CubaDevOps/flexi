<?php

declare(strict_types=1);

namespace Flexi\Test\TestData\Handlers;

use Flexi\Contracts\Classes\PlainTextMessage;
use Flexi\Contracts\Interfaces\DTOInterface;
use Flexi\Contracts\Interfaces\HandlerInterface;
use Flexi\Contracts\Interfaces\MessageInterface;

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