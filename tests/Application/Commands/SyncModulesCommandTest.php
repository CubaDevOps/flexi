<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Application\Commands;

use CubaDevOps\Flexi\Application\Commands\SyncModulesCommand;
use Flexi\Contracts\Interfaces\CliDTOInterface;
use Flexi\Contracts\Interfaces\DTOInterface;
use PHPUnit\Framework\TestCase;

class SyncModulesCommandTest extends TestCase
{
    public function testImplementsInterfaces(): void
    {
        $command = new SyncModulesCommand();

        $this->assertInstanceOf(CliDTOInterface::class, $command);
        $this->assertInstanceOf(DTOInterface::class, $command);
    }

    public function testConstructorWithEmptyData(): void
    {
        $command = new SyncModulesCommand();

        $this->assertEquals([], $command->toArray());
    }

    public function testConstructorWithData(): void
    {
        $data = ['force' => true, 'dry_run' => false];
        $command = new SyncModulesCommand($data);

        $this->assertEquals($data, $command->toArray());
    }

    public function testToString(): void
    {
        $command = new SyncModulesCommand();

        $this->assertEquals(SyncModulesCommand::class, $command->__toString());
        $this->assertEquals(SyncModulesCommand::class, (string)$command);
    }

    public function testFromArray(): void
    {
        $data = ['auto_discover' => true];
        $command = SyncModulesCommand::fromArray($data);

        $this->assertInstanceOf(SyncModulesCommand::class, $command);
        $this->assertEquals($data, $command->toArray());
    }

    public function testValidate(): void
    {
        $this->assertTrue(SyncModulesCommand::validate([]));
        $this->assertTrue(SyncModulesCommand::validate(['force' => true]));
    }

    public function testGet(): void
    {
        $data = ['force' => true, 'auto_discover' => false];
        $command = new SyncModulesCommand($data);

        $this->assertTrue($command->get('force'));
        $this->assertFalse($command->get('auto_discover'));
        $this->assertNull($command->get('nonexistent'));
    }

    public function testUsage(): void
    {
        $command = new SyncModulesCommand();
        $expectedUsage = 'Usage: modules:sync - Auto-discover and sync all modules';

        $this->assertEquals($expectedUsage, $command->usage());
    }
}