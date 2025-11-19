<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Domain\ValueObjects;

use CubaDevOps\Flexi\Domain\ValueObjects\ModuleType;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ModuleTypeTest extends TestCase
{
    public function testStaticConstructorsAndValues(): void
    {
        $this->assertSame('local', ModuleType::local()->getValue());
        $this->assertSame('vendor', ModuleType::vendor()->getValue());
        $this->assertSame('mixed', ModuleType::mixed()->getValue());
    }

    public function testFromStringReturnsExpectedInstances(): void
    {
        $this->assertTrue(ModuleType::local()->equals(ModuleType::fromString('local')));
        $this->assertTrue(ModuleType::vendor()->equals(ModuleType::fromString('vendor')));
        $this->assertTrue(ModuleType::mixed()->equals(ModuleType::fromString('mixed')));
    }

    public function testFromStringWithInvalidValueThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ModuleType::fromString('unsupported');
    }

    public function testDescriptionsCoverAllVariants(): void
    {
        $this->assertSame('Local development module (modules/)', ModuleType::local()->getDescription());
        $this->assertSame('Composer package (vendor/)', ModuleType::vendor()->getDescription());
        $this->assertSame('Installed in both locations (conflict)', ModuleType::mixed()->getDescription());
    }

    public function testUnknownDescriptionFallsBackGracefully(): void
    {
        $reflection = new ReflectionClass(ModuleType::class);
        /** @var ModuleType $type */
        $type = $reflection->newInstanceWithoutConstructor();

        $valueProperty = $reflection->getProperty('value');
        $valueProperty->setAccessible(true);
        $valueProperty->setValue($type, 'legacy');

        $this->assertSame('Unknown', $type->getDescription());
    }

    public function testFlagsForDevelopmentPackagedAndConflict(): void
    {
        $local = ModuleType::local();
        $vendor = ModuleType::vendor();
        $mixed = ModuleType::mixed();

        $this->assertTrue($local->isDevelopment());
        $this->assertFalse($vendor->isDevelopment());
        $this->assertTrue($mixed->isDevelopment());

        $this->assertFalse($local->isPackaged());
        $this->assertTrue($vendor->isPackaged());
        $this->assertTrue($mixed->isPackaged());

        $this->assertFalse($local->hasConflict());
        $this->assertFalse($vendor->hasConflict());
        $this->assertTrue($mixed->hasConflict());
    }

    public function testEqualsAndToString(): void
    {
        $localA = ModuleType::local();
        $localB = ModuleType::local();
        $vendor = ModuleType::vendor();

        $this->assertTrue($localA->equals($localB));
        $this->assertFalse($localA->equals($vendor));
        $this->assertSame('local', (string) $localA);
    }
}
