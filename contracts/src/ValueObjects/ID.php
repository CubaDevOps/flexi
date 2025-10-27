<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\ValueObjects;

use CubaDevOps\Flexi\Contracts\Interfaces\ValueObjectInterface;;

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

    public function equals(ValueObjectInterface $other): bool
    {
        return $other instanceof self && $this->id === $other->getValue();
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
