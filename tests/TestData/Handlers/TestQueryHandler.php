<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\TestData\Handlers;

use CubaDevOps\Flexi\Contracts\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Contracts\DTOContract;
use CubaDevOps\Flexi\Contracts\HandlerContract;
use CubaDevOps\Flexi\Contracts\MessageContract;

/**
 * Simple test handler for unit testing purposes.
 * This is a real implementation, not a test double.
 */
class TestQueryHandler implements HandlerContract
{
    public function handle(DTOContract $query): MessageContract
    {
        return new PlainTextMessage('test-response');
    }
}