<?php

namespace CubaDevOps\Flexi\Test\Domain\ValueObjects;

use CubaDevOps\Flexi\Domain\ValueObjects\CollectionType;
use PHPUnit\Framework\TestCase;

class CollectionTypeTest extends TestCase
{
    public function testCreate(): void
    {
        $collectionType = new CollectionType('int');
        $this->assertEquals('int', $collectionType->getValue());
        $this->assertInstanceOf(CollectionType::class, $collectionType);
    }

    public function testInvalidType(): void
    {
        $type = 'invalid type';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("{$type} is not a valid type for collections, valid types are (".implode(',', array_keys(CollectionType::TYPE_ENUMS)).')');
        new CollectionType($type);
    }

    public function testNumericValue(): void
    {
        $collectionType = new CollectionType('numeric');
        $this->assertTrue($collectionType->isValidType(1));
    }

    public function testIntegerValue(): void
    {
        $collectionType = new CollectionType('integer');
        $this->assertTrue($collectionType->isValidType(1));
    }

    public function testFloatValue(): void
    {
        $collectionType = new CollectionType('float');
        $this->assertTrue($collectionType->isValidType(1.1));
    }

    public function testStringValue(): void
    {
        $collectionType = new CollectionType('string');
        $this->assertTrue($collectionType->isValidType('stringValue'));
    }

    public function testBooleanValue(): void
    {
        $collectionType = new CollectionType('boolean');
        $this->assertTrue($collectionType->isValidType(true));
    }

    public function testNullValue(): void
    {
        $collectionType = new CollectionType('null');
        $this->assertTrue($collectionType->isValidType(null));
    }

    public function testArrayValue(): void
    {
        $collectionType = new CollectionType('array');
        $this->assertTrue($collectionType->isValidType([1, 2, 3]));
    }

    public function testObjectValue(): void
    {
        $collectionType = new CollectionType('object');
        $this->assertTrue($collectionType->isValidType(new \stdClass()));
    }

    public function testResourceValue(): void
    {
        $collectionType = new CollectionType('resource');
        $this->assertTrue($collectionType->isValidType(tmpfile()));
    }

    public function testScalarValue(): void
    {
        $collectionType = new CollectionType('scalar');
        $this->assertTrue($collectionType->isValidType(0123));
    }

    public function testCallableValue(): void
    {
        $callable = function() {
            return 'Callable';
        };

        $collectionType = new CollectionType('callable');
        $this->assertTrue($collectionType->isValidType($callable));
    }

    public function testIterableValue(): void
    {
        $collectionType = new CollectionType('iterable');
        $this->assertTrue($collectionType->isValidType(["a", "b", "c"]));
    }
}
