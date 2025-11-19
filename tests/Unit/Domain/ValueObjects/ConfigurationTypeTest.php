<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use Flexi\Domain\ValueObjects\ConfigurationType;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class ConfigurationTypeTest extends TestCase
{
    public function testCanCreateValidConfigurationTypes(): void
    {
        $services = ConfigurationType::services();
        $routes = ConfigurationType::routes();
        $commands = ConfigurationType::commands();
        $queries = ConfigurationType::queries();
        $listeners = ConfigurationType::listeners();

        $this->assertEquals('services', $services->value());
        $this->assertEquals('routes', $routes->value());
        $this->assertEquals('commands', $commands->value());
        $this->assertEquals('queries', $queries->value());
        $this->assertEquals('listeners', $listeners->value());
    }

    public function testCanCreateFromValidString(): void
    {
        $type = new ConfigurationType('services');
        $this->assertEquals('services', $type->value());
        $this->assertEquals('services', (string) $type);
    }

    public function testThrowsExceptionForInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid configuration type "invalid". Valid types: services, routes, commands, queries, listeners');

        new ConfigurationType('invalid');
    }

    public function testEqualsComparison(): void
    {
        $type1 = ConfigurationType::services();
        $type2 = ConfigurationType::services();
        $type3 = ConfigurationType::routes();

        $this->assertTrue($type1->equals($type2));
        $this->assertFalse($type1->equals($type3));
    }

    public function testGetAllTypes(): void
    {
        $allTypes = ConfigurationType::getAllTypes();

        $expected = ['services', 'routes', 'commands', 'queries', 'listeners'];
        $this->assertEquals($expected, $allTypes);
    }

    public function testIsValidType(): void
    {
        $this->assertTrue(ConfigurationType::isValidType('services'));
        $this->assertTrue(ConfigurationType::isValidType('routes'));
        $this->assertFalse(ConfigurationType::isValidType('invalid'));
        $this->assertFalse(ConfigurationType::isValidType(''));
    }
}