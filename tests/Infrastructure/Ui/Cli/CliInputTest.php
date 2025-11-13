<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Ui\Cli;

use CubaDevOps\Flexi\Infrastructure\Ui\Cli\CliInput;
use PHPUnit\Framework\TestCase;

class CliInputTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $input = new CliInput('test-command', ['arg1' => 'value1']);

        $this->assertEquals('test-command', $input->getCommandName());
        $this->assertEquals(['arg1' => 'value1'], $input->getArguments());
        $this->assertEquals('query', $input->getType());
        $this->assertFalse($input->showHelp());
    }

    public function testConstructorWithAllParameters(): void
    {
        $args = ['name' => 'test', 'force' => true];
        $input = new CliInput('custom-command', $args, 'command', true);

        $this->assertEquals('custom-command', $input->getCommandName());
        $this->assertEquals($args, $input->getArguments());
        $this->assertEquals('command', $input->getType());
        $this->assertTrue($input->showHelp());
    }

    public function testToString(): void
    {
        $input = new CliInput('my-command', []);

        $this->assertEquals('my-command', (string)$input);
        $this->assertEquals('my-command', $input->__toString());
    }

    public function testGetArgumentWithExistingArg(): void
    {
        $args = ['module' => 'auth', 'force' => true, 'count' => 5];
        $input = new CliInput('test', $args);

        $this->assertEquals('auth', $input->getArgument('module'));
        $this->assertTrue($input->getArgument('force'));
        $this->assertEquals(5, $input->getArgument('count'));
    }

    public function testGetArgumentWithNonExistingArg(): void
    {
        $input = new CliInput('test', ['existing' => 'value']);

        $this->assertNull($input->getArgument('nonexistent'));
        $this->assertEquals('default', $input->getArgument('nonexistent', 'default'));
        $this->assertEquals(0, $input->getArgument('nonexistent', 0));
        $this->assertFalse($input->getArgument('nonexistent', false));
    }

    public function testGetArguments(): void
    {
        $args = [
            'name' => 'test-module',
            'version' => '1.0.0',
            'force' => true,
            'dry-run' => false
        ];
        $input = new CliInput('install', $args);

        $this->assertEquals($args, $input->getArguments());
        $this->assertIsArray($input->getArguments());
        $this->assertCount(4, $input->getArguments());
    }

    public function testGetArgumentsWithEmptyArgs(): void
    {
        $input = new CliInput('simple-command', []);

        $this->assertEquals([], $input->getArguments());
        $this->assertIsArray($input->getArguments());
        $this->assertEmpty($input->getArguments());
    }

    public function testGetType(): void
    {
        $queryInput = new CliInput('query-cmd', [], 'query');
        $commandInput = new CliInput('command-cmd', [], 'command');

        $this->assertEquals('query', $queryInput->getType());
        $this->assertEquals('command', $commandInput->getType());
    }

    public function testShowHelp(): void
    {
        $helpInput = new CliInput('help-cmd', [], 'query', true);
        $normalInput = new CliInput('normal-cmd', [], 'query', false);

        $this->assertTrue($helpInput->showHelp());
        $this->assertFalse($normalInput->showHelp());
    }
}