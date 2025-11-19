<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Ui\Cli;

use CubaDevOps\Flexi\Infrastructure\Ui\Cli\ConsoleOutputFormatter;
use PHPUnit\Framework\TestCase;

final class ConsoleOutputFormatterTest extends TestCase
{
    public function testFormatsWithKnownStyleAndNewLine(): void
    {
        $result = ConsoleOutputFormatter::format('Done', 'success');

        $this->assertSame("\033[0;32mDone\033[0m" . PHP_EOL, $result);
    }

    public function testFormatsWithUnknownStyleFallsBackToPlainText(): void
    {
        $result = ConsoleOutputFormatter::format('Raw message', 'missing-style');

        $this->assertSame('Raw message' . PHP_EOL, $result);
    }

    public function testCanDisableNewLine(): void
    {
        $result = ConsoleOutputFormatter::format('Failure', 'error', false);

        $this->assertSame("\033[31;31mFailure\033[0m", $result);
    }

    public function testSupportsCompositeStyles(): void
    {
        $boldBlue = ConsoleOutputFormatter::format('Heads up', 'bold blue', false);
        $backgroundCyan = ConsoleOutputFormatter::format('Context', 'bg cyan');

        $this->assertSame("\033[1;34mHeads up\033[0m", $boldBlue);
        $this->assertSame("\033[46;1;37mContext\033[0m" . PHP_EOL, $backgroundCyan);
    }
}
