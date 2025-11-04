<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Cache\Tests\Infrastructure\Cache;

use CubaDevOps\Flexi\Contracts\Interfaces\CacheInterface;
use CubaDevOps\Flexi\Modules\Cache\Domain\Exceptions\InvalidArgumentCacheException;
use CubaDevOps\Flexi\Modules\Cache\Infrastructure\Cache\InMemoryCache;
use PHPUnit\Framework\TestCase;

class InMemoryCacheTest extends TestCase
{
    private CacheInterface $cache;

    public function testDeleteMultiple()
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->cache->deleteMultiple(['key1', 'key2']);

        $this->assertEmpty($this->cache->get('key1'));
        $this->assertEmpty($this->cache->get('key2'));
    }

    public function testDeleteMultipleThrowAnExceptionWithInvalidKey()
    {
        $this->expectException(InvalidArgumentCacheException::class);
        $this->cache->deleteMultiple([1]);
    }

    public function testDelete()
    {
        $this->cache->set('key', 'value');
        $this->cache->delete('key');
        $this->assertEmpty($this->cache->get('key'));
    }

    public function testDeleteThrowAnExceptionWithInvalidKey()
    {
        $this->expectException(InvalidArgumentCacheException::class);
        $this->cache->delete(1);
    }

    public function testClear()
    {
        $this->cache->set('key', 'value');
        $this->cache->clear();
        $this->assertEmpty($this->cache->get('key'));
    }

    public function testSetMultiple()
    {
        $this->cache->setMultiple(['key1' => 'value1', 'key2' => 'value2']);
        $this->assertEquals('value1', $this->cache->get('key1'));
        $this->assertEquals('value2', $this->cache->get('key2'));
    }

    public function testSetMultipleThrowAnExceptionWithInvalidKey()
    {
        $this->expectException(InvalidArgumentCacheException::class);
        $this->cache->setMultiple([1]);
    }

    public function testSetMultipleThrowAnExceptionWithInvalidParam()
    {
        $this->expectException(InvalidArgumentCacheException::class);
        $this->cache->setMultiple(1);
    }

    public function testHas()
    {
        $this->cache->set('key', 'value');
        $this->assertTrue($this->cache->has('key'));
    }

    public function testHasThrowAnExceptionWithInvalidKey()
    {
        $this->expectException(InvalidArgumentCacheException::class);
        $this->cache->has(1);
    }

    public function testGetMultiple()
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->assertEquals(['value1', 'value2'], $this->cache->getMultiple(['key1', 'key2']));
    }

    public function testGetMultipleThrowAnExceptionWithInvalidKey()
    {
        $this->expectException(InvalidArgumentCacheException::class);
        $this->cache->getMultiple([1]);
    }

    public function testGet()
    {
        $this->cache->set('key', 'value');
        $this->assertEquals('value', $this->cache->get('key'));
    }

    public function testGetThrowAnExceptionWithInvalidKey()
    {
        $this->expectException(InvalidArgumentCacheException::class);
        $this->cache->get(1);
    }

    public function testSet()
    {
        $this->cache->set('key', 'value');
        $this->assertEquals('value', $this->cache->get('key'));
    }

    public function testSetThrowAnExceptionWithInvalidKey()
    {
        $this->expectException(InvalidArgumentCacheException::class);
        $this->cache->set(1, 2);
    }

    protected function setUp(): void
    {
        $this->cache = new InMemoryCache();
    }
}
