<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Interfaces;

interface ConfigurationRepositoryInterface
{
    public function get(string $key);

    public function has(string $key): bool;

    public function getAll(): array;
}
