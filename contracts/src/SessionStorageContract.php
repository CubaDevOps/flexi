<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts;

use ArrayAccess;

/**
 * @template TValue
 * @template TKey
 *
 * @extends ArrayAccess<TKey,TValue>
 */
interface SessionStorageContract extends ArrayAccess
{
    public function set(string $key, $value): void;

    public function get(string $key);

    public function has(string $key): bool;

    public function remove(string $key): void;
}