<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Application\Commands;

use CubaDevOps\Flexi\Application\Commands\ValidateModulesCommand;
use Flexi\Contracts\Interfaces\CliDTOInterface;
use Flexi\Contracts\Interfaces\DTOInterface;
use PHPUnit\Framework\TestCase;

class ValidateModulesCommandTest extends TestCase
{
    public function testImplementsInterfaces(): void
    {
        $command = new ValidateModulesCommand();

        $this->assertInstanceOf(CliDTOInterface::class, $command);
        $this->assertInstanceOf(DTOInterface::class, $command);
    }

    public function testConstructorWithEmptyData(): void
    {
        $command = new ValidateModulesCommand();

        $this->assertEquals([], $command->toArray());
    }

    public function testConstructorWithData(): void
    {
        $data = ['strict_mode' => true, 'verbose' => false];
        $command = new ValidateModulesCommand($data);

        $this->assertEquals($data, $command->toArray());
    }

    public function testToString(): void
    {
        $command = new ValidateModulesCommand();

        $this->assertEquals(ValidateModulesCommand::class, $command->__toString());
        $this->assertEquals(ValidateModulesCommand::class, (string)$command);
    }

    public function testFromArray(): void
    {
        $data = ['check_dependencies' => true];
        $command = ValidateModulesCommand::fromArray($data);

        $this->assertInstanceOf(ValidateModulesCommand::class, $command);
        $this->assertEquals($data, $command->toArray());
    }

    public function testValidate(): void
    {
        $this->assertTrue(ValidateModulesCommand::validate([]));
        $this->assertTrue(ValidateModulesCommand::validate(['strict_mode' => false]));
    }

    public function testGet(): void
    {
        $data = ['strict_mode' => true, 'fix_errors' => false];
        $command = new ValidateModulesCommand($data);

        $this->assertTrue($command->get('strict_mode'));
        $this->assertFalse($command->get('fix_errors'));
        $this->assertNull($command->get('nonexistent'));
    }

    public function testUsage(): void
    {
        $command = new ValidateModulesCommand();
        $expectedUsage = 'Usage: modules:validate - Validate all modules\' configuration';

        $this->assertEquals($expectedUsage, $command->usage());
    }
}