<?php

namespace CubaDevOps\Flexi\Domain\Classes;

use ArrayObject;
use CubaDevOps\Flexi\Domain\Interfaces\CollectionInterface;
use CubaDevOps\Flexi\Domain\ValueObjects\CollectionType;

/**
 * @template TKey
 * @template TValue
 *
 * @implements CollectionInterface<TValue>
 *
 * @extends ArrayObject<TKey,TValue>
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
        return $this->getIterator()->count();
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
        $element_type = gettype($element);
        if (!$this->ofType($element_type)) {
            throw new \RuntimeException("{$element} is not of type {$this->type->getValue()}");
        }

        if (!$this->type->isValidType($element)) {
            throw new \RuntimeException("{$element} is not a valid value of type {$this->type->getValue()}");
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

    public function has(string $index): bool
    {
        return $this->offsetExists($index);
    }

    public function remove($index): void
    {
        $this->offsetUnset($index);
    }
}
