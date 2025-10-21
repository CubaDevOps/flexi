<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Application\UseCase;

use CubaDevOps\Flexi\Domain\Classes\DummySearchCriteria;
use CubaDevOps\Flexi\Domain\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Infrastructure\Persistence\VersionRepository;
use CubaDevOps\Flexi\Domain\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Domain\Interfaces\HandlerInterface;
use CubaDevOps\Flexi\Domain\Interfaces\MessageInterface;
use CubaDevOps\Flexi\Domain\ValueObjects\Version;
use JsonException;

class Health implements HandlerInterface
{
    private VersionRepository $version_repository;

    public function __construct(VersionRepository $version_repository)
    {
        $this->version_repository = $version_repository;
    }

    /**
     * @return PlainTextMessage
     * @throws JsonException
     */
    public function handle(DTOInterface $dto): MessageInterface
    {
        /** @var Version $version */
        $version = $this->version_repository->retrieveValue(
            new DummySearchCriteria()
        );

        return new PlainTextMessage((string)$version);
    }
}
