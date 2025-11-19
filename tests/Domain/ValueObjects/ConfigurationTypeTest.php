<?php

declare(strict_types=1);

namespace Flexi\Tests\Domain\ValueObjects;

use Flexi\Domain\ValueObjects\ConfigurationType;
use PHPUnit\Framework\TestCase;

class ConfigurationTypeTest extends TestCase
{
    public function testCanCreateServicesType(): void
    {
        $type = ConfigurationType::services();

        $this->assertEquals('services', $type->value());
        $this->assertEquals('services', (string) $type);
    }

    public function testCanCreateRoutesType(): void
    {
        $type = ConfigurationType::routes();

        $this->assertEquals('routes', $type->value());
    }

    public function testCanCreateCommandsType(): void
    {
        $type = ConfigurationType::commands();

        $this->assertEquals('commands', $type->value());
    }

    public function testCanCreateQueriesType(): void
    {
        $type = ConfigurationType::queries();

        $this->assertEquals('queries', $type->value());
    }

    public function testCanCreateListenersType(): void
    {
        $type = ConfigurationType::listeners();

        $this->assertEquals('listeners', $type->value());
    }

    public function testCanCreateWithConstructor(): void
    {
        $type = new ConfigurationType('services');

        $this->assertEquals('services', $type->value());
    }

    public function testThrowsExceptionForInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid configuration type "invalid"');

        new ConfigurationType('invalid');
    }

    public function testGetAllTypes(): void
    {
        $expected = ['services', 'routes', 'commands', 'queries', 'listeners'];

        $this->assertEquals($expected, ConfigurationType::getAllTypes());
    }

    public function testIsValidType(): void
    {
        $this->assertTrue(ConfigurationType::isValidType('services'));
        $this->assertTrue(ConfigurationType::isValidType('routes'));
        $this->assertFalse(ConfigurationType::isValidType('invalid'));
    }

    public function testEquals(): void
    {
        $type1 = ConfigurationType::services();
        $type2 = ConfigurationType::services();
        $type3 = ConfigurationType::routes();

        $this->assertTrue($type1->equals($type2));
        $this->assertFalse($type1->equals($type3));
    }
}