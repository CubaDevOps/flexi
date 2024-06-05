<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Interfaces;

use CubaDevOps\Flexi\Domain\ValueObjects\Version;

interface MigrationInterface
{
    public function version(): Version;

    public function up();

    public function down();
}
