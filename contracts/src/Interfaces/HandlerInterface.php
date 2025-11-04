<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Interfaces;

/**
 * Interface for Command/Query Handlers
 * Pure interface without dependencies.
 */
interface HandlerInterface
{
    /**
     * Handle a DTO and optionally return a result.
     *
     * @return void|mixed
     */
    public function handle(DTOInterface $dto);
}
