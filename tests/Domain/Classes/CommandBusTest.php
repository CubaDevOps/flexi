<?php

namespace CubaDevOps\Flexi\Test\Domain\Classes;

use CubaDevOps\Flexi\Application\UseCase\Health;
use CubaDevOps\Flexi\Application\UseCase\ListCommands;
use CubaDevOps\Flexi\Application\UseCase\ListQueries;
use CubaDevOps\Flexi\Domain\Classes\CommandBus;
use CubaDevOps\Flexi\Domain\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Domain\DTO\CommandListDTO;
use CubaDevOps\Flexi\Domain\DTO\DummyDTO;
use CubaDevOps\Flexi\Domain\DTO\EmptyVersionDTO;
use CubaDevOps\Flexi\Domain\DTO\QueryListDTO;
use CubaDevOps\Flexi\Domain\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Domain\Utils\ClassFactory;
use CubaDevOps\Flexi\Modules\Home\Application\RenderHome;
use CubaDevOps\Flexi\Modules\Home\Domain\HomePageDTO;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class CommandBusTest extends TestCase
{
    private CommandBus $commandBus;
    private ContainerInterface $container;
    private EventBusInterface $event_bus;
    private ClassFactory $class_factory;

    /**
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     */
    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->event_bus = $this->createMock(EventBusInterface::class);
        $this->class_factory = $this->createMock(ClassFactory::class);

        $this->commandBus = new CommandBus($this->container, $this->event_bus, $this->class_factory);

        $this->commandBus->loadHandlersFromJsonFile('./tests/TestData/Configurations/commands-bus-test.json');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws \ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public function testExecute(): void
    {
        $handlerMock = $this->createMock(Health::class);

        $this->event_bus
            ->expects($this->exactly(2))
            ->method('notify')->willReturnSelf();

        $this->class_factory
            ->expects($this->once())
            ->method('build')
            ->with($this->container, Health::class)
            ->willReturn($handlerMock);

        $handlerMock
            ->expects($this->once())
            ->method('handle')
            ->willReturn(new PlainTextMessage('message'));

        $message = $this->commandBus->execute(new DummyDTO());

        $this->assertNotNull($message);
        $this->assertInstanceOf(PlainTextMessage::class, $message);
        $this->assertEquals('message', $message->get('body'));
    }

    public function testGetHandler(): void
    {
        $this->assertEquals(Health::class, $this->commandBus->getHandler(DummyDTO::class));
    }

    public function testGetHandlerDoesNotExist(): void
    {
        $testHandler = EmptyVersionDTO::class;
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Not handler found for $testHandler command");
        $this->commandBus->getHandler($testHandler);
    }

    public function testHasHandler(): void
    {
        $this->assertTrue($this->commandBus->hasHandler(DummyDTO::class));
        $this->assertFalse($this->commandBus->hasHandler(EmptyVersionDTO::class));
    }

    public function testGetHandlersDefinitions(): void
    {
        $expectedDefinitions = [
            DummyDTO::class => Health::class,
            'test'          => Health::class,
        ];

        $definitions = $this->commandBus->getHandlersDefinition(true);

        $this->assertNotEmpty($definitions);
        $this->assertEquals($expectedDefinitions, $definitions);
    }
}
