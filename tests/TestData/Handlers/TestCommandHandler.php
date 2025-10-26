<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\TestData\Handlers;

use CubaDevOps\Flexi\Contracts\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Contracts\DTOContract;
use CubaDevOps\Flexi\Contracts\HandlerContract;
use CubaDevOps\Flexi\Contracts\MessageContract;

/**
 * Simple test command handler for unit testing purposes.
 * This is a real implementation, not a test double.
 */
class TestCommandHandler implements HandlerContract
{
    public function handle(DTOContract $command): MessageContract
    {
        return new PlainTextMessage('test-command-response');
    }
}