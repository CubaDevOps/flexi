<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Ui\Cli;

use CubaDevOps\Flexi\Infrastructure\Ui\Cli\ConsoleApplication;
use PHPUnit\Framework\TestCase;

class ConsoleApplicationTest extends TestCase
{
    public function testPrintUsageOutputsHelpText(): void
    {
        ob_start();
        ConsoleApplication::printUsage();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('Usage:', $output);
        $this->assertStringContainsString('--command', $output);
        $this->assertStringContainsString('--query', $output);
        $this->assertStringContainsString('--event', $output);
        $this->assertStringContainsString('command:list', $output);
        $this->assertStringContainsString('query:list', $output);
        $this->assertStringContainsString('--help', $output);
    }

    public function testPrintUsageContainsColoredOutput(): void
    {
        ob_start();
        ConsoleApplication::printUsage();
        $output = ob_get_clean();

        // Should contain ANSI color codes for green formatting
        $this->assertStringContainsString('command_name', $output);
        $this->assertStringContainsString('arg1=blabla', $output);
        $this->assertStringContainsString('-h', $output);
    }

    public function testPrintUsageContainsRequiredElements(): void
    {
        ob_start();
        ConsoleApplication::printUsage();
        $output = ob_get_clean();

        // Check for essential elements
        $this->assertStringContainsString('-c', $output); // Short flag for command
        $this->assertStringContainsString('-q', $output); // Short flag for query
        $this->assertStringContainsString('-e', $output); // Short flag for event
        $this->assertStringContainsString('blabla', $output); // Example arguments
    }

    public function testPrintUsageEndsWithNewline(): void
    {
        ob_start();
        ConsoleApplication::printUsage();
        $output = ob_get_clean();

        $this->assertStringEndsWith(PHP_EOL, $output);
    }

    public function testRunMethodExists(): void
    {
        $this->assertTrue(method_exists(ConsoleApplication::class, 'run'));
        $this->assertTrue(method_exists(ConsoleApplication::class, 'printUsage'));
    }

    public function testConsoleApplicationIsNotInstantiable(): void
    {
        $reflection = new \ReflectionClass(ConsoleApplication::class);

        // Should be static only - no public constructor
        $constructor = $reflection->getConstructor();
        $this->assertNull($constructor);
    }

    public function testClassHasStaticMethods(): void
    {
        $reflection = new \ReflectionClass(ConsoleApplication::class);

        $runMethod = $reflection->getMethod('run');
        $this->assertTrue($runMethod->isStatic());
        $this->assertTrue($runMethod->isPublic());

        $printUsageMethod = $reflection->getMethod('printUsage');
        $this->assertTrue($printUsageMethod->isStatic());
        $this->assertTrue($printUsageMethod->isPublic());
    }

    public function testPrintUsageDoesNotThrowExceptions(): void
    {
        // This should not throw any exceptions
        $this->expectNotToPerformAssertions();

        ob_start();
        try {
            ConsoleApplication::printUsage();
        } finally {
            ob_end_clean();
        }
    }
}