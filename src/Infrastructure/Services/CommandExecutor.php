<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Services;

use CubaDevOps\Flexi\Application\Services\CommandExecutorInterface;

/**
 * Service for executing system commands.
 * This class encapsulates command execution to make it testable.
 */
class CommandExecutor implements CommandExecutorInterface
{
    /**
     * Execute a system command.
     *
     * @param string $command The command to execute
     * @param array $output Output lines (passed by reference)
     * @param int $returnCode Return code (passed by reference)
     * @return void
     */
    public function execute(string $command, array &$output, int &$returnCode): void
    {
        exec($command, $output, $returnCode);
    }
}