<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\TestData\TestDoubles\Configuration;

use Flexi\Contracts\Interfaces\ConfigurationInterface;
use function sprintf;
use CubaDevOps\Flexi\Test\TestData\TestDoubles\Configuration\ConfigurationNotFound;

final class ArrayConfiguration implements ConfigurationInterface
{
    /** @var array<string, mixed> */
    private array $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function get($id)
    {
        if (!$this->has($id)) {
            throw new ConfigurationNotFound(sprintf('Configuration key "%s" not found', (string) $id));
        }

        return $this->values[$id];
    }

    public function has($id): bool
    {
        return array_key_exists($id, $this->values);
    }
}
