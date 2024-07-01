<?php

namespace CubaDevOps\Flexi\Test\Domain\DTO;

use CubaDevOps\Flexi\Domain\DTO\DummyDTO;
use PHPUnit\Framework\TestCase;

class DummyDTOTest extends TestCase
{
    private DummyDTO $dto;

    public function setUp(): void
    {
        $this->dto = new DummyDTO();
    }

    public function testToArray(): void
    {
        $this->assertEquals([], $this->dto->toArray());
    }

    public function testFromArray(): void
    {
        $newDTO = DummyDTO::fromArray([]);

        $this->assertInstanceOf(DummyDTO::class, $newDTO);
    }

    public function testToString(): void
    {
        $this->assertEquals(DummyDTO::class, $this->dto->__toString());
    }

    public function testValidate(): void
    {
        $this->assertTrue($this->dto->validate([]));
    }

    public function testGet(): void
    {
        $this->assertNull($this->dto->get('anything'));
    }
}
