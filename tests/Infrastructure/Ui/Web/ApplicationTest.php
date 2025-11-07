<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Ui\Web;

use CubaDevOps\Flexi\Infrastructure\Ui\Web\Application;
use Flexi\Contracts\Interfaces\ConfigurationInterface;
use CubaDevOps\Flexi\Infrastructure\Factories\RouterFactory;
use CubaDevOps\Flexi\Infrastructure\Http\Router;
use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class ApplicationTest extends TestCase
{
    public function testConstructorAssignsContainer(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $application = new Application($containerMock);

        $this->assertInstanceOf(Application::class, $application);
    }

    public function testApplicationHasRequiredMethods(): void
    {
        $this->assertTrue(method_exists(Application::class, 'run'));
        $this->assertTrue(method_exists(Application::class, '__construct'));
    }

    public function testSendResponseMethodExists(): void
    {
        $reflection = new \ReflectionClass(Application::class);
        $sendResponseMethod = $reflection->getMethod('sendResponse');

        $this->assertTrue($sendResponseMethod->isPrivate());
        $this->assertEquals('sendResponse', $sendResponseMethod->getName());
    }

    public function testHandleMethodExists(): void
    {
        $reflection = new \ReflectionClass(Application::class);
        $handleMethod = $reflection->getMethod('handle');

        $this->assertTrue($handleMethod->isPrivate());
        $this->assertEquals('handle', $handleMethod->getName());
    }

    public function testSendResponseWithMockedResponse(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $application = new Application($containerMock);

        // Create a mock response
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(200);
        $responseMock->method('getReasonPhrase')->willReturn('OK');
        $responseMock->method('getHeaders')->willReturn([
            'Content-Type' => ['application/json'],
            'X-Custom-Header' => ['custom-value']
        ]);

        $streamMock = $this->createMock(Stream::class);
        $responseMock->method('getBody')->willReturn($streamMock);

        // Use reflection to access private method
        $reflection = new \ReflectionClass(Application::class);
        $sendResponseMethod = $reflection->getMethod('sendResponse');
        $sendResponseMethod->setAccessible(true);

        $result = $sendResponseMethod->invoke($application, $responseMock);

        $this->assertSame($streamMock, $result);
    }

    public function testApplicationCanBeInstantiated(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $application = new Application($containerMock);

        $this->assertNotNull($application);
        $this->assertInstanceOf(Application::class, $application);
    }

    public function testApplicationMethodsAreCorrectlyDefined(): void
    {
        $reflection = new \ReflectionClass(Application::class);

        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPublic());

        $runMethod = $reflection->getMethod('run');
        $this->assertTrue($runMethod->isPublic());

        $handleMethod = $reflection->getMethod('handle');
        $this->assertTrue($handleMethod->isPrivate());

        $sendResponseMethod = $reflection->getMethod('sendResponse');
        $this->assertTrue($sendResponseMethod->isPrivate());
    }

    public function testContainerIsRequiredForConstruction(): void
    {
        $this->expectException(\TypeError::class);

        // @phpstan-ignore-next-line - Testing type error
        new Application(null);
    }
}