<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Application\Services;

/**
 * Interface for executing system commands.
 * This interface allows the Application layer to execute commands
 * without depending on Infrastructure implementations.
 */
interface CommandExecutorInterface
{
    /**
     * Execute a system command.
     *
     * @param string $command The command to execute
     * @param array $output Output lines (passed by reference)
     * @param int $returnCode Return code (passed by reference)
     * @return void
     */
    public function execute(string $command, array &$output, int &$returnCode): void;
}