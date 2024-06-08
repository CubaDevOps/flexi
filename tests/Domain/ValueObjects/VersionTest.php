<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Domain\ValueObjects;

use CubaDevOps\Flexi\Domain\ValueObjects\Version;
use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    private Version $version;

    public function testToStringVersion(): void
    {
        $this->assertEquals('1.0.0', (string)$this->version);
    }

    public function testInvalidVersion(): void
    {
        $this->expectException(\TypeError::class);

        /**
         * @noinspection PhpStrictTypeCheckingInspection
         * @psalm-suppress InvalidArgument
         */
        $this->version = new Version('1', '0', '0');
    }

    public function testGetAbsoluteVersion(): void
    {
        $this->assertEquals(100, $this->version->getAbsoluteVersion());
    }

    protected function setUp(): void
    {
        $this->version = new Version(1, 0, 0);
    }
}
