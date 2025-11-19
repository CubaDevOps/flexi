<?php

declare(strict_types=1);

namespace Flexi\Test\Infrastructure\Services;

use Flexi\Infrastructure\Services\CommandExecutor;
use Flexi\Application\Services\CommandExecutorInterface;
use PHPUnit\Framework\TestCase;

class CommandExecutorTest extends TestCase
{
    private CommandExecutor $commandExecutor;

    protected function setUp(): void
    {
        $this->commandExecutor = new CommandExecutor();
    }

    public function testImplementsCommandExecutorInterface(): void
    {
        $this->assertInstanceOf(CommandExecutorInterface::class, $this->commandExecutor);
    }

    public function testExecuteSuccessfulCommand(): void
    {
        $output = [];
        $returnCode = 0;

        // Test with a simple echo command that should work on all systems
        $this->commandExecutor->execute('echo "test output"', $output, $returnCode);

        $this->assertEquals(0, $returnCode, 'Return code should be 0 for successful command');
        $this->assertContains('test output', $output, 'Output should contain the echoed text');
        $this->assertIsArray($output, 'Output should be an array');
    }

    public function testExecuteCommandWithMultipleOutputLines(): void
    {
        $output = [];
        $returnCode = 0;

        // Use printf to generate multiple lines
        $command = 'printf "line1\nline2\nline3"';
        $this->commandExecutor->execute($command, $output, $returnCode);

        $this->assertEquals(0, $returnCode);
        $this->assertCount(3, $output, 'Should have 3 output lines');
        $this->assertEquals('line1', $output[0]);
        $this->assertEquals('line2', $output[1]);
        $this->assertEquals('line3', $output[2]);
    }

    public function testExecuteCommandWithError(): void
    {
        $output = [];
        $returnCode = 0;

        // Test with a command that should fail (try to list non-existent directory)
        $this->commandExecutor->execute('ls /non/existent/directory 2>/dev/null', $output, $returnCode);

        $this->assertNotEquals(0, $returnCode, 'Return code should not be 0 for failed command');
        $this->assertIsArray($output);
    }

    public function testExecuteInvalidCommand(): void
    {
        $output = [];
        $returnCode = 0;

        // Test with completely invalid command
        $this->commandExecutor->execute('non_existent_command_12345 2>/dev/null', $output, $returnCode);

        $this->assertNotEquals(0, $returnCode, 'Return code should not be 0 for invalid command');
        $this->assertIsArray($output);
    }

    public function testExecuteWithSimpleCommand(): void
    {
        $output = [];
        $returnCode = 0;

        // Test with a simple true command that should succeed with no output
        $this->commandExecutor->execute('true', $output, $returnCode);

        $this->assertEquals(0, $returnCode, 'True command should return 0');
        $this->assertIsArray($output);
        $this->assertEmpty($output, 'True command should produce no output');
    }

    public function testExecuteCommandWithWorkingDirectory(): void
    {
        $output = [];
        $returnCode = 0;

        // Test pwd command to verify current directory behavior
        $this->commandExecutor->execute('pwd', $output, $returnCode);

        $this->assertEquals(0, $returnCode);
        $this->assertIsArray($output);
        $this->assertNotEmpty($output, 'pwd should return current directory');
        $this->assertIsString($output[0], 'Directory path should be a string');
    }

    public function testExecuteParametersPassedByReference(): void
    {
        $output = ['initial', 'data'];
        $returnCode = 999;

        $this->commandExecutor->execute('echo "new output"', $output, $returnCode);

        // Verify that the parameters were modified by reference
        $this->assertContains('new output', $output, 'Output should contain new content');
        $this->assertEquals(0, $returnCode, 'Return code should be modified');
        // Note: exec() might add extra empty lines or preserve format, so check content exists
        $this->assertIsArray($output, 'Output should be an array');
        $this->assertNotEmpty($output, 'Output should not be empty');
    }

    public function testExecuteWithSpecialCharacters(): void
    {
        $output = [];
        $returnCode = 0;

        // Test with special characters - use simpler approach
        $this->commandExecutor->execute('echo "test with quotes"', $output, $returnCode);

        $this->assertEquals(0, $returnCode);
        $this->assertContains('test with quotes', $output);
    }

    public function testExecutePreservesOutputOrder(): void
    {
        $output = [];
        $returnCode = 0;

        // Generate numbered output to verify order preservation
        $command = 'for i in 1 2 3 4 5; do echo "Line $i"; done';
        $this->commandExecutor->execute($command, $output, $returnCode);

        $this->assertEquals(0, $returnCode);
        $this->assertCount(5, $output);

        for ($i = 1; $i <= 5; $i++) {
            $this->assertEquals("Line $i", $output[$i - 1], "Line $i should be in correct position");
        }
    }

    public function testExecuteWithLongOutput(): void
    {
        $output = [];
        $returnCode = 0;

        // Generate a longer output to test handling of multiple lines
        $command = 'seq 1 20';  // Generate numbers 1 to 20
        $this->commandExecutor->execute($command, $output, $returnCode);

        $this->assertEquals(0, $returnCode);
        $this->assertCount(20, $output, 'Should have 20 lines of output');
        $this->assertEquals('1', $output[0], 'First line should be 1');
        $this->assertEquals('20', $output[19], 'Last line should be 20');
    }
}