<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\HealthCheck\Test\Infrastructure\Persistence;

use CubaDevOps\Flexi\Contracts\CriteriaContract;
use CubaDevOps\Flexi\Contracts\ValueObjects\ID;
use CubaDevOps\Flexi\Domain\Entities\DummyEntity;
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
        $criteria = $this->createMock(CriteriaContract::class);

        $version = $this->versionRepository->retrieveValue($criteria);

        $this->assertNotNull($version);
        $this->assertIsString($version->getValue());
    }

    public function testGetID(): void
    {
        $dummyEntity = $this->versionRepository->get(new ID('uuid'));

        $this->assertNotNull($dummyEntity);
        $this->assertInstanceOf(DummyEntity::class, $dummyEntity);
    }
}
