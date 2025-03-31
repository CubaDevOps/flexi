<?php

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use CubaDevOps\Flexi\Domain\Interfaces\CacheInterface;
use CubaDevOps\Flexi\Infrastructure\Classes\InMemoryCache;

class CacheFactory
{

    public static function getInstance(): CacheInterface
    {
        //Todo: implement other caches based on configuration using ConfigurationFactory::getInstance()
        return new InMemoryCache();
    }
}