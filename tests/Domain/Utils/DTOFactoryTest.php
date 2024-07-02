<?php

namespace CubaDevOps\Flexi\Test\Domain\Utils;

use CubaDevOps\Flexi\Domain\DTO\DummyDTO;
use CubaDevOps\Flexi\Domain\DTO\NotFoundCliCommand;
use CubaDevOps\Flexi\Domain\Interfaces\BusInterface;
use CubaDevOps\Flexi\Domain\Utils\DTOFactory;
use PHPUnit\Framework\TestCase;

class DTOFactoryTest extends TestCase
{
    public function testFromArray(): void
    {
        $bus = $this->createMock(BusInterface::class);
        $id   = BusInterface::class;
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
        $id   = BusInterface::class;
        $data = ['test'];

        $bus->expects($this->once())
            ->method('hasHandler')
            ->with($id)->willReturn(false);

        $command = DTOFactory::fromArray($bus, $id, $data);

        $this->assertInstanceOf(NotFoundCliCommand::class, $command);
    }
}
