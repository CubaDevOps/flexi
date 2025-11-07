<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Application\Commands;

use CubaDevOps\Flexi\Application\Commands\UninstallModuleCommand;
use Flexi\Contracts\Interfaces\CliDTOInterface;
use Flexi\Contracts\Interfaces\DTOInterface;
use PHPUnit\Framework\TestCase;

class UninstallModuleCommandTest extends TestCase
{
    public function testImplementsInterfaces(): void
    {
        $command = new UninstallModuleCommand();

        $this->assertInstanceOf(CliDTOInterface::class, $command);
        $this->assertInstanceOf(DTOInterface::class, $command);
    }

    public function testConstructorWithEmptyData(): void
    {
        $command = new UninstallModuleCommand();

        $this->assertEquals([], $command->toArray());
    }

    public function testConstructorWithData(): void
    {
        $data = ['module_name' => 'cache-module', 'force' => true];
        $command = new UninstallModuleCommand($data);

        $this->assertEquals($data, $command->toArray());
    }

    public function testToString(): void
    {
        $command = new UninstallModuleCommand();

        $this->assertEquals(UninstallModuleCommand::class, $command->__toString());
        $this->assertEquals(UninstallModuleCommand::class, (string)$command);
    }

    public function testFromArray(): void
    {
        $data = ['module_name' => 'session-module'];
        $command = UninstallModuleCommand::fromArray($data);

        $this->assertInstanceOf(UninstallModuleCommand::class, $command);
        $this->assertEquals($data, $command->toArray());
    }

    public function testValidate(): void
    {
        $this->assertTrue(UninstallModuleCommand::validate([]));
        $this->assertTrue(UninstallModuleCommand::validate(['module_name' => 'test']));
    }

    public function testGet(): void
    {
        $data = ['module_name' => 'logging-module', 'purge_data' => true];
        $command = new UninstallModuleCommand($data);

        $this->assertEquals('logging-module', $command->get('module_name'));
        $this->assertTrue($command->get('purge_data'));
        $this->assertNull($command->get('nonexistent'));
    }

    public function testUsage(): void
    {
        $command = new UninstallModuleCommand();
        $expectedUsage = 'Usage: modules:uninstall module_name=<module-name> - Uninstall a module';

        $this->assertEquals($expectedUsage, $command->usage());
    }
}