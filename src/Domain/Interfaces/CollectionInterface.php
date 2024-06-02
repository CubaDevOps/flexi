<?php

namespace CubaDevOps\Flexi\Domain\Interfaces;

interface CollectionInterface
{
    public function count(): int;

    public function add($element): void;

    public function remove($index): void;

    public function get($index);

    public function ofType(string $type): bool;
}
