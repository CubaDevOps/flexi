<?php

namespace CubaDevOps\Flexi\Test\Domain\DTO;

use CubaDevOps\Flexi\Domain\DTO\QueryListDTO;
use PHPUnit\Framework\TestCase;

class QueryListDTOTest extends TestCase
{
    private const DTO_ALIASES = true;

    private QueryListDTO $dto;

    public function setUp(): void
    {
        $this->dto = new QueryListDTO(self::DTO_ALIASES);
    }

    public function testToArray(): void
    {
        $expected = ['with_aliases' => self::DTO_ALIASES];

        $this->assertEquals($expected, $this->dto->toArray());
    }

    public function testFromArray(): void
    {
        $data = ['with_aliases' => self::DTO_ALIASES];

        $newDTO = QueryListDTO::fromArray($data);

        $this->assertEquals($data, $newDTO->toArray());
        $this->assertInstanceOf(QueryListDTO::class, $newDTO);
    }

    public function testToString(): void
    {
        $this->assertEquals(QueryListDTO::class, $this->dto->__toString());
    }

    public function testValidate(): void
    {
        $this->assertTrue($this->dto->validate(['with_aliases' => self::DTO_ALIASES]));
    }

    public function testGet(): void
    {
        $this->assertTrue($this->dto->get('anything'));
    }

    public function testWithAliases(): void
    {
        $this->assertTrue($this->dto->withAliases());
    }

    public function testUsage(): void
    {
        $this->assertEquals('Usage: query:list with_aliases=true|false', $this->dto->usage());
    }
}
