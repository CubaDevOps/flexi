<?php

declare(strict_types=1);

namespace Flexi\Test\Domain\Events;

use Flexi\Domain\Events\RouteNotFoundEvent;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouteNotFoundEventTest extends TestCase
{
    private ServerRequestInterface $request;
    private const REQUESTED_PATH = '/test/path';
    private const FIRED_BY = 'TestRouter';

    protected function setUp(): void
    {
        $this->request = $this->createServerRequestMock();
    }

    private function createServerRequestMock(): ServerRequestInterface
    {
        /** @var ServerRequestInterface */
        return $this->createMock(ServerRequestInterface::class);
    }

    private function createResponseMock(): ResponseInterface
    {
        /** @var ResponseInterface */
        return $this->createMock(ResponseInterface::class);
    }

    public function testConstructor(): void
    {
        $event = new RouteNotFoundEvent($this->request, self::REQUESTED_PATH, self::FIRED_BY);

        $this->assertEquals('core.routeNotFound', $event->getName());
        $this->assertEquals(self::FIRED_BY, $event->firedBy());
        $this->assertSame($this->request, $event->getRequest());
        $this->assertEquals(self::REQUESTED_PATH, $event->getRequestedPath());
        $this->assertFalse($event->hasResponse());
        $this->assertNull($event->getResponse());
    }

    public function testGetRequest(): void
    {
        $event = new RouteNotFoundEvent($this->request, self::REQUESTED_PATH, self::FIRED_BY);

        $this->assertSame($this->request, $event->getRequest());
        $this->assertInstanceOf(ServerRequestInterface::class, $event->getRequest());
    }

    public function testGetRequestedPath(): void
    {
        $event = new RouteNotFoundEvent($this->request, self::REQUESTED_PATH, self::FIRED_BY);

        $this->assertEquals(self::REQUESTED_PATH, $event->getRequestedPath());
        $this->assertIsString($event->getRequestedPath());
    }

    public function testSetAndGetResponse(): void
    {
        $event = new RouteNotFoundEvent($this->request, self::REQUESTED_PATH, self::FIRED_BY);
        $response = $this->createResponseMock();

        $this->assertFalse($event->hasResponse());
        $this->assertNull($event->getResponse());
        $this->assertFalse($event->isPropagationStopped());

        $event->setResponse($response);

        $this->assertTrue($event->hasResponse());
        $this->assertSame($response, $event->getResponse());
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testHasResponseReturnsFalseWhenNoResponse(): void
    {
        $event = new RouteNotFoundEvent($this->request, self::REQUESTED_PATH, self::FIRED_BY);

        $this->assertFalse($event->hasResponse());
    }

    public function testHasResponseReturnsFalseWhenResponseIsNull(): void
    {
        $event = new RouteNotFoundEvent($this->request, self::REQUESTED_PATH, self::FIRED_BY);

        // Manually set response to null via the set method
        $event->set('response', null);

        $this->assertFalse($event->hasResponse());
    }

    public function testSetResponseStopsPropagation(): void
    {
        $event = new RouteNotFoundEvent($this->request, self::REQUESTED_PATH, self::FIRED_BY);
        $response = $this->createResponseMock();

        $this->assertFalse($event->isPropagationStopped());

        $event->setResponse($response);

        $this->assertTrue($event->isPropagationStopped());
    }

    public function testEventDataStructure(): void
    {
        $event = new RouteNotFoundEvent($this->request, self::REQUESTED_PATH, self::FIRED_BY);

        $this->assertEquals($this->request, $event->get('request'));
        $this->assertEquals(self::REQUESTED_PATH, $event->get('requested_path'));
        $this->assertNull($event->get('response'));
    }

    public function testEventDataAfterSettingResponse(): void
    {
        $event = new RouteNotFoundEvent($this->request, self::REQUESTED_PATH, self::FIRED_BY);
        $response = $this->createResponseMock();

        $event->setResponse($response);

        $this->assertSame($response, $event->get('response'));
    }

    public function testInheritsFromEvent(): void
    {
        $event = new RouteNotFoundEvent($this->request, self::REQUESTED_PATH, self::FIRED_BY);

        // Test inherited methods work correctly
        $this->assertEquals('core.routeNotFound', $event->getName());
        $this->assertEquals('core.routeNotFound', (string) $event);
        $this->assertEquals(self::FIRED_BY, $event->firedBy());

        $eventArray = $event->toArray();
        $this->assertEquals('core.routeNotFound', $eventArray['event']);
        $this->assertEquals(self::FIRED_BY, $eventArray['fired_by']);
        $this->assertArrayHasKey('occurred_on', $eventArray);
        $this->assertArrayHasKey('data', $eventArray);
    }

    public function testCustomDataCanBeSet(): void
    {
        $event = new RouteNotFoundEvent($this->request, self::REQUESTED_PATH, self::FIRED_BY);

        $event->set('custom_data', 'custom_value');

        $this->assertTrue($event->has('custom_data'));
        $this->assertEquals('custom_value', $event->get('custom_data'));
    }

    public function testResponseWithDifferentStatusCodes(): void
    {
        $event = new RouteNotFoundEvent($this->request, self::REQUESTED_PATH, self::FIRED_BY);

        // Test with 404 response
        /** @var ResponseInterface|\PHPUnit\Framework\MockObject\MockObject $response404 */
        $response404 = $this->createMock(ResponseInterface::class);
        $response404->method('getStatusCode')->willReturn(404);
        $event->setResponse($response404);
        $this->assertEquals(404, $event->getResponse()->getStatusCode());

        // Test setting another response (simulating multiple handlers)
        /** @var ResponseInterface|\PHPUnit\Framework\MockObject\MockObject $response302 */
        $response302 = $this->createMock(ResponseInterface::class);
        $response302->method('getStatusCode')->willReturn(302);
        $response302->method('getHeaderLine')->with('Location')->willReturn('/fallback');
        $event->setResponse($response302);
        $this->assertEquals(302, $event->getResponse()->getStatusCode());
        $this->assertEquals('/fallback', $event->getResponse()->getHeaderLine('Location'));
    }
}