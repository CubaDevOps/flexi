<?php

namespace CubaDevOps\Flexi\Test\Domain\Classes;

use CubaDevOps\Flexi\Domain\Classes\Service;
use CubaDevOps\Flexi\Domain\Interfaces\ServiceDefinitionInterface;
use CubaDevOps\Flexi\Domain\ValueObjects\ServiceType;
use PHPUnit\Framework\TestCase;

class ServiceTest extends TestCase
{
    private const SERVICE_NAME = 'service-name';

    private ServiceType $type;
    private ServiceDefinitionInterface $definition;

    public function setUp(): void
    {
        $this->type = $this->createMock(ServiceType::class);
        $this->definition = $this->createMock(ServiceDefinitionInterface::class);

        $this->service = new Service(self::SERVICE_NAME, $this->type, $this->definition);
    }

    public function testGetType(): void
    {
        $this->assertEquals($this->type, $this->service->getType());
    }

    public function testGetDefinition(): void
    {
        $this->assertEquals($this->definition, $this->service->getDefinition());
    }

    public function testGetName(): void
    {
        $this->assertEquals(self::SERVICE_NAME, $this->service->getName());
    }

      //TODO: this is not working as expected
//    public function testToString(): void
//    {
//        $expected = '?';
//
//        $result = $this->service->__toString();
//
//        $this->assertEquals($expected, $result);
//    }
}
