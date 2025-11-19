<?php

declare(strict_types=1);

namespace Flexi\Test\Infrastructure\Ui\Cli;

use Flexi\Application\Commands\ModuleInfoCommand;
use Flexi\Infrastructure\Bus\QueryBus;
use Flexi\Infrastructure\Ui\Cli\CliInput;
use Flexi\Infrastructure\Ui\Cli\QueryHandler;
use Flexi\Contracts\Interfaces\CliDTOInterface;
use Flexi\Contracts\Interfaces\MessageInterface;
use PHPUnit\Framework\TestCase;

class QueryHandlerTest extends TestCase
{
    public function testConstructorAssignsQueryBus(): void
    {
        $queryBusMock = $this->createMock(QueryBus::class);
        $handler = new QueryHandler($queryBusMock);

        $this->assertInstanceOf(QueryHandler::class, $handler);
    }

    public function testHandleWithQueryCommand(): void
    {
        $queryBusMock = $this->createMock(QueryBus::class);
        $queryBusMock->method('hasHandler')->willReturn(false);

        $messageMock = $this->createMock(MessageInterface::class);
        $messageMock->method('__toString')->willReturn('Query result');
        $queryBusMock->method('execute')->willReturn($messageMock);

        $handler = new QueryHandler($queryBusMock);
        $input = new CliInput('list-modules', ['filter' => 'enabled'], 'query', false);

        $result = $handler->handle($input);

        $this->assertIsString($result);
        $this->assertEquals('NotFoundCommand: No handler registered for this command', $result);
    }

    public function testHandleWithHelpFlag(): void
    {
        $queryBusMock = $this->createMock(QueryBus::class);
        $queryBusMock->method('hasHandler')->willReturn(false);

        // Mock that returns a DTO that implements CliDTOInterface
        $dtoMock = $this->createMock(CliDTOInterface::class);
        $dtoMock->method('usage')->willReturn('Usage: query-command [options]');

        $handler = new QueryHandler($queryBusMock);
        $input = new CliInput('help-query', [], 'query', true);

        // Since DTOFactory::fromArray is static and hard to mock,
        // we'll test the general behavior
        $result = $handler->handle($input);

        $this->assertIsString($result);
    }

    public function testHandleWithNotFoundQuery(): void
    {
        $queryBusMock = $this->createMock(QueryBus::class);
        $queryBusMock->method('hasHandler')->willReturn(false);

        $messageMock = $this->createMock(MessageInterface::class);
        $messageMock->method('__toString')->willReturn('Query not found');
        $queryBusMock->method('execute')->willReturn($messageMock);

        $handler = new QueryHandler($queryBusMock);
        $input = new CliInput('nonexistent-query', [], 'query', false);

        $result = $handler->handle($input);

        $this->assertIsString($result);
        $this->assertEquals('NotFoundCommand: No handler registered for this command', $result);
    }

    public function testHandleWithArguments(): void
    {
        $queryBusMock = $this->createMock(QueryBus::class);
        $queryBusMock->method('hasHandler')->willReturn(false);

        $messageMock = $this->createMock(MessageInterface::class);
        $messageMock->method('__toString')->willReturn('Filtered query result');
        $queryBusMock->method('execute')->willReturn($messageMock);

        $handler = new QueryHandler($queryBusMock);

        $arguments = [
            'name' => 'auth',
            'status' => 'active',
            'version' => '1.0'
        ];
        $input = new CliInput('find-module', $arguments, 'query', false);

        $result = $handler->handle($input);

        $this->assertIsString($result);
        $this->assertEquals('NotFoundCommand: No handler registered for this command', $result);
    }

    public function testHandleReturnsStringFromQueryBus(): void
    {
        $queryBusMock = $this->createMock(QueryBus::class);
        $queryBusMock->method('hasHandler')->willReturn(true);
        $queryBusMock->method('getDtoClassFromAlias')->willReturn('Flexi\\Test\\TestData\\Queries\\TestQuery');

        $messageMock = $this->createMock(MessageInterface::class);
        $messageMock->method('__toString')->willReturn('Complex query response');
        $queryBusMock->method('execute')->willReturn($messageMock);

        $handler = new QueryHandler($queryBusMock);
        $input = new CliInput('complex-query', ['param' => 'value'], 'query', false);

        $result = $handler->handle($input);

        $this->assertIsString($result);
        $this->assertEquals('Complex query response', $result);
    }

    public function testHandleMethodExists(): void
    {
        $this->assertTrue(method_exists(QueryHandler::class, 'handle'));
        $this->assertTrue(method_exists(QueryHandler::class, '__construct'));
    }

    public function testHandleWithEmptyArguments(): void
    {
        $queryBusMock = $this->createMock(QueryBus::class);
        $queryBusMock->method('hasHandler')->willReturn(false);

        $messageMock = $this->createMock(MessageInterface::class);
        $messageMock->method('__toString')->willReturn('Empty args query');
        $queryBusMock->method('execute')->willReturn($messageMock);

        $handler = new QueryHandler($queryBusMock);
        $input = new CliInput('status', [], 'query', false);

        $result = $handler->handle($input);

        $this->assertIsString($result);
        $this->assertEquals('NotFoundCommand: No handler registered for this command', $result);
    }

    public function testQueryHandlerWithCliDTOAndHelpFlag(): void
    {
        // Test the usage() return path when dto instanceof CliDTOInterface && input->showHelp()
        $queryBusMock = $this->createMock(QueryBus::class);

        // Mock that ModuleInfoCommand has a handler and will be returned
        $queryBusMock->method('hasHandler')->willReturn(true);
        $queryBusMock->method('getDtoClassFromAlias')->willReturn(\Flexi\Application\Commands\ModuleInfoCommand::class);

        $handler = new QueryHandler($queryBusMock);
        $input = new CliInput('module:info', ['module' => 'test'], 'query', true); // help flag = true

        $result = $handler->handle($input);

        // Should return usage string from ModuleInfoCommand->usage()
        $this->assertIsString($result);
        $this->assertStringContainsString('Usage:', $result);
    }
}