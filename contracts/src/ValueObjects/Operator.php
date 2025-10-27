<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\ValueObjects;

use CubaDevOps\Flexi\Contracts\Interfaces\ValueObjectInterface;

class Operator implements ValueObjectInterface
{
    public const OPERATORS = [
        '=',
        '!=',
        '>',
        '>=',
        '<',
        '<=',
        'LIKE',
        'NOT LIKE',
        'IN',
        'NOT IN',
        'BETWEEN',
        'NOT BETWEEN',
        'IS NULL',
        'IS NOT NULL',
    ];

    private string $operator;

    public function __construct(string $operator)
    {
        $this->assertThatIsValidOperator($operator);
        $this->operator = $operator;
    }

    public function getValue(): string
    {
        return $this->operator;
    }

    public function equals(ValueObjectInterface $other): bool
    {
        return $other instanceof self && $this->operator === $other->getValue();
    }

    public function __toString(): string
    {
        return $this->operator;
    }

    private function assertThatIsValidOperator(string $operator): void
    {
        if (!in_array($operator, self::OPERATORS, true)) {
            throw new \InvalidArgumentException("Invalid operator: $operator");
        }
    }
}
