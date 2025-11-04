<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\ValueObjects;

use CubaDevOps\Flexi\Contracts\Interfaces\ValueObjectInterface;;

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

    public function getValue(): string
    {
        return $this->__toString();
    }

    public function getAbsoluteVersion(): int
    {
        return (int) "$this->major_version$this->minor_version$this->fix_version";
    }

    public function equals(ValueObjectInterface $other): bool
    {
        return $other instanceof self
            && $this->major_version === $other->major_version
            && $this->minor_version === $other->minor_version
            && $this->fix_version === $other->fix_version;
    }
}
