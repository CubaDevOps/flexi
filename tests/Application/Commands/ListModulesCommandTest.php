<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Application\Commands;

use CubaDevOps\Flexi\Application\Commands\ListModulesCommand;
use Flexi\Contracts\Interfaces\CliDTOInterface;
use Flexi\Contracts\Interfaces\DTOInterface;
use PHPUnit\Framework\TestCase;

class ListModulesCommandTest extends TestCase
{
    private ListModulesCommand $listModulesCommand;

    public function setUp(): void
    {
        $this->listModulesCommand = new ListModulesCommand();
    }

    public function testImplementsInterfaces(): void
    {
        $this->assertInstanceOf(CliDTOInterface::class, $this->listModulesCommand);
        $this->assertInstanceOf(DTOInterface::class, $this->listModulesCommand);
    }

    public function testToArray(): void
    {
        $this->assertEquals([], $this->listModulesCommand->toArray());
    }

    public function testToString(): void
    {
        $expected = ListModulesCommand::class;

        $this->assertEquals($expected, $this->listModulesCommand->__toString());
        $this->assertEquals($expected, (string)$this->listModulesCommand);
    }

    public function testFromArray(): void
    {
        $data = ['some' => 'data'];
        $command = ListModulesCommand::fromArray($data);

        $this->assertInstanceOf(ListModulesCommand::class, $command);
        $this->assertEquals([], $command->toArray()); // ListModulesCommand always returns empty array
    }

    public function testValidate(): void
    {
        $this->assertTrue(ListModulesCommand::validate([]));
        $this->assertTrue(ListModulesCommand::validate(['any' => 'data']));
    }

    public function testGet(): void
    {
        $this->assertNull($this->listModulesCommand->get('any_key'));
        $this->assertNull($this->listModulesCommand->get(''));
        $this->assertNull($this->listModulesCommand->get('nonexistent'));
    }

    public function testUsage(): void
    {
        $expectedUsage = 'Usage: modules:list';

        $this->assertEquals($expectedUsage, $this->listModulesCommand->usage());
    }
}