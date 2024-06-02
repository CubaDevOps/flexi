<?php

namespace CubaDevOps\Flexi\Domain\ValueObjects;

use CubaDevOps\Flexi\Domain\Interfaces\ValueObjectInterface;

class Version implements ValueObjectInterface
{
    private int $major_version;
    private int $minor_version;
    private int $fix_version;

    public function __construct(int $major_version, int $minor_version, int $fix_version)
    {
        $this->major_version = $major_version;
        $this->minor_version = $minor_version;
        $this->fix_version = $fix_version;
    }

    public function __toString(): string
    {
        return "{$this->major_version}.{$this->minor_version}.{$this->fix_version}";
    }

    public function getValue(): int
    {
        return $this->getAbsoluteVersion();
    }

    public function getAbsoluteVersion(): int
    {
        return (int) "$this->major_version$this->minor_version$this->fix_version";
    }
}
