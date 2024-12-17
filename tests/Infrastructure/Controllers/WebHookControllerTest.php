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
            'event_name' => 'test_event',
            'trigger_date' => '2024-12-17T18:00:00Z',
            'data' => ['key' => 'value']
        ];
        $jsonData = json_encode($data);

        $this->streamMock->method('getContents')->willReturn($jsonData);
        $this->requestMock->method('getBody')->willReturn($this->streamMock);

        $this->eventBusMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(Event::class));

        $response = $this->webHookController->handle($this->requestMock);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testHandleValidationError(): void
    {
        $data = ['test' => 'required fields empty'];
        $jsonData = json_encode($data);

        $this->streamMock->method('getContents')->willReturn($jsonData);
        $this->requestMock->method('getBody')->willReturn($this->streamMock);

        $response = $this->webHookController->handle($this->requestMock);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testHandleDispatchError(): void
    {
        $data = [
            'event_name' => 'test_event',
            'trigger_date' => '2024-12-17T18:00:00Z',
            'data' => ['key' => 'value']
        ];
        $jsonData = json_encode($data);

        $this->streamMock->method('getContents')->willReturn($jsonData);
        $this->requestMock->method('getBody')->willReturn($this->streamMock);

        $this->eventBusMock
            ->method('dispatch')
            ->willThrowException(new Exception('Dispatch failed'));

        $response = $this->webHookController->handle($this->requestMock);
        $this->assertEquals(400, $response->getStatusCode());
    }
}
