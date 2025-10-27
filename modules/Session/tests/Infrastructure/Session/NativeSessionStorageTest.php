<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Session\Test\Infrastructure\Session;

use CubaDevOps\Flexi\Modules\Session\Infrastructure\Session\NativeSessionStorage;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class NativeSessionStorageTest extends TestCase
{
    private const SESSION_OPTIONS = [
        'name' => 'PHPSESSID',
        'cookie_lifetime' => 0,
        'cookie_path' => '/',
        'cookie_domain' => 'texi.local',
        'cookie_secure' => false,
        'cookie_httponly' => true,
        'use_cookies' => true,
        'use_only_cookies' => true,
        'use_strict_mode' => true,
        'sid_length' => 32,
        'sid_bits_per_character' => 5,
    ];

    private const STORAGE_VAR_USER = [
        'key' => 'username',
        'value' => 'John Doe',
    ];

    private const STORAGE_VAR_ROLE = [
        'key' => 'role',
        'value' => 'Moderator',
    ];

    private NativeSessionStorage $nativeSessionStorage;
    private LoggerInterface $logger;

    public function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->nativeSessionStorage = new NativeSessionStorage($this->logger, self::SESSION_OPTIONS);
        $this->nativeSessionStorage->set(self::STORAGE_VAR_USER['key'], self::STORAGE_VAR_USER['value']);
        $this->nativeSessionStorage->offsetSet(self::STORAGE_VAR_ROLE['key'], self::STORAGE_VAR_ROLE['value']);
    }

    public function testGet(): void
    {
        $this->assertEquals(
            self::STORAGE_VAR_USER['value'],
            $this->nativeSessionStorage->get(self::STORAGE_VAR_USER['key'])
        );
        $this->assertEquals(
            self::STORAGE_VAR_ROLE['value'],
            $this->nativeSessionStorage->get(self::STORAGE_VAR_ROLE['key'])
        );
    }

    public function testOffsetGet(): void
    {
        $this->assertEquals(
            self::STORAGE_VAR_USER['value'],
            $this->nativeSessionStorage->offsetGet(self::STORAGE_VAR_USER['key'])
        );
        $this->assertEquals(
            self::STORAGE_VAR_ROLE['value'],
            $this->nativeSessionStorage->offsetGet(self::STORAGE_VAR_ROLE['key'])
        );
    }

    public function testHas(): void
    {
        $this->assertTrue($this->nativeSessionStorage->has(self::STORAGE_VAR_USER['key']));
        $this->assertTrue($this->nativeSessionStorage->has(self::STORAGE_VAR_ROLE['key']));
    }

    public function testOffsetExists(): void
    {
        $this->assertTrue($this->nativeSessionStorage->offsetExists(self::STORAGE_VAR_USER['key']));
        $this->assertTrue($this->nativeSessionStorage->offsetExists(self::STORAGE_VAR_ROLE['key']));
    }

    public function testAll(): void
    {
        $expected = [
            self::STORAGE_VAR_USER['key'] => self::STORAGE_VAR_USER['value'],
            self::STORAGE_VAR_ROLE['key'] => self::STORAGE_VAR_ROLE['value'],
        ];

        $this->assertEquals($expected, $this->nativeSessionStorage->all());
    }

    public function testOffsetUnset(): void
    {
        $this->nativeSessionStorage->offsetUnset(self::STORAGE_VAR_USER['key']);
        $this->assertFalse($this->nativeSessionStorage->offsetExists(self::STORAGE_VAR_USER['key']));
    }

    public function testRemove(): void
    {
        $this->nativeSessionStorage->remove(self::STORAGE_VAR_ROLE['key']);
        $this->assertFalse($this->nativeSessionStorage->has(self::STORAGE_VAR_ROLE['key']));
    }

    public function testClear(): void
    {
        $this->nativeSessionStorage->clear();
        $this->assertEmpty($this->nativeSessionStorage->all());
    }
}
