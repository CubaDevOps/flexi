<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Ui\Cli;

use CubaDevOps\Flexi\Application\Commands\NotFoundCommand;
use CubaDevOps\Flexi\Infrastructure\Ui\Cli\DTOFactory;
use Flexi\Contracts\Interfaces\BusInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test for DTOFactory and basic CLI Infrastructure components
 * CommandHandler is tested separately through integration tests
 */
class CommandHandlerTest extends TestCase
{
    public function testDTOFactoryFromArrayWithNotFoundCommand(): void
    {
        $busMock = $this->getMockForAbstractClass(BusInterface::class);
        $busMock->method('hasHandler')->willReturn(false);

        $result = DTOFactory::fromArray($busMock, 'nonexistent-command', ['arg' => 'value']);

        $this->assertInstanceOf(NotFoundCommand::class, $result);
        $this->assertEquals(NotFoundCommand::class, get_class($result));
    }

    public function testDTOFactoryFromArrayWithValidHandler(): void
    {
        $busMock = $this->getMockForAbstractClass(BusInterface::class);
        $busMock->method('hasHandler')->willReturn(true);
        $busMock->method('getDtoClassFromAlias')->willReturn(NotFoundCommand::class);

        $result = DTOFactory::fromArray($busMock, 'existing-command', ['test' => 'data']);

        $this->assertInstanceOf(NotFoundCommand::class, $result);
    }

    public function testDTOFactoryFromArrayWithEmptyArguments(): void
    {
        $busMock = $this->getMockForAbstractClass(BusInterface::class);
        $busMock->method('hasHandler')->willReturn(false);

        $result = DTOFactory::fromArray($busMock, 'test-command', []);

        $this->assertInstanceOf(NotFoundCommand::class, $result);
    }

    public function testDTOFactoryFromArrayWithComplexArguments(): void
    {
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
        $busMock = $this->getMockForAbstractClass(BusInterface::class);
        $busMock->method('hasHandler')->willReturn(false);

        $result1 = DTOFactory::fromArray($busMock, 'command1', ['arg1' => 'value1']);
        $result2 = DTOFactory::fromArray($busMock, 'command2', ['arg2' => 'value2']);

        $this->assertInstanceOf(NotFoundCommand::class, $result1);
        $this->assertInstanceOf(NotFoundCommand::class, $result2);
        // Both should be NotFoundCommand instances but different objects
        $this->assertNotSame($result1, $result2);
    }
}