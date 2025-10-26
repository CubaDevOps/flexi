<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\HealthCheck\Application\UseCase;

use CubaDevOps\Flexi\Contracts\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Contracts\DTOContract;
use CubaDevOps\Flexi\Contracts\HandlerContract;
use CubaDevOps\Flexi\Contracts\MessageContract;
use CubaDevOps\Flexi\Contracts\RepositoryContract;
use CubaDevOps\Flexi\Contracts\ValueObjects\Version;
use CubaDevOps\Flexi\Domain\Criteria\AnyCriteria;

class Health implements HandlerContract
{
    private RepositoryContract $version_repository;

    public function __construct(RepositoryContract $version_repository)
    {
        $this->version_repository = $version_repository;
    }

    /**
     * @return PlainTextMessage
     *
     * @throws \JsonException
     */
    public function handle(DTOContract $dto): MessageContract
    {
        /** @var Version $version */
        $version = $this->version_repository->retrieveValue(
            new AnyCriteria()
        );

        return new PlainTextMessage((string) $version);
    }
}
