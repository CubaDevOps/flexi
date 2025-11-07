<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Domain\Exceptions;

use CubaDevOps\Flexi\Domain\Exceptions\ServiceNotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;

class ServiceNotFoundExceptionTest extends TestCase
{
    public function testImplementsPsrNotFoundExceptionInterface(): void
    {
        $exception = new ServiceNotFoundException('Service not found');
        $this->assertInstanceOf(NotFoundExceptionInterface::class, $exception);
    }

    public function testInheritsFromException(): void
    {
        $exception = new ServiceNotFoundException('Test message');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testCanBeConstructedWithMessage(): void
    {
        $message = 'Service "DatabaseConnection" not found';
        $exception = new ServiceNotFoundException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testCanBeConstructedWithMessageAndCode(): void
    {
        $message = 'Service not found error';
        $code = 404;
        $exception = new ServiceNotFoundException($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    public function testCanBeConstructedWithMessageCodeAndPrevious(): void
    {
        $previousException = new \InvalidArgumentException('Invalid service name');
        $message = 'Service lookup failed with previous error';
        $code = 500;
        $exception = new ServiceNotFoundException($message, $code, $previousException);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testCanBeThrown(): void
    {
        $message = 'Requested service is not available';

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage($message);

        throw new ServiceNotFoundException($message);
    }

    public function testExceptionFileAndLine(): void
    {
        $exception = new ServiceNotFoundException('Test message');

        $this->assertEquals(__FILE__, $exception->getFile());
        $this->assertIsInt($exception->getLine());
    }

    public function testToString(): void
    {
        $message = 'Service lookup failed';
        $exception = new ServiceNotFoundException($message);

        $stringOutput = (string) $exception;
        $this->assertStringContainsString($message, $stringOutput);
        $this->assertStringContainsString(ServiceNotFoundException::class, $stringOutput);
    }

    /**
     * Test common service not found scenarios
     *
     * @dataProvider serviceNotFoundProvider
     */
    public function testWithVariousServiceNotFoundScenarios(string $serviceName, string $context): void
    {
        $message = sprintf('Service "%s" not found in %s', $serviceName, $context);
        $exception = new ServiceNotFoundException($message);

        $this->assertStringContainsString($serviceName, $exception->getMessage());
        $this->assertStringContainsString($context, $exception->getMessage());
    }

    public function serviceNotFoundProvider(): array
    {
        return [
            'database service' => ['DatabaseConnection', 'container'],
            'logger service' => ['LoggerInterface', 'dependency injection container'],
            'cache service' => ['CacheInterface', 'service registry'],
            'mailer service' => ['MailerService', 'application container'],
            'event dispatcher' => ['EventDispatcher', 'service locator'],
        ];
    }

    public function testDifferentiatesFromContainerException(): void
    {
        // ServiceNotFoundException should be more specific than general container exceptions
        $exception = new ServiceNotFoundException('Specific service not found');

        // It should still implement the PSR interface
        $this->assertInstanceOf(NotFoundExceptionInterface::class, $exception);

        // But it should be a distinct exception type
        $this->assertEquals(ServiceNotFoundException::class, get_class($exception));
    }

    public function testWithEmptyMessage(): void
    {
        $exception = new ServiceNotFoundException('');

        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testWithNullPrevious(): void
    {
        $exception = new ServiceNotFoundException('Test message');

        $this->assertNull($exception->getPrevious());
    }
}