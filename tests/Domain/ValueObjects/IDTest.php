<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Domain\ValueObjects;

use CubaDevOps\Flexi\Contracts\ValueObjects\ID;
use PHPUnit\Framework\TestCase;

class IDTest extends TestCase
{
    public function testCreate(): void
    {
        $id = new ID('uuid');
        $this->assertEquals('uuid', $id->getValue());
    }
}
