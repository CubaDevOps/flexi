<?php

namespace CubaDevOps\Flexi\Test\Domain\Utils;

use CubaDevOps\Flexi\Domain\Interfaces\BusInterface;
use CubaDevOps\Flexi\Domain\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Domain\Utils\DTOFactory;
use PHPUnit\Framework\TestCase;

class DTOFactoryTest extends TestCase
{
    //TODO: add getDtoFromAlias() to BusInterface
//    public function testFromArray(): void
//    {
//        $bus = $this->createMock(BusInterface::class);
//        $id   = BusInterface::class;
//        $data = ['test'];
//
//        $dto = $this->createMock(DTOInterface::class);
//
//        $bus->expects($this->once())
//            ->method('hasHandler')
//            ->with($id)->willReturn(true);
//
//
//        $bus->expects($this->once())
//            ->method('getDtoFromAlias')
//            ->with($id)->willReturn($dto);
//
//        $result = DTOFactory::fromArray($bus, $id, $data);
//
//        $this->assertEquals($dto, $result);
//    }
}
