<?php

declare(strict_types=1);

namespace Flexi\Test\Application\Commands;

use Flexi\Application\Commands\UpdateModuleEnvironmentCommand;
use Flexi\Contracts\Interfaces\DTOInterface;
use PHPUnit\Framework\TestCase;

class UpdateModuleEnvironmentCommandTest extends TestCase
{
    public function testConstructWithNamedArguments(): void
    {
        $command = new UpdateModuleEnvironmentCommand([
            'module_name' => 'analytics',
            'modified_by' => 'tester',
            'force' => true,
        ]);

        $this->assertInstanceOf(DTOInterface::class, $command);
        $this->assertTrue($command->isValid());
        $this->assertSame('analytics', $command->getModuleName());
        $this->assertSame('tester', $command->getModifiedBy());
        $this->assertTrue($command->isForceUpdate());
        $this->assertNull($command->getValidationError());

        $expected = [
            'module_name' => 'analytics',
            'modified_by' => 'tester',
            'force' => true,
        ];

        $this->assertSame($expected, $command->toArray());
        $this->assertSame($expected['module_name'], $command->get('module_name'));
        $this->assertSame($expected['modified_by'], $command->get('modified_by'));
        $this->assertTrue($command->get('force'));
        $this->assertNull($command->get('unknown'));
        $this->assertSame($expected, json_decode((string) $command, true));
    }

    public function testConstructWithPositionalArguments(): void
    {
        $command = new UpdateModuleEnvironmentCommand(['catalog']);

        $this->assertSame('catalog', $command->getModuleName());
        $this->assertNull($command->getModifiedBy());
        $this->assertFalse($command->isForceUpdate());
        $this->assertTrue($command->isValid());
    }

    public function testInvalidCommandProvidesErrorMessage(): void
    {
        $command = new UpdateModuleEnvironmentCommand();

        $this->assertFalse($command->isValid());
        $this->assertSame('Module name is required', $command->getValidationError());
        $this->assertFalse(UpdateModuleEnvironmentCommand::validate([]));
    }

    public function testFromArrayAndStaticValidate(): void
    {
        $data = [
            'module_name' => 'payments',
            'modified_by' => 'ci-pipeline',
            'force' => 1,
        ];

        $command = UpdateModuleEnvironmentCommand::fromArray($data);

        $this->assertInstanceOf(UpdateModuleEnvironmentCommand::class, $command);
        $this->assertTrue(UpdateModuleEnvironmentCommand::validate($data));
        $this->assertTrue($command->isForceUpdate());
        $this->assertSame([
            'module_name' => 'payments',
            'modified_by' => 'ci-pipeline',
            'force' => true,
        ], $command->toArray());
    }
}
