<?php

declare(strict_types=1);

namespace Flexi\Test\Application\Commands;

use Flexi\Application\Commands\ModuleInfoCommand;
use Flexi\Contracts\Interfaces\CliDTOInterface;
use Flexi\Contracts\Interfaces\DTOInterface;
use PHPUnit\Framework\TestCase;

class ModuleInfoCommandTest extends TestCase
{
    public function testImplementsInterfaces(): void
    {
        $command = new ModuleInfoCommand();

        $this->assertInstanceOf(CliDTOInterface::class, $command);
        $this->assertInstanceOf(DTOInterface::class, $command);
    }

    public function testConstructorWithEmptyData(): void
    {
        $command = new ModuleInfoCommand();

        $this->assertEquals([], $command->toArray());
    }

    public function testConstructorWithData(): void
    {
        $data = ['module_name' => 'cache-module'];
        $command = new ModuleInfoCommand($data);

        $this->assertEquals($data, $command->toArray());
    }

    public function testToString(): void
    {
        $command = new ModuleInfoCommand();

        $this->assertEquals(ModuleInfoCommand::class, $command->__toString());
        $this->assertEquals(ModuleInfoCommand::class, (string)$command);
    }

    public function testFromArray(): void
    {
        $data = ['module_name' => 'session-module'];
        $command = ModuleInfoCommand::fromArray($data);

        $this->assertInstanceOf(ModuleInfoCommand::class, $command);
        $this->assertEquals($data, $command->toArray());
    }

    public function testValidate(): void
    {
        $this->assertTrue(ModuleInfoCommand::validate([]));
        $this->assertTrue(ModuleInfoCommand::validate(['module_name' => 'test']));
    }

    public function testGet(): void
    {
        $data = ['module_name' => 'logging-module', 'show_details' => true];
        $command = new ModuleInfoCommand($data);

        $this->assertEquals('logging-module', $command->get('module_name'));
        $this->assertTrue($command->get('show_details'));
        $this->assertNull($command->get('nonexistent'));
    }

    public function testUsage(): void
    {
        $command = new ModuleInfoCommand();
        $expectedUsage = 'Usage: modules:info module_name=<module-name> - Show detailed information about a specific module';

        $this->assertEquals($expectedUsage, $command->usage());
    }
}