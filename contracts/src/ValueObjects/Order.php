<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\ValueObjects;

use CubaDevOps\Flexi\Contracts\Interfaces\ValueObjectInterface;

class Order implements ValueObjectInterface
{
    public const ASC = 'ASC';
    public const DESC = 'DESC';

    private string $order;

    public function __construct(string $order)
    {
        $this->assertThatIsValidOrder($order);
        $this->order = $order;
    }

    public function getValue(): string
    {
        return $this->order;
    }

    public function equals(ValueObjectInterface $other): bool
    {
        return $other instanceof self && $this->order === $other->getValue();
    }

    public function __toString(): string
    {
        return $this->order;
    }

    private function assertThatIsValidOrder(string $order): void
    {
        if (!in_array($order, [self::ASC, self::DESC], true)) {
            throw new \InvalidArgumentException("Invalid order value: $order");
        }
    }
}
