<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\DependencyInjection;

use Flexi\Contracts\Interfaces\ServiceDefinitionInterface;
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\Service;
use CubaDevOps\Flexi\Domain\ValueObjects\ServiceType;
use PHPUnit\Framework\TestCase;

class ServiceTest extends TestCase
{
    private const SERVICE_NAME = 'service-name';

    private ServiceType $type;
    private $definition;
    private Service $service;

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

    /**
     * @throws \JsonException
     */
    public function testToString(): void
    {
        $result = $this->service->__toString();

        // Should return a valid JSON string
        $this->assertIsString($result);
        $this->assertJson($result);

        // Decode and verify the JSON is a valid object representation
        $decoded = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);

        // The JSON should contain the internal properties of the Service object
        // (private properties are included when using json_encode on an object)
    }

    /**
     * Test that the service can be cast to string implicitly
     * @throws \JsonException
     */
    public function testImplicitStringConversion(): void
    {
        $stringRepresentation = (string) $this->service;

        $this->assertIsString($stringRepresentation);
        $this->assertJson($stringRepresentation);

        // Both explicit and implicit conversions should produce the same result
        $this->assertEquals($this->service->__toString(), $stringRepresentation);
    }
}
