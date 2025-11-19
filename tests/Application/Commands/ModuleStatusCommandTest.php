<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Application\Commands;

use CubaDevOps\Flexi\Application\Commands\ModuleStatusCommand;
use Flexi\Contracts\Interfaces\CliDTOInterface;
use Flexi\Contracts\Interfaces\DTOInterface;
use PHPUnit\Framework\TestCase;

class ModuleStatusCommandTest extends TestCase
{
    public function testConstructWithNamedArguments(): void
    {
        $command = new ModuleStatusCommand([
            'module_name' => 'blog',
            'details' => true,
            'conflicts' => true,
            'type' => 'vendor',
        ]);

        $this->assertInstanceOf(CliDTOInterface::class, $command);
        $this->assertInstanceOf(DTOInterface::class, $command);
        $this->assertSame('blog', $command->getModuleName());
        $this->assertTrue($command->shouldShowDetails());
        $this->assertTrue($command->shouldShowConflicts());
        $this->assertSame('vendor', $command->getFilterByType());
        $this->assertTrue($command->isSpecificModule());
        $this->assertFalse($command->isAllModules());
        $this->assertTrue($command->isValid());
        $this->assertNull($command->getValidationError());

        $expected = [
            'module_name' => 'blog',
            'details' => true,
            'conflicts' => true,
            'type' => 'vendor',
        ];
        $this->assertSame($expected, $command->toArray());
        $this->assertSame($expected['module_name'], $command->get('module_name'));
        $this->assertSame($expected['details'], $command->get('details'));
        $this->assertSame($expected['conflicts'], $command->get('conflicts'));
        $this->assertSame($expected['type'], $command->get('type'));
        $this->assertNull($command->get('unknown'));
        $this->assertSame($expected, json_decode((string) $command, true));
    }

    public function testConstructWithPositionalArgumentsUsesDefaults(): void
    {
        $command = new ModuleStatusCommand(['analytics']);

        $this->assertSame('analytics', $command->getModuleName());
        $this->assertFalse($command->shouldShowDetails());
        $this->assertFalse($command->shouldShowConflicts());
        $this->assertNull($command->getFilterByType());
        $this->assertTrue($command->isSpecificModule());
    }

    public function testConstructWithoutModuleTargetsAllModules(): void
    {
        $command = new ModuleStatusCommand();

        $this->assertNull($command->getModuleName());
        $this->assertTrue($command->isAllModules());
        $this->assertTrue($command->isValid());
    }

    public function testInvalidTypeFailsValidation(): void
    {
        $command = new ModuleStatusCommand(['type' => 'invalid']);

        $this->assertFalse($command->isValid());
        $this->assertSame("Invalid type filter 'invalid'. Valid types: local, vendor, mixed", $command->getValidationError());
        $this->assertFalse(ModuleStatusCommand::validate(['type' => 'invalid']));
    }

    public function testStaticValidateSupportsCliFlags(): void
    {
        $this->assertTrue(ModuleStatusCommand::validate(['--type' => 'mixed']));
    }

    public function testFromArrayProducesEquivalentCommand(): void
    {
        $data = [
            'module_name' => 'catalog',
            'details' => 1,
            'conflicts' => 0,
            'type' => 'local',
        ];

        $command = ModuleStatusCommand::fromArray($data);

        $this->assertInstanceOf(ModuleStatusCommand::class, $command);
        $this->assertSame('catalog', $command->getModuleName());
        $this->assertTrue($command->shouldShowDetails());
        $this->assertFalse($command->shouldShowConflicts());
        $this->assertSame('local', $command->getFilterByType());
        $this->assertSame([
            'module_name' => 'catalog',
            'details' => true,
            'conflicts' => false,
            'type' => 'local',
        ], $command->toArray());
    }

    public function testUsageMessageIsDocumented(): void
    {
        $expected = 'Usage: module:status module_name=module [--details] [--conflicts] [--type=local|vendor|mixed]';
        $this->assertSame($expected, (new ModuleStatusCommand())->usage());
    }
}
