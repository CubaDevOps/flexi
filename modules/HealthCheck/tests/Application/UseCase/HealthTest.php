<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\HealthCheck\Test\Application\UseCase;

use CubaDevOps\Flexi\Contracts\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Contracts\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Contracts\ValueObjects\Version;
use CubaDevOps\Flexi\Modules\HealthCheck\Application\UseCase\Health;
use CubaDevOps\Flexi\Modules\HealthCheck\Infrastructure\Persistence\VersionRepository;
use PHPUnit\Framework\TestCase;

class HealthTest extends TestCase
{
    private $versionRepository;
    private $health;

    public function setUp(): void
    {
        $this->versionRepository = $this->createMock(VersionRepository::class);

        $this->health = new Health($this->versionRepository);
    }

    public function testHandleEvent(): void
    {
        $dto = $this->createMock(DTOInterface::class);

        $this->versionRepository->expects($this->once())
            ->method('retrieveValue')
            ->willReturn(new Version(1, 1, 1));

        $message = $this->health->handle($dto);

        $this->assertInstanceOf(PlainTextMessage::class, $message);

        $this->assertEquals('1.1.1', $message->get('body'));
        $this->assertInstanceOf(\DateTimeImmutable::class, $message->get('created_at'));
    }
}
