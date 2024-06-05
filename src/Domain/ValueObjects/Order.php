<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\ValueObjects;

use CubaDevOps\Flexi\Domain\Interfaces\ValueObjectInterface;

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

    public function getValue()
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
