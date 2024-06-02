<?php

namespace CubaDevOps\Flexi\Domain\ValueObjects;

use CubaDevOps\Flexi\Domain\Interfaces\ValueObjectInterface;

class ID implements ValueObjectInterface
{
    private string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getValue(): string
    {
        return $this->id;
    }
}
