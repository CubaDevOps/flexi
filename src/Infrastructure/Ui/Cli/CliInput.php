<?php

declare(strict_types=1);

namespace Flexi\Infrastructure\Ui\Cli;

class CliInput
{
    private string $command_name;
    private array $args;
    /**
     * @var false
     */
    private bool $show_help;
    private string $type;

    public function __construct(string $command_name, array $args, string $type = 'query', $show_help = false)
    {
        $this->command_name = $command_name;
        $this->args = $args;
        $this->show_help = $show_help;
        $this->type = $type;
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

    public function getType(): string
    {
        return $this->type;
    }
}
