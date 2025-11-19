<?php

declare(strict_types=1);

namespace Flexi\Test\Infrastructure\Ui\Cli;

use Flexi\Infrastructure\Ui\Cli\ConsoleExceptionFormatter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

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

    public function testGetExceptionNameReturnsShortClassName(): void
    {
        $result = $this->invokeFormatter('getExceptionName', [new \RuntimeException('boom')]);

        $this->assertSame('RuntimeException', $result);
    }

    public function testFormatStackTraceLimitsFrames(): void
    {
        $exception = $this->createExceptionWithDepth(12);

        $output = $this->invokeFormatter('formatStackTrace', [$exception]);

        $this->assertStringContainsString('#9', $output);
        $this->assertStringNotContainsString('#10', $output);
        $this->assertStringContainsString('more frames', $output);
    }

    public function testFormatPreviousExceptionStopsAfterDepthLimit(): void
    {
        $level4 = new \RuntimeException('Level 4');
        $level3 = new \RuntimeException('Level 3', 0, $level4);
        $level2 = new \RuntimeException('Level 2', 0, $level3);
        $level1 = new \RuntimeException('Level 1', 0, $level2);

        $output = $this->invokeFormatter('formatPreviousException', [$level1]);

        $this->assertStringContainsString('Level 1', $output);
        $this->assertStringContainsString('Level 2', $output);
        $this->assertStringContainsString('Level 3', $output);
        $this->assertStringContainsString('... (more nested exceptions)', $output);
        $this->assertStringNotContainsString('Level 4', $output);
    }

    public function testShortenPathMakesPathRelative(): void
    {
        $path = getcwd() . '/sub/dir/file.php';

        $this->assertSame('./sub/dir/file.php', $this->invokeFormatter('shortenPath', [$path]));
    }

    public function testShortenPathTruncatesVeryLongPaths(): void
    {
        $longPath = '/var/www/' . str_repeat('a', 80) . '/file.php';

        $result = $this->invokeFormatter('shortenPath', [$longPath]);

        $this->assertStringStartsWith('...', $result);
        $this->assertLessThanOrEqual(60, strlen($result));
    }

    public function testWrapTextSplitsLongLines(): void
    {
        $text = str_repeat('LongWords ', 10);

        $result = $this->invokeFormatter('wrapText', [$text, 20]);

        $this->assertStringContainsString(PHP_EOL, $result);
        $this->assertGreaterThan(1, substr_count($result, PHP_EOL));
    }

    private function invokeFormatter(string $method, array $arguments = [])
    {
        $reflection = new ReflectionClass(ConsoleExceptionFormatter::class);
        $reflectionMethod = $reflection->getMethod($method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs(null, $arguments);
    }

    private function createExceptionWithDepth(int $depth): \RuntimeException
    {
        return $this->triggerNestedException($depth);
    }

    private function triggerNestedException(int $level): \RuntimeException
    {
        if ($level <= 0) {
            try {
                throw new \RuntimeException('deep stack');
            } catch (\RuntimeException $exception) {
                return $exception;
            }
        }

        return $this->triggerNestedException($level - 1);
    }
}

