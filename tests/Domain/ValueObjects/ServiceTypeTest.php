<?php

declare(strict_types=1);

namespace Flexi\Test\Domain\ValueObjects;

use Flexi\Domain\ValueObjects\ServiceType;
use Flexi\Contracts\Interfaces\ValueObjectInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ServiceTypeTest extends TestCase
{
    public function testImplementsValueObjectInterface(): void
    {
        $serviceType = new ServiceType(ServiceType::TYPE_CLASS);
        $this->assertInstanceOf(ValueObjectInterface::class, $serviceType);
    }

    /**
     * @dataProvider validTypesProvider
     */
    public function testValidTypes(string $type, string $expectedValue): void
    {
        $serviceType = new ServiceType($type);
        $this->assertEquals($expectedValue, $serviceType->getValue());
    }

    public function validTypesProvider(): array
    {
        return [
            'class type' => [ServiceType::TYPE_CLASS, 'class'],
            'factory type' => [ServiceType::TYPE_FACTORY, 'factory'],
            'alias type' => [ServiceType::TYPE_ALIAS, 'alias'],
            'uppercase class' => ['CLASS', 'CLASS'],
            'uppercase factory' => ['FACTORY', 'FACTORY'],
            'uppercase alias' => ['ALIAS', 'ALIAS'],
            'mixed case class' => ['Class', 'Class'],
            'mixed case factory' => ['Factory', 'Factory'],
            'mixed case alias' => ['Alias', 'Alias'],
        ];
    }

    /**
     * @dataProvider invalidTypesProvider
     */
    public function testInvalidTypeThrowsException(string $invalidType): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "{$invalidType} is not a valid type for services, valid types are (" .
            implode(',', ServiceType::TYPE_ENUMS) . ')'
        );

        new ServiceType($invalidType);
    }

    public function invalidTypesProvider(): array
    {
        return [
            'empty string' => [''],
            'invalid type' => ['invalid'],
            'service' => ['service'],
            'singleton' => ['singleton'],
            'instance' => ['instance'],
            'numeric string' => ['123'],
            'special chars' => ['class!'],
            'with spaces' => ['class '],
            'multiple words' => ['class type'],
        ];
    }

    public function testGetValue(): void
    {
        $serviceType = new ServiceType(ServiceType::TYPE_FACTORY);
        $this->assertEquals(ServiceType::TYPE_FACTORY, $serviceType->getValue());
        $this->assertIsString($serviceType->getValue());
    }

    public function testToString(): void
    {
        $serviceType = new ServiceType(ServiceType::TYPE_CLASS);
        $this->assertEquals('class', (string) $serviceType);
        $this->assertEquals($serviceType->getValue(), (string) $serviceType);
    }

    public function testEqualsWithSameType(): void
    {
        $serviceType1 = new ServiceType(ServiceType::TYPE_CLASS);
        $serviceType2 = new ServiceType(ServiceType::TYPE_CLASS);

        $this->assertTrue($serviceType1->equals($serviceType2));
        $this->assertTrue($serviceType2->equals($serviceType1));
    }

    public function testEqualsWithDifferentType(): void
    {
        $serviceType1 = new ServiceType(ServiceType::TYPE_CLASS);
        $serviceType2 = new ServiceType(ServiceType::TYPE_FACTORY);

        $this->assertFalse($serviceType1->equals($serviceType2));
        $this->assertFalse($serviceType2->equals($serviceType1));
    }

    public function testEqualsWithDifferentClass(): void
    {
        $serviceType = new ServiceType(ServiceType::TYPE_CLASS);
        $otherValueObject = new class implements ValueObjectInterface {
            public function getValue(): string { return 'class'; }
            public function equals(ValueObjectInterface $other): bool { return false; }
            public function __toString(): string { return 'class'; }
        };

        $this->assertFalse($serviceType->equals($otherValueObject));
    }

    public function testEqualsWithSameInstanceReturnsSelf(): void
    {
        $serviceType = new ServiceType(ServiceType::TYPE_ALIAS);
        $this->assertTrue($serviceType->equals($serviceType));
    }

    public function testCaseInsensitiveComparison(): void
    {
        // Construcción es case-insensitive
        $serviceType1 = new ServiceType('class');
        $serviceType2 = new ServiceType('CLASS');
        $serviceType3 = new ServiceType('Class');

        // Pero el valor almacenado conserva el case original
        $this->assertEquals('class', $serviceType1->getValue());
        $this->assertEquals('CLASS', $serviceType2->getValue());
        $this->assertEquals('Class', $serviceType3->getValue());

        // Y no son iguales porque conservan el case original
        $this->assertFalse($serviceType1->equals($serviceType2));
        $this->assertFalse($serviceType1->equals($serviceType3));
        $this->assertFalse($serviceType2->equals($serviceType3));
    }

    public function testTypeConstants(): void
    {
        $this->assertEquals('class', ServiceType::TYPE_CLASS);
        $this->assertEquals('factory', ServiceType::TYPE_FACTORY);
        $this->assertEquals('alias', ServiceType::TYPE_ALIAS);

        $this->assertIsArray(ServiceType::TYPE_ENUMS);
        $this->assertEquals(['class', 'factory', 'alias'], ServiceType::TYPE_ENUMS);
        $this->assertCount(3, ServiceType::TYPE_ENUMS);
    }

    public function testAllConstantTypesAreValid(): void
    {
        // Verificar que todas las constantes de tipo son válidas
        foreach (ServiceType::TYPE_ENUMS as $type) {
            $serviceType = new ServiceType($type);
            $this->assertEquals($type, $serviceType->getValue());
        }
    }

    public function testImmutability(): void
    {
        $serviceType = new ServiceType(ServiceType::TYPE_FACTORY);
        $originalValue = $serviceType->getValue();

        // Intentar "modificar" creando nuevo objeto
        $newServiceType = new ServiceType(ServiceType::TYPE_CLASS);

        // El objeto original no debe cambiar
        $this->assertEquals($originalValue, $serviceType->getValue());
        $this->assertEquals('factory', $serviceType->getValue());
        $this->assertEquals('class', $newServiceType->getValue());
    }
}
