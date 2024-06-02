<?php

namespace CubaDevOps\Flexi\Domain\Interfaces;

interface HandlerInterface
{
    /**
     * @param DTOInterface $dto
     * @return void|MessageInterface
     */
    public function handle(DTOInterface $dto);
}
