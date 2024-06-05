<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Interfaces;

interface CriteriaInterface
{
    public function apply($request);
}
