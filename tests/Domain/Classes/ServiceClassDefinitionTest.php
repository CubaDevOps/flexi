<?php

namespace CubaDevOps\Flexi\Test\Domain\Classes;

use CubaDevOps\Flexi\Domain\Classes\ServiceClassDefinition;
use PHPUnit\Framework\TestCase;

class ServiceClassDefinitionTest extends TestCase
{
    private const SERVICE_CLASS = 'testServiceClass';
    private const SERVICE_ARGS  = ['arg' => 'test-arg'];

    private ServiceClassDefinition $classDefinition;

     public function setUp(): void
     {
         $this->classDefinition = new ServiceClassDefinition(self::SERVICE_CLASS, self::SERVICE_ARGS);
     }

     public function testGetClass(): void
     {
         $this->assertEquals(self::SERVICE_CLASS, $this->classDefinition->getClass());
     }

    public function testGetMethod(): void
    {
        $this->assertEmpty($this->classDefinition->getMethod());
    }

    public function testGetArgs(): void
    {
        $this->assertEquals(self::SERVICE_ARGS, $this->classDefinition->getArguments());
    }
}
