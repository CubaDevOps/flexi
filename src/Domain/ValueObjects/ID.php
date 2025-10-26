<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\ValueObjects;

use CubaDevOps\Flexi\Contracts\ValueObjectContract;

class ID implements ValueObjectContract
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

    public function equals(ValueObjectContract $other): bool
    {
        return $other instanceof self && $this->id === $other->getValue();
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
