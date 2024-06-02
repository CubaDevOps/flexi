<?php

namespace CubaDevOps\Flexi\Domain\DTO;

use CubaDevOps\Flexi\Domain\Interfaces\DTOInterface;

class CommandListDTO implements DTOInterface
{
    private bool $with_aliases;

    public function __construct(bool $with_aliases = false)
    {
        $this->with_aliases = $with_aliases;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'with_aliases' => $this->with_aliases
        ];
    }

    /**
     * @return string
     */
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
        return new self($data['with_aliases'] ?? false);
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
     * @return bool
     */
    public function get(string $name): bool
    {
        return $this->with_aliases;
    }

    public function withAliases(): bool
    {
        return $this->with_aliases;
    }
}