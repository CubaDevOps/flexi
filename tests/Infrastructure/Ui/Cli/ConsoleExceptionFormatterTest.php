<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Ui\Cli;

use CubaDevOps\Flexi\Infrastructure\Ui\Cli\ConsoleExceptionFormatter;
use PHPUnit\Framework\TestCase;

class ConsoleExceptionFormatterTest extends TestCase
{
    public function testFormatExceptionInDebugMode(): void
    {
        $exception = new \RuntimeException('Test error message', 500);

        $output = ConsoleExceptionFormatter::format($exception, true);

        // Verify output contains key elements
        $this->assertStringContainsString('ERROR: RuntimeException', $output);
        $this->assertStringContainsString('Test error message', $output);
        $this->assertStringContainsString('Location:', $output);
        $this->assertStringContainsString('Stack Trace:', $output);
        $this->assertStringContainsString('Error Code:', $output);
        $this->assertStringContainsString('500', $output);
    }

    public function testFormatExceptionInNormalMode(): void
    {
        $exception = new \RuntimeException('Test error message');

        $output = ConsoleExceptionFormatter::format($exception, false);

        // Verify output contains basic elements
        $this->assertStringContainsString('ERROR: RuntimeException', $output);
        $this->assertStringContainsString('Test error message', $output);
        $this->assertStringContainsString('Tip: Enable DEBUG_MODE', $output);

        // Verify it doesn't contain debug information
        $this->assertStringNotContainsString('Location:', $output);
        $this->assertStringNotContainsString('Stack Trace:', $output);
    }

    public function testFormatExceptionWithPrevious(): void
    {
        $previous = new \InvalidArgumentException('Previous error');
        $exception = new \RuntimeException('Main error', 0, $previous);

        $output = ConsoleExceptionFormatter::format($exception, true);

        $this->assertStringContainsString('Previous Exception:', $output);
        $this->assertStringContainsString('InvalidArgumentException', $output);
        $this->assertStringContainsString('Previous error', $output);
    }

    public function testFormatSimpleError(): void
    {
        $output = ConsoleExceptionFormatter::formatSimpleError('Simple error message');

        $this->assertStringContainsString('[ERROR]', $output);
        $this->assertStringContainsString('Simple error message', $output);
    }

    public function testFormatSuccess(): void
    {
        $output = ConsoleExceptionFormatter::formatSuccess('Operation successful');

        $this->assertStringContainsString('[SUCCESS]', $output);
        $this->assertStringContainsString('Operation successful', $output);
    }

    public function testFormatWarning(): void
    {
        $output = ConsoleExceptionFormatter::formatWarning('Warning message');

        $this->assertStringContainsString('[WARNING]', $output);
        $this->assertStringContainsString('Warning message', $output);
    }

    public function testFormatExceptionWithoutErrorCode(): void
    {
        $exception = new \RuntimeException('Test error without code');

        $output = ConsoleExceptionFormatter::format($exception, true);

        // Should not show error code section when code is 0
        $this->assertStringNotContainsString('Error Code:', $output);
    }

    public function testFormatDifferentExceptionTypes(): void
    {
        $exceptions = [
            new \InvalidArgumentException('Invalid argument'),
            new \LogicException('Logic error'),
            new \OutOfBoundsException('Out of bounds'),
        ];

        foreach ($exceptions as $exception) {
            $output = ConsoleExceptionFormatter::format($exception, true);

            $className = (new \ReflectionClass($exception))->getShortName();
            $this->assertStringContainsString($className, $output);
        }
    }
}

