<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Interfaces;

interface BusInterface
{
    public function register(
        string $identifier,
        string $handler
    ): void;

    public function execute(DTOInterface $dto);

    public function hasHandler(string $identifier): bool;

    public function getHandler(string $identifier): string;

    public function loadHandlersFromJsonFile(string $file): void;

    public function getDtoClassFromAlias(string $id): string;
}
