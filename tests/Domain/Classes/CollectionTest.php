<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Domain\Classes;

use CubaDevOps\Flexi\Domain\Classes\Collection;
use CubaDevOps\Flexi\Domain\Entities\DummyEntity;
use CubaDevOps\Flexi\Domain\ValueObjects\CollectionType;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    private const EXPECTED_COUNT = 2;
    private Collection $collection;
    private array $items;
    protected function setUp(): void
    {
        $type = new CollectionType('object');
        $this->collection = new Collection($type);

        $this->items = [new DummyEntity(), new DummyEntity()];
    }

    public function testCount(): void
    {
        $this->collection->add($this->items[0]);
        $this->collection->add($this->items[1]);

        $this->assertEquals(self::EXPECTED_COUNT, $this->collection->count());
    }

    public function testAdd(): void
    {
        $this->collection->add($this->items[0]);
        $this->collection->add($this->items[1]);

        $expected = $this->items[1];
        /** @var DummyEntity $actual */
        $actual = $this->collection->get(1);

        $this->assertTrue($this->collection->has(1));
        $this->assertEquals($expected, $actual);
        $this->assertInstanceOf(DummyEntity::class, $actual);
    }

    public function testAddInvalidType(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('1 is not a valid value of type object');
        $this->collection->add(1);
    }

    public function testOfType(): void
    {
        $this->assertTrue($this->collection->ofType('object'));

        $this->assertFalse($this->collection->ofType('resource'));
        $this->assertFalse($this->collection->ofType('integer'));
        $this->assertFalse($this->collection->ofType('string'));
        $this->assertFalse($this->collection->ofType('array'));
    }

    public function testRemove(): void
    {
        $this->collection->add($this->items[0], 0);
        $this->collection->add($this->items[1], 0);

        $this->assertTrue($this->collection->has(0));
        $this->assertFalse($this->collection->has(1));

        $this->collection->remove(0);

        $this->assertFalse($this->collection->has(0));
    }
}
