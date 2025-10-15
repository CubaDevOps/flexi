<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Persistence;

use CubaDevOps\Flexi\Domain\Classes\ObjectCollection;
use CubaDevOps\Flexi\Domain\Entities\DummyEntity;
use CubaDevOps\Flexi\Domain\Interfaces\CollectionInterface;
use CubaDevOps\Flexi\Domain\Interfaces\CriteriaInterface;
use CubaDevOps\Flexi\Domain\Interfaces\EntityInterface;
use CubaDevOps\Flexi\Domain\Interfaces\RepositoryInterface;
use CubaDevOps\Flexi\Domain\Interfaces\ValueObjectInterface;
use CubaDevOps\Flexi\Domain\ValueObjects\ID;
use CubaDevOps\Flexi\Domain\ValueObjects\Version;
use CubaDevOps\Flexi\Infrastructure\Utils\JsonFileReader;
use JsonException;

class VersionRepository implements RepositoryInterface
{
    use JsonFileReader;

    /**
     * @return mixed
     * @throws JsonException
     */
    public function retrieveValue(
        CriteriaInterface $criteria
    ): ValueObjectInterface {
        $version_string = $this->readJsonFile('composer.json')['version'];
        [$major, $minor, $patch] = array_map('intval', explode('.', $version_string));
        return new Version($major,$minor,$patch);
    }

    /**
     * @return DummyEntity
     */
    public function get(ID $id): EntityInterface
    {
        return new DummyEntity();
    }

    public function create(EntityInterface $entity): void
    {
    }

    /**
     * @param EntityInterface $entity
     * @return void
     * @throws JsonException
     */
    public function update(EntityInterface $entity): void
    {
        $this->writeJsonFileFromArray('composer.json', $entity->toArray());
    }

    public function delete(ID $entity_id): void
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
}
