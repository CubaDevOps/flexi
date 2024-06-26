<?php

namespace CubaDevOps\Flexi\Test\Application\UseCase;

use CubaDevOps\Flexi\Application\UseCase\ListCommands;
use CubaDevOps\Flexi\Domain\Classes\CommandBus;
use CubaDevOps\Flexi\Domain\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Domain\DTO\CommandListDTO;
use PHPUnit\Framework\TestCase;

class ListCommandsTest extends TestCase
{
    private CommandBus $commandBus;
    private ListCommands $listCommands;

    public function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBus::class);

        $this->listCommands = new ListCommands($this->commandBus);
    }

    public function testHandleEvent(): void
    {
        $dto = $this->createMock(CommandListDTO::class);
        $commands = ['command1', 'command2'];

        $dto->expects($this->once())
            ->method('withAliases')->willReturn(true);

        $this->commandBus->expects($this->once())
            ->method('getHandlersDefinition')
            ->willReturn($commands);

        $message = $this->listCommands->handle($dto);

        $this->assertInstanceOf(PlainTextMessage::class, $message);

        $this->assertEquals(
            $commands, json_decode((string)$message, true, 512, JSON_THROW_ON_ERROR)
        );

        $this->assertInstanceOf(\DateTimeImmutable::class, $message->get('created_at'));
    }
}
