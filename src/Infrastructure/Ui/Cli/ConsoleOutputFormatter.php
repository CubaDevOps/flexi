<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Ui\Cli;

class ConsoleOutputFormatter
{

    /**
     * @param string $output
     * @param string $style
     * @param bool $new_line
     * @return string
     */
    public static function format(string $output, string $style = 'info', bool $new_line = true): string
    {
        $styles = array(
            'success' => "[0;32m%s[0m",
            'error' => "[31;31m%s[0m",
            'info' => "[33;33m%s[0m",

            'black' => "[0;30m%s[0m",
            'red' => "[0;31m%s[0m",
            'green' => "[0;32m%s[0m",
            'yellow' => "[0;33m%s[0m",
            'blue' => "[0;34m%s[0m",
            'purple' => "[0;35m%s[0m",
            'cyan' => "[0;36m%s[0m",
            'gray' => "[0;37m%s[0m",
            'graphite' => "[1;30m%s[0m",

            'bold red' => "[1;31m%s[0m",
            'bold green' => "[1;32m%s[0m",
            'bold yellow' => "[1;33m%s[0m",
            'bold blue' => "[1;34m%s[0m",
            'bold purple' => "[1;35m%s[0m",
            'bold cyan' => "[1;36m%s[0m",
            'bold white' => "[1;37m%s[0m",

            'bg black' => "[40;1;37m%s[0m",
            'bg red' => "[41;1;37m%s[0m",
            'bg green' => "[42;1;37m%s[0m",
            'bg yellow' => "[43;1;37m%s[0m",
            'bg blue' => "[44;1;37m%s[0m",
            'bg purple' => "[45;1;37m%s[0m",
            'bg cyan' => "[46;1;37m%s[0m",
            'bg gray' => "[47;1;37m%s[0m",

            'underscore' => "[4;37m%s[0m",
            'inverted' => "[7;37m%s[0m",
            'blink' => "[5;37m%s[0m",
        );

        $format = $styles[$style] ?? '%s';

        if ($new_line) {
            $format .= PHP_EOL;
        }

        return sprintf($format, $output);
    }
}
