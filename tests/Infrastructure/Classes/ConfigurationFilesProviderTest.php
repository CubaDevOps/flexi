<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Tests\Infrastructure\Classes;

use CubaDevOps\Flexi\Domain\ValueObjects\ConfigurationType;
use PHPUnit\Framework\TestCase;

class ConfigurationFilesProviderTest extends TestCase
{
    public function testValueObjectIntegrationWithConfigurationFilesProvider(): void
    {
        // Test que el Value Object funciona correctamente con el provider
        $servicesType = ConfigurationType::services();
        $routesType = ConfigurationType::routes();
        $commandsType = ConfigurationType::commands();
        $queriesType = ConfigurationType::queries();
        $listenersType = ConfigurationType::listeners();

        $this->assertEquals('services', $servicesType->value());
        $this->assertEquals('routes', $routesType->value());
        $this->assertEquals('commands', $commandsType->value());
        $this->assertEquals('queries', $queriesType->value());
        $this->assertEquals('listeners', $listenersType->value());

        // Verificar que los tipos son diferentes entre sí
        $this->assertFalse($servicesType->equals($routesType));
        $this->assertFalse($routesType->equals($commandsType));
        $this->assertTrue($servicesType->equals(ConfigurationType::services()));
    }

    public function testAllConfigurationTypesAreSupported(): void
    {
        $supportedTypes = ConfigurationType::getAllTypes();

        $expectedTypes = ['services', 'routes', 'commands', 'queries', 'listeners'];

        $this->assertEquals($expectedTypes, $supportedTypes);

        foreach ($expectedTypes as $type) {
            $this->assertTrue(ConfigurationType::isValidType($type));
        }

        $this->assertFalse(ConfigurationType::isValidType('invalid_type'));
    }
}