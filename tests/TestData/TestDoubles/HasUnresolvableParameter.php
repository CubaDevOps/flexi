<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\TestData\TestDoubles;

class HasUnresolvableParameter
{
    public function __construct($unresolvableParam)
    {
        // Constructor with parameter that has no type hint
    }
}