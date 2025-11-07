<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Domain\Exceptions;

use CubaDevOps\Flexi\Domain\Exceptions\InvalidArgumentCacheException;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException;

class InvalidArgumentCacheExceptionTest extends TestCase
{
    public function testImplementsPsrInvalidArgumentException(): void
    {
        $exception = new InvalidArgumentCacheException('Test message');
        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
    }

    public function testConstructorSetsMessage(): void
    {
        $message = 'Invalid cache key provided';
        $exception = new InvalidArgumentCacheException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testConstructorWithEmptyMessage(): void
    {
        $exception = new InvalidArgumentCacheException('');

        $this->assertEquals('', $exception->getMessage());
    }

    public function testConstructorWithSpecialCharactersInMessage(): void
    {
        $message = 'Invalid key: "test@key#123" contains invalid characters!';
        $exception = new InvalidArgumentCacheException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testExceptionCanBeThrown(): void
    {
        $message = 'Cache key cannot be empty';

        $this->expectException(InvalidArgumentCacheException::class);
        $this->expectExceptionMessage($message);

        throw new InvalidArgumentCacheException($message);
    }

    public function testInheritsFromException(): void
    {
        $exception = new InvalidArgumentCacheException('Test message');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testExceptionCodeDefaultsToZero(): void
    {
        $exception = new InvalidArgumentCacheException('Test message');

        $this->assertEquals(0, $exception->getCode());
    }

    public function testExceptionFileAndLine(): void
    {
        $exception = new InvalidArgumentCacheException('Test message');

        $this->assertEquals(__FILE__, $exception->getFile());
        // Line number may vary due to test execution context
        $this->assertIsInt($exception->getLine());
    }

    /**
     * Test various common cache key validation scenarios
     *
     * @dataProvider invalidCacheKeyProvider
     */
    public function testWithVariousInvalidCacheKeys(string $invalidKey, string $expectedMessagePattern): void
    {
        $message = sprintf('Invalid cache key: %s', $invalidKey);
        $exception = new InvalidArgumentCacheException($message);

        $this->assertStringContainsString($invalidKey, $exception->getMessage());
        $this->assertMatchesRegularExpression($expectedMessagePattern, $exception->getMessage());
    }

    public function invalidCacheKeyProvider(): array
    {
        return [
            'empty key' => ['', '/Invalid cache key:/'],
            'key with spaces' => ['key with spaces', '/Invalid cache key:.*key with spaces/'],
            'key with special chars' => ['key@#$%', '/Invalid cache key:.*key@#\$%/'],
            'null byte in key' => ["key\0null", '/Invalid cache key:.*key.*null/'],
            'very long key' => [str_repeat('a', 256), '/Invalid cache key:.*a+/'],
        ];
    }

    public function testMessageIsReadableFromToString(): void
    {
        $message = 'Cache operation failed';
        $exception = new InvalidArgumentCacheException($message);

        $stringOutput = (string) $exception;
        $this->assertStringContainsString($message, $stringOutput);
        $this->assertStringContainsString(InvalidArgumentCacheException::class, $stringOutput);
    }
}