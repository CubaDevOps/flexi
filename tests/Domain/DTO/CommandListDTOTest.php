<?php

namespace CubaDevOps\Flexi\Test\Domain\DTO;

use CubaDevOps\Flexi\Domain\DTO\CommandListDTO;
use CubaDevOps\Flexi\Domain\Interfaces\CliDTOInterface;
use PHPUnit\Framework\TestCase;

class CommandListDTOTest extends TestCase
{
    private const DTO_ALIASES = true;

    private CommandListDTO $dto;

    public function setUp(): void
    {
        $this->dto = new CommandListDTO(self::DTO_ALIASES);
    }

    public function testToArray(): void
    {
        $expected = ['with_aliases' => self::DTO_ALIASES];

        $this->assertEquals($expected, $this->dto->toArray());
    }

    public function testFromArray(): void
    {
        $data = ['with_aliases' => self::DTO_ALIASES];

        $newDTO = CommandListDTO::fromArray($data);

        $this->assertEquals($data, $newDTO->toArray());
        $this->assertInstanceOf(CommandListDTO::class, $newDTO);
    }

    public function testToString(): void
    {
        $this->assertEquals(CommandListDTO::class, $this->dto->__toString());
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
        $this->assertEquals('Usage: command:list with_aliases=true|false', $this->dto->usage());
    }
}
