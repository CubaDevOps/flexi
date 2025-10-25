<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\DevTools\Application\Queries;

use CubaDevOps\Flexi\Domain\Interfaces\CliDTOInterface;
use CubaDevOps\Flexi\Domain\Interfaces\DTOInterface;

class ListQueriesQuery implements CliDTOInterface
{
    private bool $with_aliases;

    public function __construct(bool $with_aliases = false)
    {
        $this->with_aliases = $with_aliases;
    }

    /**
     * @return self
     */
    public static function fromArray(array $data): DTOInterface
    {
        $with_aliases = isset($data['with_aliases']) && ('true' === $data['with_aliases'] || true === $data['with_aliases']);

        return new self($with_aliases);
    }

    public static function validate(array $data): bool
    {
        return true;
    }

    public function toArray(): array
    {
        return [
            'with_aliases' => $this->with_aliases,
        ];
    }

    public function __toString(): string
    {
        return __CLASS__;
    }

    public function get(string $name): bool
    {
        return $this->with_aliases;
    }

    public function withAliases(): bool
    {
        return $this->with_aliases;
    }

    public function usage(): string
    {
        return 'Usage: query:list with_aliases=true|false';
    }
}