<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\ValueObjects;

use CubaDevOps\Flexi\Contracts\Interfaces\ValueObjectInterface;;

class CollectionType implements ValueObjectInterface
{
    public const TYPE_ENUMS = [
        'array' => true,
        'boolean' => true,
        'bool' => true,
        'double' => true,
        'float' => true,
        'integer' => true,
        'int' => true,
        'null' => true,
        'numeric' => true,
        'object' => true,
        'real' => true,
        'resource' => true,
        'resource (closed)' => true,
        'string' => true,
        'scalar' => true,
        'callable' => true,
        'iterable' => true,
    ];
    private string $type;

    public function __construct(string $type)
    {
        if (!$this->isAcceptedType($type)) {
            throw new \RuntimeException("{$type} is not a valid type for collections, valid types are (".implode(',', array_keys(self::TYPE_ENUMS)).')');
        }
        $this->type = $type;
    }

    protected function isAcceptedType(string $type): bool
    {
        return isset(self::TYPE_ENUMS[strtolower($type)]);
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

    /**
     * Evaluates the constraint for parameter $value. Returns true if the
     * constraint is met, false otherwise.
     *
     * @param mixed $value value or object to evaluate
     */
    public function isValidType($value): bool
    {
        switch ($this->type) {
            case 'numeric':
                return is_numeric($value);

            case 'integer':
            case 'int':
                return is_int($value);

            case 'double':
            case 'float':
            case 'real':
                return is_float($value);

            case 'string':
                return is_string($value);

            case 'boolean':
            case 'bool':
                return is_bool($value);

            case 'null':
                return null === $value;

            case 'array':
                return is_array($value);

            case 'object':
                return is_object($value);

            case 'resource':
            case 'resource (closed)':
                $type = gettype($value);

                return 'resource' === $type || 'resource (closed)' === $type;

            case 'scalar':
                return is_scalar($value);

            case 'callable':
                return is_callable($value);

            case 'iterable':
                return is_iterable($value);

            default:
                return false;
        }
    }
}
