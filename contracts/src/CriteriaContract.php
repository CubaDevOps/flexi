<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts;

interface CriteriaContract
{
    public function __toString(): string;

    public function apply($request);
}
