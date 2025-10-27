<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\HealthCheck\Infrastructure\Persistence;

use CubaDevOps\Flexi\Contracts\Classes\ObjectCollection;
use CubaDevOps\Flexi\Contracts\Interfaces\CollectionInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\CriteriaInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\EntityInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\RepositoryInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\ValueObjectInterface;
use CubaDevOps\Flexi\Contracts\ValueObjects\ID;
use CubaDevOps\Flexi\Contracts\ValueObjects\Version;
use CubaDevOps\Flexi\Modules\HealthCheck\Domain\Entities\VersionEntity;
use CubaDevOps\Flexi\Infrastructure\Utils\JsonFileReader;

class VersionRepository implements RepositoryInterface
{
    use JsonFileReader;

    /**
     * @return mixed
     *
     * @throws \JsonException
     */
    public function retrieveValue(
        CriteriaInterface $criteria
    ): ValueObjectInterface {
        $version_string = $this->readJsonFile('composer.json')['version'];
        [$major, $minor, $patch] = array_map('intval', explode('.', $version_string));

        return new Version($major, $minor, $patch);
    }

    /**
     * @return VersionEntity
     */
    public function get(ID $id): EntityInterface
    {
        return new VersionEntity();
    }

    public function delete(EntityInterface $entity): void
    {
    }

    /**
     * @return ObjectCollection
     */
    public function getAll(): CollectionInterface
    {
        return new ObjectCollection(__CLASS__);
    }

    /**
     * @return ObjectCollection
     */
    public function search(CriteriaInterface $criteria): CollectionInterface
    {
        return new ObjectCollection(__CLASS__);
    }

    public function findById($id): ?EntityInterface
    {
        return new VersionEntity();
    }

    public function save(EntityInterface $entity): void
    {
    }
}
