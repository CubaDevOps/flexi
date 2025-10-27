<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Classes;

use CubaDevOps\Flexi\Contracts\Interfaces\CollectionInterface;;
use CubaDevOps\Flexi\Contracts\ValueObjects\CollectionType;

/**
 * @template TKey
 * @template TValue
 *
 * @implements CollectionInterface<TValue>
 *
 * @extends \ArrayObject<TKey,TValue>
 */
class Collection extends \ArrayObject implements CollectionInterface
{
    private CollectionType $type;

    public function __construct(CollectionType $type)
    {
        parent::__construct([], \ArrayIterator::STD_PROP_LIST);
        $this->type = $type;
    }

    public function count(): int
    {
        return parent::count();
    }

    /**
     * @param int|string|null $index
     */
    public function add($element, $index = null): void
    {
        $this->assertIsValidValue($element);
        (null === $index)
            ? $this->append($element)
            : $this->insert($index, $element);
    }

    protected function assertIsValidValue($element): void
    {
        if (!$this->type->isValidType($element)) {
            throw new \RuntimeException($element.' is not a valid value of type '.$this->type->getValue());
        }
    }

    public function ofType(string $type): bool
    {
        return $this->type->getValue() === $type;
    }

    /**
     * @param int|string $index
     *
     * @return $this
     */
    protected function insert($index, $element): self
    {
        $this->offsetSet($index, $element);

        return $this;
    }

    /**
     * @param int|string $index
     */
    public function get($index)
    {
        return $this->offsetGet($index);
    }

    /**
     * @param int|string $index
     */
    public function has($index): bool
    {
        return $this->offsetExists($index);
    }

    public function remove($index): void
    {
        $this->offsetUnset($index);
    }
}
