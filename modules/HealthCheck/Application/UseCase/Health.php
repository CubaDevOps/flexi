<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\HealthCheck\Application\UseCase;

use CubaDevOps\Flexi\Contracts\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Contracts\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\HandlerInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\MessageInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\RepositoryInterface;
use CubaDevOps\Flexi\Contracts\ValueObjects\Version;
use CubaDevOps\Flexi\Domain\Criteria\AnyCriteria;

class Health implements HandlerInterface
{
    private RepositoryInterface $version_repository;

    public function __construct(RepositoryInterface $version_repository)
    {
        $this->version_repository = $version_repository;
    }

    /**
     * @return PlainTextMessage
     *
     * @throws \JsonException
     */
    public function handle(DTOInterface $dto): MessageInterface
    {
        /** @var Version $version */
        $version = $this->version_repository->retrieveValue(
            new AnyCriteria()
        );

        return new PlainTextMessage((string) $version);
    }
}
