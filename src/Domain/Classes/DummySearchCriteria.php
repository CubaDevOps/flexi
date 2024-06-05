<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Classes;

use CubaDevOps\Flexi\Domain\Interfaces\CriteriaInterface;

class DummySearchCriteria implements CriteriaInterface
{
    public function __toString()
    {
        return __CLASS__;
    }

    public function apply($request)
    {
        return $request;
    }
}
