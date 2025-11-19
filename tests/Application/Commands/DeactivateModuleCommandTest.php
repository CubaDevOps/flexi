<?php

declare(strict_types=1);

namespace Flexi\Test\Application\Commands;

use Flexi\Application\Commands\DeactivateModuleCommand;
use Flexi\Contracts\Interfaces\DTOInterface;
use PHPUnit\Framework\TestCase;

class DeactivateModuleCommandTest extends TestCase
{
    public function testConstructWithNamedArguments(): void
    {
        $command = new DeactivateModuleCommand([
            'module_name' => 'analytics',
            'modified_by' => 'tester',
        ]);

        $this->assertInstanceOf(DTOInterface::class, $command);
        $this->assertTrue($command->isValid());
        $this->assertSame('analytics', $command->getModuleName());
        $this->assertSame('tester', $command->getModifiedBy());
        $this->assertNull($command->getValidationError());

        $expected = [
            'module_name' => 'analytics',
            'modified_by' => 'tester',
        ];

        $this->assertSame($expected, $command->toArray());
        $this->assertSame($expected['module_name'], $command->get('module_name'));
        $this->assertSame($expected['modified_by'], $command->get('modified_by'));
        $this->assertNull($command->get('unknown_key'));
        $this->assertSame($expected, json_decode((string) $command, true));
    }

    public function testConstructWithPositionalArguments(): void
    {
        $command = new DeactivateModuleCommand(['blog']);

        $this->assertSame('blog', $command->getModuleName());
        $this->assertNull($command->getModifiedBy());
        $this->assertTrue($command->isValid());
    }

    public function testInvalidCommandProvidesErrorMessage(): void
    {
        $command = new DeactivateModuleCommand();

        $this->assertFalse($command->isValid());
        $this->assertSame('Module name is required', $command->getValidationError());
        $this->assertFalse(DeactivateModuleCommand::validate([]));
    }

    public function testFromArrayAndStaticValidate(): void
    {
        $data = [
            'module_name' => 'payments',
            'modified_by' => 'ci-pipeline',
        ];

        $command = DeactivateModuleCommand::fromArray($data);

        $this->assertInstanceOf(DeactivateModuleCommand::class, $command);
        $this->assertTrue(DeactivateModuleCommand::validate($data));
        $this->assertSame($data, $command->toArray());
    }
}
