<?php

namespace CubaDevOps\Flexi\Test\Domain\Classes;

use CubaDevOps\Flexi\Domain\Classes\ServiceFactoryDefinition;
use PHPUnit\Framework\TestCase;

class ServiceFactoryDefinitionTest extends TestCase
{
    private const FACTORY_CLASS = 'testServiceClass';
    private const FACTORY_METHOD = 'getInstance';
    private const FACTORY_ARGS  = ['arg' => 'test-arg'];

    private ServiceFactoryDefinition $factory;

    public function setUp(): void
    {
        $this->factory = new ServiceFactoryDefinition(
            self::FACTORY_CLASS,
            self::FACTORY_METHOD,
            self::FACTORY_ARGS
        );
    }

    public function testGetClass(): void
    {
        $this->assertEquals(self::FACTORY_CLASS, $this->factory->getClass());
    }

    public function testGetMethod(): void
    {
        $this->assertEquals(self::FACTORY_METHOD, $this->factory->getMethod());
    }

    public function testGetArgs(): void
    {
        $this->assertEquals(self::FACTORY_ARGS, $this->factory->getArguments());
    }
}
