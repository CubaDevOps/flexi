<?php

namespace CubaDevOps\Flexi\Test\Domain\ValueObjects;

use CubaDevOps\Flexi\Domain\ValueObjects\Order;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    public function testInvalidOperator(): void
    {
        $order = 'invalid order';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid order value: $order");
        new Order($order);
    }

    public function testGetValue(): void
    {
        $order = new Order(Order::ASC);
        $this->assertEquals(Order::ASC, $order->getValue());
    }
}
