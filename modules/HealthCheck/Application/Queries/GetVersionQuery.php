<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\HealthCheck\Application\Queries;

use CubaDevOps\Flexi\Contracts\Interfaces\DTOInterface;

class GetVersionQuery implements DTOInterface
{
    public function toArray(): array
    {
        return ['query' => 'get-version'];
    }

    public static function fromArray(array $data): self
    {
        return new self();
    }

    public function get(string $name)
    {
        $data = $this->toArray();
        return $data[$name] ?? null;
    }

    public static function validate(array $data): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return 'get-version';
    }
}
