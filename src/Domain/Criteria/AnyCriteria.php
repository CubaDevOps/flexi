<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Criteria;

use CubaDevOps\Flexi\Contracts\CriteriaContract;

/**
 * Criteria that matches any request without filtering.
 * Used when no specific criteria is needed.
 */
class AnyCriteria implements CriteriaContract
{
    public function __toString(): string
    {
        return 'AnyCriteria: matches any request';
    }

    public function apply($request)
    {
        return $request;
    }
}