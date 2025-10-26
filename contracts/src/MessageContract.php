<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts;

interface MessageContract extends DTOContract
{
    public function createdAt(): \DateTimeImmutable;

    // Todo implement different message formats like json, plain text, html, etc.
}
