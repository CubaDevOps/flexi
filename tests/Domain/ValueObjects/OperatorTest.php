<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Domain\ValueObjects;

use Flexi\Contracts\ValueObjects\Operator;
use PHPUnit\Framework\TestCase;

class OperatorTest extends TestCase
{
    public function testInvalidOperator(): void
    {
        $operator = 'invalid operator';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid operator: $operator");
        new Operator($operator);
    }

    public function testGetValue(): void
    {
        $operator = new Operator('!=');
        $this->assertEquals('!=', $operator->getValue());
    }
}
