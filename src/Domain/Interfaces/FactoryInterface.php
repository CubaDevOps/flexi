<?php

namespace CubaDevOps\Flexi\Domain\Interfaces;

interface FactoryInterface
{
    public static function getInstance(...$args): object;
}
