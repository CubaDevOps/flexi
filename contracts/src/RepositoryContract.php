<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts;

/**
 * Contract for Repository Pattern
 * Standard interface for data persistence.
 */
interface RepositoryContract
{

    public function retrieveValue(CriteriaContract $criteria): ?ValueObjectContract;


    /**
     * Find entity by ID.
     */
    public function findById($id): ?EntityContract;

    /**
     * Save entity.
     */
    public function save(EntityContract $entity): void;

    /**
     * Delete entity.
     */
    public function delete(EntityContract $entity): void;

    /**
     * Find entities by criteria.
     */
    public function search(CriteriaContract $criteria): CollectionContract;
}
