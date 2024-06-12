<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Ui\Cli;

class CliInput
{
    private string $command_name;
    private array $args;
    /**
     * @var false
     */
    private bool $show_help;
    private bool $is_command;

    public function __construct(string $command_name, array $args, bool $is_command, $show_help = false)
    {
        $this->command_name = $command_name;
        $this->args = $args;
        $this->show_help = $show_help;
        $this->is_command = $is_command;
    }

    public function __toString(): string
    {
        return $this->getCommandName();
    }

    public function getCommandName(): string
    {
        return $this->command_name;
    }

    public function getArgument(string $name, $default = null)
    {
        return $this->args[$name] ?? $default;
    }

    public function getArguments(): array
    {
        return $this->args;
    }

    public function showHelp(): bool
    {
        return $this->show_help;
    }

    public function isCommand(): bool
    {
        return $this->is_command;
    }
}
