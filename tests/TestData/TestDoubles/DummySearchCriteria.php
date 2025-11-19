<?php

declare(strict_types=1);

namespace Flexi\Test\TestData\TestDoubles;

use Flexi\Contracts\Interfaces\CriteriaInterface;

class DummySearchCriteria implements CriteriaInterface
{
    public function __toString(): string
    {
        return __CLASS__;
    }

    public function apply($request)
    {
        return $request;
    }
}