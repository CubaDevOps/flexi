<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Classes;

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
