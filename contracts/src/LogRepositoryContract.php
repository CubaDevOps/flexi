<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts;

interface LogRepositoryContract
{
    public function save(LogContract $log): void;
}