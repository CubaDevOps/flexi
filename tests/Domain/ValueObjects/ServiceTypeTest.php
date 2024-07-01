<?php

namespace CubaDevOps\Flexi\Test\Domain\ValueObjects;

use CubaDevOps\Flexi\Domain\ValueObjects\ServiceType;
use PHPUnit\Framework\TestCase;

class ServiceTypeTest extends TestCase
{
    public function testInvalidType(): void
    {
        $type = 'invalid type';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("{$type} is not a valid type for services, valid types are (".implode(',', ServiceType::TYPE_ENUMS).')');
        new ServiceType($type);
    }

    public function testGetValue(): void
    {
        $serviceType = new ServiceType(ServiceType::TYPE_FACTORY);
        $this->assertEquals(ServiceType::TYPE_FACTORY, $serviceType->getValue());
    }
}
