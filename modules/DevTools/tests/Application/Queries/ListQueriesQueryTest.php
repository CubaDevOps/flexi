<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\DevTools\Test\Application\Queries;

use CubaDevOps\Flexi\Modules\DevTools\Application\Queries\ListQueriesQuery;
use PHPUnit\Framework\TestCase;

class ListQueriesQueryTest extends TestCase
{
    private const DTO_ALIASES = true;

    private ListQueriesQuery $dto;

    public function setUp(): void
    {
        $this->dto = new ListQueriesQuery(self::DTO_ALIASES);
    }

    public function testToArray(): void
    {
        $expected = ['with_aliases' => self::DTO_ALIASES];

        $this->assertEquals($expected, $this->dto->toArray());
    }

    public function testFromArray(): void
    {
        $data = ['with_aliases' => self::DTO_ALIASES];

        $newDTO = ListQueriesQuery::fromArray($data);

        $this->assertEquals($data, $newDTO->toArray());
        $this->assertInstanceOf(ListQueriesQuery::class, $newDTO);
    }

    public function testToString(): void
    {
        $this->assertEquals(ListQueriesQuery::class, $this->dto->__toString());
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
