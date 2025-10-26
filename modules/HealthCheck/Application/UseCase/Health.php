<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\HealthCheck\Application\UseCase;

use CubaDevOps\Flexi\Domain\Classes\DummySearchCriteria;
use CubaDevOps\Flexi\Domain\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Modules\HealthCheck\Infrastructure\Persistence\VersionRepository;
use CubaDevOps\Flexi\Contracts\DTOContract;
use CubaDevOps\Flexi\Contracts\HandlerContract;
use CubaDevOps\Flexi\Contracts\MessageContract;
use CubaDevOps\Flexi\Domain\ValueObjects\Version;
use JsonException;

class Health implements HandlerContract
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
    public function handle(DTOContract $dto): MessageContract
    {
        /** @var Version $version */
        $version = $this->version_repository->retrieveValue(
            new DummySearchCriteria()
        );

        return new PlainTextMessage((string)$version);
    }
}