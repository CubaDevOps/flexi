<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Application\Commands;

use CubaDevOps\Flexi\Application\Commands\InstallModuleCommand;
use Flexi\Contracts\Interfaces\CliDTOInterface;
use Flexi\Contracts\Interfaces\DTOInterface;
use PHPUnit\Framework\TestCase;

class InstallModuleCommandTest extends TestCase
{
    public function testImplementsInterfaces(): void
    {
        $command = new InstallModuleCommand();

        $this->assertInstanceOf(CliDTOInterface::class, $command);
        $this->assertInstanceOf(DTOInterface::class, $command);
    }

    public function testConstructorWithEmptyData(): void
    {
        $command = new InstallModuleCommand();

        $this->assertEquals([], $command->toArray());
    }

    public function testConstructorWithData(): void
    {
        $data = ['module_name' => 'test-module', 'version' => '1.0.0'];
        $command = new InstallModuleCommand($data);

        $this->assertEquals($data, $command->toArray());
    }

    public function testToString(): void
    {
        $command = new InstallModuleCommand();

        $this->assertEquals(InstallModuleCommand::class, $command->__toString());
        $this->assertEquals(InstallModuleCommand::class, (string)$command);
    }

    public function testFromArray(): void
    {
        $data = ['module_name' => 'cache-module'];
        $command = InstallModuleCommand::fromArray($data);

        $this->assertInstanceOf(InstallModuleCommand::class, $command);
        $this->assertEquals($data, $command->toArray());
    }

    public function testValidate(): void
    {
        $this->assertTrue(InstallModuleCommand::validate([]));
        $this->assertTrue(InstallModuleCommand::validate(['module_name' => 'test']));
    }

    public function testGet(): void
    {
        $data = ['module_name' => 'test-module', 'force' => true];
        $command = new InstallModuleCommand($data);

        $this->assertEquals('test-module', $command->get('module_name'));
        $this->assertTrue($command->get('force'));
        $this->assertNull($command->get('nonexistent'));
    }

    public function testUsage(): void
    {
        $command = new InstallModuleCommand();
        $expectedUsage = 'Usage: modules:install module_name=<module-name> - Install a module';

        $this->assertEquals($expectedUsage, $command->usage());
    }
}