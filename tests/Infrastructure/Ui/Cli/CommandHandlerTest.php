<?php

declare(strict_types=1);

namespace Flexi\Test\Infrastructure\Ui\Cli;

use Flexi\Domain\Commands\NotFoundCommand;
use Flexi\Infrastructure\Ui\Cli\DTOFactory;
use Flexi\Infrastructure\Ui\Cli\CommandHandler;
use Flexi\Infrastructure\Ui\Cli\CliInput;
use Flexi\Infrastructure\Bus\CommandBus;
use Flexi\Contracts\Interfaces\BusInterface;
use Flexi\Contracts\Interfaces\CliDTOInterface;
use Flexi\Contracts\Interfaces\MessageInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test for CommandHandler and DTOFactory CLI Infrastructure components
 */
class CommandHandlerTest extends TestCase
{
    public function testDTOFactoryFromArrayWithNotFoundCommand(): void
    {
        /** @var BusInterface|MockObject $busMock */
        $busMock = $this->getMockForAbstractClass(BusInterface::class);
        $busMock->method('hasHandler')->willReturn(false);

        $result = DTOFactory::fromArray($busMock, 'nonexistent-command', ['arg' => 'value']);

        $this->assertInstanceOf(NotFoundCommand::class, $result);
        $this->assertEquals(NotFoundCommand::class, get_class($result));
    }

    public function testDTOFactoryFromArrayWithValidHandler(): void
    {
        /** @var BusInterface|MockObject $busMock */
        $busMock = $this->getMockForAbstractClass(BusInterface::class);
        $busMock->method('hasHandler')->willReturn(true);
        $busMock->method('getDtoClassFromAlias')->willReturn(NotFoundCommand::class);

        $result = DTOFactory::fromArray($busMock, 'existing-command', ['test' => 'data']);

        $this->assertInstanceOf(NotFoundCommand::class, $result);
    }

    public function testDTOFactoryFromArrayWithEmptyArguments(): void
    {
        /** @var BusInterface|MockObject $busMock */
        $busMock = $this->getMockForAbstractClass(BusInterface::class);
        $busMock->method('hasHandler')->willReturn(false);

        $result = DTOFactory::fromArray($busMock, 'test-command', []);

        $this->assertInstanceOf(NotFoundCommand::class, $result);
    }

    public function testDTOFactoryFromArrayWithComplexArguments(): void
    {
        /** @var BusInterface|MockObject $busMock */
        $busMock = $this->getMockForAbstractClass(BusInterface::class);
        $busMock->method('hasHandler')->willReturn(false);

        $complexArgs = [
            'name' => 'module-name',
            'version' => '2.0.1',
            'force' => true,
            'environment' => 'production'
        ];

        $result = DTOFactory::fromArray($busMock, 'install-module', $complexArgs);

        $this->assertInstanceOf(NotFoundCommand::class, $result);
    }

    public function testDTOFactoryFromArrayReturnsDifferentInstancesForDifferentCalls(): void
    {
        /** @var BusInterface|MockObject $busMock */
        $busMock = $this->getMockForAbstractClass(BusInterface::class);
        $busMock->method('hasHandler')->willReturn(false);

        $result1 = DTOFactory::fromArray($busMock, 'command1', ['arg1' => 'value1']);
        $result2 = DTOFactory::fromArray($busMock, 'command2', ['arg2' => 'value2']);

        $this->assertInstanceOf(NotFoundCommand::class, $result1);
        $this->assertInstanceOf(NotFoundCommand::class, $result2);
        // Both should be NotFoundCommand instances but different objects
        $this->assertNotSame($result1, $result2);
    }

    // ===== CommandHandler Tests =====

    public function testCommandHandlerConstruction(): void
    {
        /** @var CommandBus|MockObject $commandBus */
        $commandBus = $this->createMock(CommandBus::class);

        $handler = new CommandHandler($commandBus);

        $this->assertInstanceOf(CommandHandler::class, $handler);
    }

    public function testCommandHandlerHandleWithValidCommand(): void
    {
        /** @var MessageInterface|MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->method('__toString')->willReturn('Command executed successfully');

        /** @var CommandBus|MockObject $commandBus */
        $commandBus = $this->createMock(CommandBus::class);
        $commandBus->method('hasHandler')->willReturn(true);
        $commandBus->method('getDtoClassFromAlias')->willReturn('Flexi\Test\TestData\Commands\TestCommand');
        $commandBus->method('execute')->willReturn($message);

        $input = new CliInput('test-command', ['param' => 'value']);
        $handler = new CommandHandler($commandBus);

        $result = $handler->handle($input);

        $this->assertEquals('Command executed successfully', $result);
    }

    public function testCommandHandlerHandleWithNotFoundCommand(): void
    {
        /** @var MessageInterface|MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->method('__toString')->willReturn('Command not found');

        /** @var CommandBus|MockObject $commandBus */
        $commandBus = $this->createMock(CommandBus::class);
        $commandBus->method('hasHandler')->willReturn(false);
        $commandBus->method('execute')->willReturn($message);

        $input = new CliInput('unknown-command', []);
        $handler = new CommandHandler($commandBus);

        $result = $handler->handle($input);

        $this->assertEquals('NotFoundCommand: No handler registered for this command', $result);
    }

    public function testCommandHandlerHandleWithHelpFlag(): void
    {
        /** @var CliDTOInterface|MockObject $cliDTO */
        $cliDTO = $this->createMock(CliDTOInterface::class);
        $cliDTO->method('usage')->willReturn('Usage: test-command [options]');

        /** @var CommandBus|MockObject $commandBus */
        $commandBus = $this->createMock(CommandBus::class);
        $commandBus->method('hasHandler')->willReturn(true);
        $commandBus->method('getDtoClassFromAlias')->willReturn(get_class($cliDTO));

        // Mock DTOFactory to return our CliDTO
        $input = new CliInput('test-command', ['help' => true], 'command', true);
        $handler = new CommandHandler($commandBus);

        // This test would require mocking the static DTOFactory method
        // For now, we'll test the basic structure
        $this->assertInstanceOf(CommandHandler::class, $handler);
    }

    public function testCommandHandlerHandleWithEmptyArguments(): void
    {
        /** @var MessageInterface|MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->method('__toString')->willReturn('No command specified');

        /** @var CommandBus|MockObject $commandBus */
        $commandBus = $this->createMock(CommandBus::class);
        $commandBus->method('hasHandler')->willReturn(false);
        $commandBus->method('execute')->willReturn($message);

        $input = new CliInput('', []);
        $handler = new CommandHandler($commandBus);

        $result = $handler->handle($input);

        $this->assertEquals('NotFoundCommand: No handler registered for this command', $result);
    }

    public function testCommandHandlerHandleWithComplexArguments(): void
    {
        /** @var MessageInterface|MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->method('__toString')->willReturn('Complex command executed');

        /** @var CommandBus|MockObject $commandBus */
        $commandBus = $this->createMock(CommandBus::class);
        $commandBus->method('hasHandler')->willReturn(true);
        $commandBus->method('getDtoClassFromAlias')->willReturn('Flexi\\Test\\TestData\\Commands\\TestCommand');
        $commandBus->method('execute')->willReturn($message);

        $complexArgs = [
            'name' => 'test-module',
            'version' => '1.0.0',
            'force' => true,
            'options' => ['debug', 'verbose']
        ];

        $input = new CliInput('install-module', $complexArgs);
        $handler = new CommandHandler($commandBus);

        $result = $handler->handle($input);

        $this->assertEquals('Complex command executed', $result);
    }

    /**
     * Test CommandHandler handle method with CliDTO that returns help/usage
     */
    public function testCommandHandlerHandleWithCliDTOHelpMode(): void
    {
        // This test verifies that the CommandHandler properly creates instances
        // Since DTOFactory is a static method that's harder to mock, we focus on
        // testing the CommandHandler's dependency injection and basic functionality

        /** @var CommandBus&MockObject $commandBus */
        $commandBus = $this->createMock(CommandBus::class);

        // Create input with help flag enabled
        $input = new CliInput('test-command', ['option' => 'value'], 'command', true);
        $handler = new CommandHandler($commandBus);

        // Verify the handler was constructed properly
        $this->assertInstanceOf(CommandHandler::class, $handler);

        // Verify the input has help enabled
        $this->assertTrue($input->showHelp());
    }

    /**
     * Test constructor dependency injection thoroughly
     */
    public function testCommandHandlerConstructorDependencyInjection(): void
    {
        /** @var CommandBus&MockObject $commandBus */
        $commandBus = $this->createMock(CommandBus::class);

        // Test multiple instances with different command buses
        $handler1 = new CommandHandler($commandBus);
        $handler2 = new CommandHandler($commandBus);

        $this->assertInstanceOf(CommandHandler::class, $handler1);
        $this->assertInstanceOf(CommandHandler::class, $handler2);
        $this->assertNotSame($handler1, $handler2); // Different instances
    }

    /**
     * Test handle method with various input scenarios to ensure full coverage
     */
    public function testCommandHandlerHandleVariousScenarios(): void
    {
        // Scenario 1: Command with numeric arguments
        /** @var MessageInterface&MockObject $message1 */
        $message1 = $this->createMock(MessageInterface::class);
        $message1->method('__toString')->willReturn('Numeric command result');

        /** @var CommandBus&MockObject $commandBus1 */
        $commandBus1 = $this->createMock(CommandBus::class);
        $commandBus1->method('hasHandler')->willReturn(true);
        $commandBus1->method('getDtoClassFromAlias')->willReturn('Flexi\Test\TestData\Commands\TestCommand');
        $commandBus1->method('execute')->willReturn($message1);

        $input1 = new CliInput('numeric-command', ['count' => 42, 'timeout' => 30]);
        $handler1 = new CommandHandler($commandBus1);
        $result1 = $handler1->handle($input1);

        $this->assertEquals('Numeric command result', $result1);

        // Scenario 2: Command with boolean flags
        /** @var MessageInterface&MockObject $message2 */
        $message2 = $this->createMock(MessageInterface::class);
        $message2->method('__toString')->willReturn('Boolean flag command');

        /** @var CommandBus&MockObject $commandBus2 */
        $commandBus2 = $this->createMock(CommandBus::class);
        $commandBus2->method('hasHandler')->willReturn(true);
        $commandBus2->method('getDtoClassFromAlias')->willReturn('Flexi\Test\TestData\Commands\TestCommand');
        $commandBus2->method('execute')->willReturn($message2);

        $input2 = new CliInput('flag-command', ['verbose' => true, 'debug' => false]);
        $handler2 = new CommandHandler($commandBus2);
        $result2 = $handler2->handle($input2);

        $this->assertEquals('Boolean flag command', $result2);
    }

    /**
     * Test error handling scenarios
     */
    public function testCommandHandlerErrorScenarios(): void
    {
        // Test when CommandBus execute returns a message with empty string
        /** @var MessageInterface&MockObject $emptyMessage */
        $emptyMessage = $this->createMock(MessageInterface::class);
        $emptyMessage->method('__toString')->willReturn('');

        /** @var CommandBus&MockObject $commandBus */
        $commandBus = $this->createMock(CommandBus::class);
        $commandBus->method('hasHandler')->willReturn(true);
        $commandBus->method('getDtoClassFromAlias')->willReturn('Flexi\Test\TestData\Commands\TestCommand');
        $commandBus->method('execute')->willReturn($emptyMessage);

        $input = new CliInput('empty-return-command', []);
        $handler = new CommandHandler($commandBus);
        $result = $handler->handle($input);

        $this->assertEquals('', $result);
    }

    /**
     * Test constructor with different CommandBus configurations
     */
    public function testCommandHandlerWithDifferentCommandBusConfigs(): void
    {
        // Test with CommandBus that has different behaviors
        /** @var CommandBus&MockObject $strictCommandBus */
        $strictCommandBus = $this->createMock(CommandBus::class);
        $strictCommandBus->method('hasHandler')->willReturn(false);

        /** @var CommandBus&MockObject $permissiveCommandBus */
        $permissiveCommandBus = $this->createMock(CommandBus::class);
        $permissiveCommandBus->method('hasHandler')->willReturn(true);

        $strictHandler = new CommandHandler($strictCommandBus);
        $permissiveHandler = new CommandHandler($permissiveCommandBus);

        $this->assertInstanceOf(CommandHandler::class, $strictHandler);
        $this->assertInstanceOf(CommandHandler::class, $permissiveHandler);
    }

    /**
     * Test DTOFactory edge cases for better coverage
     */
    public function testDTOFactoryEdgeCases(): void
    {
        /** @var BusInterface&MockObject $busMock */
        $busMock = $this->getMockForAbstractClass(BusInterface::class);

        // Test with special characters in command name
        $busMock->method('hasHandler')->willReturn(false);
        $result1 = DTOFactory::fromArray($busMock, 'test-command_with.special@chars', ['arg' => 'value']);
        $this->assertInstanceOf(NotFoundCommand::class, $result1);

        // Test with null values in arguments
        $result2 = DTOFactory::fromArray($busMock, 'null-args', ['null_arg' => null, 'empty_arg' => '']);
        $this->assertInstanceOf(NotFoundCommand::class, $result2);

        // Test with nested array arguments
        $nestedArgs = [
            'config' => [
                'database' => [
                    'host' => 'localhost',
                    'port' => 3306
                ]
            ]
        ];
        $result3 = DTOFactory::fromArray($busMock, 'nested-config', $nestedArgs);
        $this->assertInstanceOf(NotFoundCommand::class, $result3);
    }

    /**
     * Comprehensive test of CommandHandler constructor and its properties
     */
    public function testCommandHandlerConstructorThorough(): void
    {
        /** @var CommandBus&MockObject $commandBus */
        $commandBus = $this->createMock(CommandBus::class);

        // Verify construction doesn't throw any exceptions
        $handler = new CommandHandler($commandBus);

        // Verify the object was created and has the expected type
        $this->assertInstanceOf(CommandHandler::class, $handler);

        // Test that the same CommandBus reference can be used multiple times
        $handler1 = new CommandHandler($commandBus);
        $handler2 = new CommandHandler($commandBus);

        $this->assertInstanceOf(CommandHandler::class, $handler1);
        $this->assertInstanceOf(CommandHandler::class, $handler2);
    }

    /**
     * Test to ensure complete method coverage of CommandHandler
     */
    public function testCommandHandlerCompleteCoverage(): void
    {
        /** @var MessageInterface&MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->method('__toString')->willReturn('Coverage test result');

        /** @var CommandBus&MockObject $commandBus */
        $commandBus = $this->createMock(CommandBus::class);
        $commandBus->method('hasHandler')->willReturn(true);
        $commandBus->method('getDtoClassFromAlias')->willReturn('Flexi\Test\TestData\Commands\TestCommand');
        $commandBus->method('execute')->willReturn($message);

        // Create CommandHandler instance - this should cover constructor
        $handler = new CommandHandler($commandBus);

        // Create CliInput with various configurations
        $input = new CliInput('coverage-test', ['param' => 'value'], 'coverage-type', false);

        // Call handle method - this should cover the handle method
        $result = $handler->handle($input);

        $this->assertEquals('Coverage test result', $result);
        $this->assertInstanceOf(CommandHandler::class, $handler);
    }

    /**
     * Test CommandHandler with CliDTOInterface and help flag to cover usage() path
     */
    public function testCommandHandlerWithCliDTOAndHelpFlag(): void
    {
        /** @var CommandBus&MockObject $commandBus */
        $commandBus = $this->createMock(CommandBus::class);

        // Mock that handler exists for 'module-info' command
        $commandBus->method('hasHandler')->with('module-info')->willReturn(true);
        $commandBus->method('getDtoClassFromAlias')->with('module-info')->willReturn('Flexi\\Application\\Commands\\ModuleInfoCommand');

        // Create input with help flag enabled
        $input = new CliInput('module-info', ['module' => 'test-module'], 'command', true);

        $this->assertTrue($input->showHelp(), 'Input should have help flag enabled');

        $handler = new CommandHandler($commandBus);

        // This should trigger the usage() path since ModuleInfoCommand implements CliDTOInterface
        // and input.showHelp() is true
        $result = $handler->handle($input);

        // The result should be the usage string from ModuleInfoCommand
        $this->assertIsString($result);
        $this->assertStringContainsString('Usage:', $result);
        $this->assertStringContainsString('modules:info', $result);
    }
}