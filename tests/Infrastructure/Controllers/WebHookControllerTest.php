<?php

namespace CubaDevOps\Flexi\Test\Infrastructure\Controllers;

use CubaDevOps\Flexi\Infrastructure\Controllers\WebHookController;
use CubaDevOps\Flexi\Domain\Classes\EventBus;
use CubaDevOps\Flexi\Domain\Classes\Event;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class WebHookControllerTest extends TestCase
{
    private EventBus $eventBusMock;
    private ServerRequestInterface $requestMock;
    private StreamInterface $streamMock;
    private WebHookController $webHookController;

    protected function setUp(): void
    {
        $this->eventBusMock = $this->createMock(EventBus::class);
        $this->requestMock = $this->createMock(ServerRequestInterface::class);
        $this->streamMock = $this->createMock(StreamInterface::class);

        $this->webHookController = new WebHookController($this->eventBusMock);
    }

    public function testHandleSuccess(): void
    {
        $data = [
            'event' => 'test_event',
            'fired_by' => 'tester',
            'data' => ['key' => 'value']
        ];

        $this->mockRequestPayload($data);

        $this->eventBusMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(Event::class));

        $response = $this->webHookController->handle($this->requestMock);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testHandleValidationError(): void
    {
        $data = [
            'event' => '',
            'fired_by' => '',
            'data' => ['key' => 'value']
        ];

        $this->mockRequestPayload($data);

        $response = $this->webHookController->handle($this->requestMock);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testHandleDispatchError(): void
    {
        $data = [
            'event' => 'test_event',
            'fired_by' => 'tester',
            'data' => ['key' => 'value']
        ];

        $this->mockRequestPayload($data);

        $this->eventBusMock
            ->method('dispatch')
            ->willThrowException(new \ReflectionException('Dispatch failed'));

        $response = $this->webHookController->handle($this->requestMock);

        $this->assertEquals(400, $response->getStatusCode());
    }

    private function mockRequestPayload(array $payload): void
    {
        $this->requestMock
            ->method('getAttribute')
            ->with('payload')
            ->willReturn((object)$payload);
    }
}