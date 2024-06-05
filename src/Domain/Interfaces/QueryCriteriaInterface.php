<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Interfaces;

use CubaDevOps\Flexi\Domain\ValueObjects\Operator;
use CubaDevOps\Flexi\Domain\ValueObjects\Order;

interface QueryCriteriaInterface extends CriteriaInterface
{
    public function where(string $column, Operator $operator, $value): CriteriaInterface;

    public function limit(int $limit): CriteriaInterface;

    public function orderBy(string $column, string $direction = Order::ASC): CriteriaInterface;
}
