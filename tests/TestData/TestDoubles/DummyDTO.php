<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\TestData\TestDoubles;

use CubaDevOps\Flexi\Contracts\DTOContract;

class DummyDTO implements DTOContract
{
    /**
     * @return string[]
     *
     * @psalm-return array{dummy: 'dummy DTO data'}
     */
    public function toArray(): array
    {
        return [];
    }

    public function __toString(): string
    {
        return __CLASS__;
    }

    /**
     * @return static
     */
    public static function fromArray(array $data): self
    {
        return new static();
    }

    public static function validate(array $data): bool
    {
        return true;
    }

    /**
     * @return null
     */
    public function get(string $name)
    {
        return null;
    }
}