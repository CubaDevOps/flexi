<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\HealthCheck\Infrastructure\Persistence;

use CubaDevOps\Flexi\Contracts\Classes\ObjectCollection;
use CubaDevOps\Flexi\Contracts\CollectionContract;
use CubaDevOps\Flexi\Contracts\CriteriaContract;
use CubaDevOps\Flexi\Contracts\EntityContract;
use CubaDevOps\Flexi\Contracts\RepositoryContract;
use CubaDevOps\Flexi\Contracts\ValueObjectContract;
use CubaDevOps\Flexi\Contracts\ValueObjects\ID;
use CubaDevOps\Flexi\Contracts\ValueObjects\Version;
use CubaDevOps\Flexi\Domain\Entities\DummyEntity;
use CubaDevOps\Flexi\Infrastructure\Utils\JsonFileReader;

class VersionRepository implements RepositoryContract
{
    use JsonFileReader;

    /**
     * @return mixed
     *
     * @throws \JsonException
     */
    public function retrieveValue(
        CriteriaContract $criteria
    ): ValueObjectContract {
        $version_string = $this->readJsonFile('composer.json')['version'];
        [$major, $minor, $patch] = array_map('intval', explode('.', $version_string));

        return new Version($major, $minor, $patch);
    }

    /**
     * @return DummyEntity
     */
    public function get(ID $id): EntityContract
    {
        return new DummyEntity();
    }

    public function create(EntityContract $entity): void
    {
    }

    /**
     * @throws \JsonException
     */
    public function update(EntityContract $entity): void
    {
        $this->writeJsonFileFromArray('composer.json', $entity->toArray());
    }

    public function delete($entity): void
    {
    }

    /**
     * @return ObjectCollection
     */
    public function getAll(): CollectionContract
    {
        return new ObjectCollection(__CLASS__);
    }

    /**
     * @return ObjectCollection
     */
    public function search(CriteriaContract $criteria): CollectionContract
    {
        return new ObjectCollection(__CLASS__);
    }

    public function findByCriteria(array $criteria): array
    {
        return [];
    }

    public function findById($id)
    {
        return new DummyEntity();
    }

    public function save($entity): void
    {
    }
}
