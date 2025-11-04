<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Ui\Cli;

use Throwable;

class ConsoleExceptionFormatter
{
    /**
     * Format an exception for console output in a user-friendly way
     */
    public static function format(Throwable $exception, bool $debugMode = false): string
    {
        $output = '';

        // Header with error type
        $output .= PHP_EOL;
        $output .= ConsoleOutputFormatter::format(
            str_repeat('=', 80),
            'bold red'
        );
        $output .= ConsoleOutputFormatter::format(
            '  ERROR: ' . self::getExceptionName($exception),
            'bold red'
        );
        $output .= ConsoleOutputFormatter::format(
            str_repeat('=', 80),
            'bold red'
        );
        $output .= PHP_EOL;

        // Error message
        $output .= ConsoleOutputFormatter::format('Message:', 'bold yellow');
        $output .= self::wrapText('  ' . $exception->getMessage(), 78);
        $output .= PHP_EOL;

        if ($debugMode) {
            // File and line information
            $output .= ConsoleOutputFormatter::format('Location:', 'bold yellow');
            $output .= ConsoleOutputFormatter::format(
                '  File: ' . $exception->getFile(),
                'cyan'
            );
            $output .= ConsoleOutputFormatter::format(
                '  Line: ' . $exception->getLine(),
                'cyan'
            );
            $output .= PHP_EOL;

            // Error code if present
            if ($exception->getCode() !== 0) {
                $output .= ConsoleOutputFormatter::format('Error Code:', 'bold yellow');
                $output .= ConsoleOutputFormatter::format(
                    '  ' . $exception->getCode(),
                    'cyan'
                );
                $output .= PHP_EOL;
            }

            // Stack trace
            $output .= ConsoleOutputFormatter::format('Stack Trace:', 'bold yellow');
            $output .= self::formatStackTrace($exception);
            $output .= PHP_EOL;

            // Previous exception if exists
            if ($previous = $exception->getPrevious()) {
                $output .= ConsoleOutputFormatter::format(
                    str_repeat('-', 80),
                    'yellow'
                );
                $output .= ConsoleOutputFormatter::format('Previous Exception:', 'bold yellow');
                $output .= self::formatPreviousException($previous);
            }
        } else {
            // In non-debug mode, just show a helpful hint
            $output .= ConsoleOutputFormatter::format(
                'Tip: Enable DEBUG_MODE to see detailed error information',
                'gray'
            );
            $output .= PHP_EOL;
        }

        $output .= ConsoleOutputFormatter::format(
            str_repeat('=', 80),
            'bold red'
        );

        return $output;
    }

    /**
     * Get a human-readable exception name
     */
    private static function getExceptionName(Throwable $exception): string
    {
        $className = get_class($exception);
        $parts = explode('\\', $className);
        return end($parts);
    }

    /**
     * Format the stack trace in a readable way
     */
    private static function formatStackTrace(Throwable $exception): string
    {
        $trace = $exception->getTrace();
        $output = '';
        $maxFrames = 10; // Limit to first 10 frames to avoid overwhelming output

        foreach (array_slice($trace, 0, $maxFrames) as $index => $frame) {
            $file = $frame['file'] ?? 'unknown';
            $line = $frame['line'] ?? '?';
            $function = '';

            if (isset($frame['class'])) {
                $function = $frame['class'] . $frame['type'] . $frame['function'];
            } elseif (isset($frame['function'])) {
                $function = $frame['function'];
            }

            $output .= ConsoleOutputFormatter::format(
                sprintf('  #%d %s', $index, $function),
                'cyan'
            );
            $output .= ConsoleOutputFormatter::format(
                sprintf('      %s:%s', self::shortenPath($file), $line),
                'gray'
            );
        }

        if (count($trace) > $maxFrames) {
            $remaining = count($trace) - $maxFrames;
            $output .= ConsoleOutputFormatter::format(
                sprintf('  ... %d more frames', $remaining),
                'gray'
            );
        }

        return $output;
    }

    /**
     * Format previous exceptions recursively
     */
    private static function formatPreviousException(Throwable $exception, int $depth = 1): string
    {
        if ($depth > 3) { // Limit depth to avoid too long output
            return ConsoleOutputFormatter::format('  ... (more nested exceptions)', 'gray');
        }

        $output = '';
        $indent = str_repeat('  ', $depth);

        $output .= ConsoleOutputFormatter::format(
            $indent . self::getExceptionName($exception),
            'yellow'
        );
        $output .= self::wrapText(
            $indent . 'Message: ' . $exception->getMessage(),
            78 - strlen($indent)
        );
        $output .= ConsoleOutputFormatter::format(
            $indent . 'at ' . self::shortenPath($exception->getFile()) . ':' . $exception->getLine(),
            'gray'
        );

        if ($previous = $exception->getPrevious()) {
            $output .= PHP_EOL;
            $output .= ConsoleOutputFormatter::format($indent . 'Caused by:', 'yellow');
            $output .= self::formatPreviousException($previous, $depth + 1);
        }

        return $output;
    }

    /**
     * Shorten file paths to make them more readable
     */
    private static function shortenPath(string $path): string
    {
        // Try to make paths relative to common base directories
        $cwd = getcwd();
        if ($cwd && strpos($path, $cwd) === 0) {
            return '.' . substr($path, strlen($cwd));
        }

        // If path is too long, show only the last part
        if (strlen($path) > 60) {
            return '...' . substr($path, -57);
        }

        return $path;
    }

    /**
     * Wrap long text to fit within console width
     */
    private static function wrapText(string $text, int $width = 78): string
    {
        $lines = explode("\n", $text);
        $output = '';

        foreach ($lines as $line) {
            if (strlen($line) <= $width) {
                $output .= ConsoleOutputFormatter::format($line, 'cyan');
            } else {
                $wrapped = wordwrap($line, $width, "\n", true);
                foreach (explode("\n", $wrapped) as $wrappedLine) {
                    $output .= ConsoleOutputFormatter::format($wrappedLine, 'cyan');
                }
            }
        }

        return $output;
    }

    /**
     * Format a simple error message without exception details
     */
    public static function formatSimpleError(string $message): string
    {
        $output = '';
        $output .= PHP_EOL;
        $output .= ConsoleOutputFormatter::format('[ERROR] ', 'bold red', false);
        $output .= ConsoleOutputFormatter::format($message, 'red');
        $output .= PHP_EOL;

        return $output;
    }

    /**
     * Format a success message
     */
    public static function formatSuccess(string $message): string
    {
        $output = '';
        $output .= PHP_EOL;
        $output .= ConsoleOutputFormatter::format('[SUCCESS] ', 'bold green', false);
        $output .= ConsoleOutputFormatter::format($message, 'green');
        $output .= PHP_EOL;

        return $output;
    }

    /**
     * Format a warning message
     */
    public static function formatWarning(string $message): string
    {
        $output = '';
        $output .= PHP_EOL;
        $output .= ConsoleOutputFormatter::format('[WARNING] ', 'bold yellow', false);
        $output .= ConsoleOutputFormatter::format($message, 'yellow');
        $output .= PHP_EOL;

        return $output;
    }
}

