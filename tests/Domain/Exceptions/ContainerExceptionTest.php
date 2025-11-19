<?php

declare(strict_types=1);

namespace Flexi\Test\Domain\Exceptions;

use Flexi\Domain\Exceptions\ContainerException;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;

class ContainerExceptionTest extends TestCase
{
    public function testImplementsPsrNotFoundExceptionInterface(): void
    {
        $exception = new ContainerException('Service not found');
        $this->assertInstanceOf(NotFoundExceptionInterface::class, $exception);
    }

    public function testInheritsFromException(): void
    {
        $exception = new ContainerException('Test message');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testCanBeConstructedWithMessage(): void
    {
        $message = 'Service "TestService" not found in container';
        $exception = new ContainerException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testCanBeConstructedWithMessageAndCode(): void
    {
        $message = 'Container error';
        $code = 404;
        $exception = new ContainerException($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    public function testCanBeConstructedWithMessageCodeAndPrevious(): void
    {
        $previousException = new \RuntimeException('Previous exception');
        $message = 'Container error with previous';
        $code = 500;
        $exception = new ContainerException($message, $code, $previousException);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testCanBeThrown(): void
    {
        $message = 'Service not found';

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage($message);

        throw new ContainerException($message);
    }

    public function testExceptionFileAndLine(): void
    {
        $exception = new ContainerException('Test message');

        $this->assertEquals(__FILE__, $exception->getFile());
        // Line number may vary due to test execution context
        $this->assertIsInt($exception->getLine());
    }

    public function testToString(): void
    {
        $message = 'Container operation failed';
        $exception = new ContainerException($message);

        $stringOutput = (string) $exception;
        $this->assertStringContainsString($message, $stringOutput);
        $this->assertStringContainsString(ContainerException::class, $stringOutput);
    }

    /**
     * Test common container error scenarios
     *
     * @dataProvider containerErrorProvider
     */
    public function testWithVariousContainerErrors(string $serviceName, string $errorType): void
    {
        $message = sprintf('%s: "%s"', $errorType, $serviceName);
        $exception = new ContainerException($message);

        $this->assertStringContainsString($serviceName, $exception->getMessage());
        $this->assertStringContainsString($errorType, $exception->getMessage());
    }

    public function containerErrorProvider(): array
    {
        return [
            'service not found' => ['UserService', 'Service not found'],
            'circular dependency' => ['PaymentService', 'Circular dependency detected'],
            'invalid service definition' => ['LoggerService', 'Invalid service definition'],
            'missing parameter' => ['DatabaseConnection', 'Missing required parameter'],
            'instantiation failed' => ['EmailService', 'Cannot instantiate service'],
        ];
    }
}