<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Classes;

use CubaDevOps\Flexi\Domain\Exceptions\InvalidArgumentCacheException;
use CubaDevOps\Flexi\Infrastructure\Classes\InMemoryCache;
use Flexi\Contracts\Interfaces\CacheInterface;
use PHPUnit\Framework\TestCase;

class InMemoryCacheTest extends TestCase
{
    private InMemoryCache $cache;

    protected function setUp(): void
    {
        $this->cache = new InMemoryCache();
    }

    public function testImplementsCacheInterface(): void
    {
        $this->assertInstanceOf(CacheInterface::class, $this->cache);
    }

    public function testSetAndGet(): void
    {
        $this->assertTrue($this->cache->set('key1', 'value1'));
        $this->assertEquals('value1', $this->cache->get('key1'));
    }

    public function testGetWithDefault(): void
    {
        $this->assertEquals('default_value', $this->cache->get('nonexistent', 'default_value'));
        $this->assertNull($this->cache->get('nonexistent'));
    }

    public function testSetWithTtl(): void
    {
        // TTL is not implemented in InMemoryCache, but should not fail
        $this->assertTrue($this->cache->set('key_with_ttl', 'value', 3600));
        $this->assertEquals('value', $this->cache->get('key_with_ttl'));
    }

    public function testHas(): void
    {
        $this->cache->set('existing_key', 'value');

        $this->assertTrue($this->cache->has('existing_key'));
        $this->assertFalse($this->cache->has('nonexistent_key'));
    }

    public function testDelete(): void
    {
        $this->cache->set('to_delete', 'value');
        $this->assertTrue($this->cache->has('to_delete'));

        $this->assertTrue($this->cache->delete('to_delete'));
        $this->assertFalse($this->cache->has('to_delete'));

        // Deleting nonexistent key should return false
        $this->assertFalse($this->cache->delete('to_delete'));
    }

    public function testClear(): void
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->cache->set('key3', 'value3');

        $this->assertTrue($this->cache->has('key1'));
        $this->assertTrue($this->cache->has('key2'));
        $this->assertTrue($this->cache->has('key3'));

        $this->assertTrue($this->cache->clear());

        $this->assertFalse($this->cache->has('key1'));
        $this->assertFalse($this->cache->has('key2'));
        $this->assertFalse($this->cache->has('key3'));
    }

    public function testSetMultiple(): void
    {
        $values = [
            'multi1' => 'value1',
            'multi2' => 'value2',
            'multi3' => 'value3',
        ];

        $this->assertTrue($this->cache->setMultiple($values));

        $this->assertEquals('value1', $this->cache->get('multi1'));
        $this->assertEquals('value2', $this->cache->get('multi2'));
        $this->assertEquals('value3', $this->cache->get('multi3'));
    }

    public function testSetMultipleWithTtl(): void
    {
        $values = ['ttl_key1' => 'ttl_value1', 'ttl_key2' => 'ttl_value2'];

        $this->assertTrue($this->cache->setMultiple($values, 3600));

        $this->assertEquals('ttl_value1', $this->cache->get('ttl_key1'));
        $this->assertEquals('ttl_value2', $this->cache->get('ttl_key2'));
    }

    public function testGetMultiple(): void
    {
        $this->cache->set('get_multi1', 'value1');
        $this->cache->set('get_multi2', 'value2');

        $keys = ['get_multi1', 'get_multi2', 'nonexistent'];
        $result = $this->cache->getMultiple($keys, 'default');

        $this->assertEquals(['value1', 'value2', 'default'], $result);
    }

    public function testGetMultipleWithArray(): void
    {
        $this->cache->set('array_key1', 'array_value1');
        $this->cache->set('array_key2', 'array_value2');

        $keys = ['array_key1', 'array_key2'];
        $result = $this->cache->getMultiple($keys);

        $this->assertEquals(['array_value1', 'array_value2'], $result);
    }

    public function testDeleteMultiple(): void
    {
        $this->cache->set('del_multi1', 'value1');
        $this->cache->set('del_multi2', 'value2');
        $this->cache->set('del_multi3', 'value3');

        // Only delete existing keys to ensure true return
        $keys = ['del_multi1', 'del_multi3'];
        $this->assertTrue($this->cache->deleteMultiple($keys));

        $this->assertFalse($this->cache->has('del_multi1'));
        $this->assertTrue($this->cache->has('del_multi2'));
        $this->assertFalse($this->cache->has('del_multi3'));
    }

    public function testDeleteMultipleWithNonexistentKey(): void
    {
        $this->cache->set('existing_key', 'value');

        // Should return false if any key doesn't exist
        $keys = ['existing_key', 'nonexistent_key'];
        $this->assertFalse($this->cache->deleteMultiple($keys));

        // But existing key should still be deleted
        $this->assertFalse($this->cache->has('existing_key'));
    }

    public function testTooLongKeyThrowsException(): void
    {
        $this->expectException(InvalidArgumentCacheException::class);
        $this->expectExceptionMessage('Key must be a string with a maximum length of 100 characters');

        $longKey = str_repeat('a', 101); // 101 characters
        $this->cache->get($longKey);
    }

    public function testMaxLengthKeyIsValid(): void
    {
        $maxKey = str_repeat('a', 100); // Exactly 100 characters

        $this->assertTrue($this->cache->set($maxKey, 'max_value'));
        $this->assertEquals('max_value', $this->cache->get($maxKey));
        $this->assertTrue($this->cache->has($maxKey));
        $this->assertTrue($this->cache->delete($maxKey));
    }

    public function testSetMultipleWithInvalidKey(): void
    {
        $this->expectException(InvalidArgumentCacheException::class);

        $values = [123 => 'value']; // Invalid key in array - numeric keys not allowed
        $this->cache->setMultiple($values);
    }

    public function testDeleteMultipleWithInvalidKey(): void
    {
        $this->expectException(InvalidArgumentCacheException::class);

        $keys = [123, 'valid_key']; // Mixed types in keys array
        $this->cache->deleteMultiple($keys);
    }

    public function testVariousDataTypes(): void
    {
        $testData = [
            'string_key' => 'string_value',
            'int_key' => 42,
            'float_key' => 3.14,
            'array_key' => ['nested' => 'array'],
            'bool_key' => true,
            'null_key' => null,
            'object_key' => (object)['property' => 'value']
        ];

        foreach ($testData as $key => $value) {
            $this->assertTrue($this->cache->set($key, $value));
            $this->assertEquals($value, $this->cache->get($key));
        }
    }

    public function testOverwriteExistingKey(): void
    {
        $this->cache->set('overwrite', 'original');
        $this->assertEquals('original', $this->cache->get('overwrite'));

        $this->cache->set('overwrite', 'updated');
        $this->assertEquals('updated', $this->cache->get('overwrite'));
    }

    public function testEmptyStringKey(): void
    {
        $this->assertTrue($this->cache->set('', 'empty_key_value'));
        $this->assertEquals('empty_key_value', $this->cache->get(''));
        $this->assertTrue($this->cache->has(''));
        $this->assertTrue($this->cache->delete(''));
    }

    /**
     * Test getMultiple with non-iterable values throws exception
     */
    public function testGetMultipleWithNonIterableThrowsException(): void
    {
        $this->expectException(InvalidArgumentCacheException::class);
        $this->expectExceptionMessage('Values must be an iterable');

        // Pass a non-iterable (string) instead of array/iterable
        $this->cache->getMultiple('not_an_iterable');
    }

    /**
     * Test setMultiple with non-iterable values throws exception
     */
    public function testSetMultipleWithNonIterableThrowsException(): void
    {
        $this->expectException(InvalidArgumentCacheException::class);
        $this->expectExceptionMessage('Values must be an iterable');

        // Pass a non-iterable (string) instead of array/iterable
        $this->cache->setMultiple('not_an_iterable');
    }

    /**
     * Test deleteMultiple with non-iterable values throws exception
     */
    public function testDeleteMultipleWithNonIterableThrowsException(): void
    {
        $this->expectException(InvalidArgumentCacheException::class);
        $this->expectExceptionMessage('Values must be an iterable');

        // Pass a non-iterable (integer) instead of array/iterable
        $this->cache->deleteMultiple(123);
    }
}