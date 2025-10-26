<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts;

/**
 * Contract for Command/Query Handlers
 * Pure contract without dependencies.
 */
interface HandlerContract
{
    /**
     * Handle a DTO and optionally return a result.
     *
     * @return void|mixed
     */
    public function handle(DTOContract $dto);
}
