<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Domain\Collections;

use Flexi\Contracts\Classes\ObjectCollection;
use CubaDevOps\Flexi\Test\TestData\TestDoubles\DummyDTO;
use CubaDevOps\Flexi\Test\TestData\TestDoubles\DummyEntity;
use PHPUnit\Framework\TestCase;

class ObjectCollectionTest extends TestCase
{
    private ObjectCollection $objectCollection;

    public function setUp(): void
    {
        $this->objectCollection = new ObjectCollection(DummyEntity::class);
        $this->objectCollection->add(new DummyEntity());
    }

    public function testCollection(): void
    {
        $this->assertIsInt($this->objectCollection->count());
        $this->assertInstanceOf(DummyEntity::class, $this->objectCollection->get(0));
    }

    public function testOfType(): void
    {
        $this->assertTrue($this->objectCollection->ofType(DummyEntity::class));
        $this->assertFalse($this->objectCollection->ofType(DummyDTO::class));
    }

    public function testInvalidValue(): void
    {
        $validType = DummyEntity::class;
        $invalidObject = new DummyDTO();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("{$invalidObject} is not of type {$validType}");

        $this->objectCollection->add($invalidObject);
    }
}
