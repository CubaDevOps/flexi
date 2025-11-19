<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Ui\Cli;

use Flexi\Contracts\Interfaces\BusInterface;
use CubaDevOps\Flexi\Test\TestData\TestDoubles\DummyDTO;
use CubaDevOps\Flexi\Domain\Commands\NotFoundCommand;
use CubaDevOps\Flexi\Infrastructure\Ui\Cli\DTOFactory;
use PHPUnit\Framework\TestCase;

class DTOFactoryTest extends TestCase
{
    public function testFromArray(): void
    {
        $bus = $this->createMock(BusInterface::class);
        $id = BusInterface::class;
        $data = ['test'];

        $dto = new DummyDTO();

        $bus->expects($this->once())
            ->method('hasHandler')
            ->with($id)->willReturn(true);

        $bus->expects($this->once())
            ->method('getDtoClassFromAlias')
            ->with($id)->willReturn(get_class($dto));

        $result = DTOFactory::fromArray($bus, $id, $data);

        $this->assertEquals($dto, $result);
    }

    public function testFromArrayNotFoundCliCommand(): void
    {
        $bus = $this->createMock(BusInterface::class);
        $id = BusInterface::class;
        $data = ['test'];

        $bus->expects($this->once())
            ->method('hasHandler')
            ->with($id)->willReturn(false);

        $command = DTOFactory::fromArray($bus, $id, $data);

        $this->assertInstanceOf(NotFoundCommand::class, $command);
    }
}