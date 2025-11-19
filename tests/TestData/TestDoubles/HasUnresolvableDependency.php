<?php

declare(strict_types=1);

namespace Flexi\Test\TestData\TestDoubles;

class HasUnresolvableDependency
{
    public function __construct(\stdClass $dependency)
    {
        // Constructor requires stdClass which won't be in container
    }
}