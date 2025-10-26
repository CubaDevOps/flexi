<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts;

interface ConfigurationRepositoryContract
{
    public function get(string $key);

    public function has(string $key): bool;

    public function getAll(): array;
}