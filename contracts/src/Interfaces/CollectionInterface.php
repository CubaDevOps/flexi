<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Interfaces;

interface CollectionInterface
{
    public function count(): int;

    public function add($element): void;

    public function remove($index): void;

    public function get($index);

    public function ofType(string $type): bool;
}
