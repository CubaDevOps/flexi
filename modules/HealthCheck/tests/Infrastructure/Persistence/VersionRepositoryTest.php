<?php

namespace CubaDevOps\Flexi\Modules\HealthCheck\Test\Infrastructure\Persistence;

use CubaDevOps\Flexi\Modules\HealthCheck\Infrastructure\Persistence\VersionRepository;
use CubaDevOps\Flexi\Domain\Entities\DummyEntity;
use CubaDevOps\Flexi\Domain\Interfaces\CriteriaInterface;
use CubaDevOps\Flexi\Domain\ValueObjects\ID;
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
        $this->assertIsInt($version->getValue());
    }

    public function testGetID(): void
    {
        $dummyEntity = $this->versionRepository->get(new ID('uuid'));

        $this->assertNotNull($dummyEntity);
        $this->assertInstanceOf(DummyEntity::class, $dummyEntity);
    }
}