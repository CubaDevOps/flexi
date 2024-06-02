<?php

namespace CubaDevOps\Flexi\Domain\DTO;

use CubaDevOps\Flexi\Domain\Interfaces\DTOInterface;

class DummyDTO implements DTOInterface
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
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): DTOInterface
    {
        return new self();
    }

    /**
     * @param array $data
     * @return bool
     */
    public static function validate(array $data): bool
    {
        return true;
    }

    /**
     * @param string $name
     * @return null
     */
    public function get(string $name)
    {
        return null;
    }
}
