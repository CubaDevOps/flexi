<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Interfaces;

interface HandlerInterface
{
    /**
     * @return void|MessageInterface
     */
    public function handle(DTOInterface $dto);
}
