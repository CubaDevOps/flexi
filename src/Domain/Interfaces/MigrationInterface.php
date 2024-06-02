<?php

namespace CubaDevOps\Flexi\Domain\Interfaces;

use CubaDevOps\Flexi\Domain\ValueObjects\Version;

interface MigrationInterface
{
    public function version(): Version;

    public function up();

    public function down();
}
