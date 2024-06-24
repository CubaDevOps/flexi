<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Interfaces;

interface MessageInterface extends DTOInterface
{
    public function createdAt(): \DateTimeImmutable;

    //Todo implement different message formats like json, plain text, html, etc.
}
