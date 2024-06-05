<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\ValueObjects;

use CubaDevOps\Flexi\Domain\Interfaces\ValueObjectInterface;

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

    public function getValue()
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
