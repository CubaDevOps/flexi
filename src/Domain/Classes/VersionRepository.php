<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Classes;

use CubaDevOps\Flexi\Domain\Entities\DummyEntity;
use CubaDevOps\Flexi\Domain\Interfaces\CollectionInterface;
use CubaDevOps\Flexi\Domain\Interfaces\CriteriaInterface;
use CubaDevOps\Flexi\Domain\Interfaces\EntityInterface;
use CubaDevOps\Flexi\Domain\Interfaces\RepositoryInterface;
use CubaDevOps\Flexi\Domain\Interfaces\ValueObjectInterface;
use CubaDevOps\Flexi\Domain\ValueObjects\ID;
use CubaDevOps\Flexi\Domain\ValueObjects\Version;

class VersionRepository implements RepositoryInterface
{
    /**
     * @return mixed
     */
    public function retrieveValue(
        CriteriaInterface $criteria
    ): ValueObjectInterface {
        [$major, $minor, $fix] = explode('.', '1.0.0');

        return new Version($major, $minor, $fix);
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

    public function update(EntityInterface $entity): void
    {
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
