<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\HealthCheck\Test\Infrastructure\Persistence;

use CubaDevOps\Flexi\Contracts\Interfaces\CriteriaInterface;
use CubaDevOps\Flexi\Contracts\ValueObjects\ID;
use CubaDevOps\Flexi\Modules\HealthCheck\Domain\Entities\VersionEntity;
use CubaDevOps\Flexi\Modules\HealthCheck\Infrastructure\Persistence\VersionRepository;
use PHPUnit\Framework\TestCase;

class VersionRepositoryTest extends TestCase
{
    private VersionRepository $versionRepository;

    public function setUp(): void
    {
        $this->versionRepository = new VersionRepository();
    }

    public function testRetrieveValue(): void
    {
        $criteria = $this->createMock(CriteriaInterface::class);

        $version = $this->versionRepository->retrieveValue($criteria);

        $this->assertNotNull($version);
        $this->assertIsString($version->getValue());
    }

    public function testGetID(): void
    {
        $versionEntity = $this->versionRepository->get(new ID('uuid'));

        $this->assertNotNull($versionEntity);
        $this->assertInstanceOf(VersionEntity::class, $versionEntity);
    }
}
