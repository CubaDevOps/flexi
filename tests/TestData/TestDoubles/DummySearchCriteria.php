<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\TestData\TestDoubles;

use CubaDevOps\Flexi\Contracts\CriteriaContract;

class DummySearchCriteria implements CriteriaContract
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