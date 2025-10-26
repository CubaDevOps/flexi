<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Classes;

use CubaDevOps\Flexi\Contracts\ValueObjects\CollectionType;

/**
 * @template TKey
 * @template TValue
 *
 * @extends Collection<TKey,TValue>
 */
class ObjectCollection extends Collection
{
    private string $class_name_type;

    public function __construct(string $class_name)
    {
        parent::__construct(new CollectionType('object'));
        $this->class_name_type = $class_name;
    }

    public function ofType(string $type): bool
    {
        return $this->class_name_type === $type;
    }

    protected function assertIsValidValue($element): void
    {
        if (get_class($element) !== $this->class_name_type) {
            throw new \RuntimeException("{$element} is not of type {$this->class_name_type}");
        }
    }
}
