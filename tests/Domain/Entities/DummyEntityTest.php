<?php

namespace CubaDevOps\Flexi\Test\Domain\Entities;

use CubaDevOps\Flexi\Domain\Entities\DummyEntity;
use CubaDevOps\Flexi\Domain\ValueObjects\ID;
use PHPUnit\Framework\TestCase;

class DummyEntityTest extends TestCase
{
    public function testDummyEntity(): void
    {
        //TODO: DummyEntity is not fully implemented
        $newDummyEntity = new DummyEntity();
        $this->assertEquals([], $newDummyEntity->toArray());
        $this->assertInstanceOf(ID::class, $newDummyEntity->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $newDummyEntity->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $newDummyEntity->getUpdatedAt());
    }
}
