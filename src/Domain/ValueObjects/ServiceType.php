<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\ValueObjects;

use Flexi\Contracts\Interfaces\ValueObjectInterface;

class ServiceType implements ValueObjectInterface
{
    public const TYPE_CLASS = 'class';
    public const TYPE_FACTORY = 'factory';
    public const TYPE_ALIAS = 'alias';
    public const TYPE_ENUMS = ['class', 'factory', 'alias'];
    private string $type;

    public function __construct(string $type)
    {
        if (!$this->isAcceptedType($type)) {
            throw new \RuntimeException("{$type} is not a valid type for services, valid types are (".implode(',', self::TYPE_ENUMS).')');
        }
        $this->type = $type;
    }

    protected function isAcceptedType(string $type): bool
    {
        return in_array(strtolower($type), self::TYPE_ENUMS, true);
    }

    public function getValue(): string
    {
        return $this->type;
    }

    public function equals(ValueObjectInterface $other): bool
    {
        return $other instanceof self && $this->type === $other->getValue();
    }

    public function __toString(): string
    {
        return $this->type;
    }
}
