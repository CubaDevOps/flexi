<?php

namespace CubaDevOps\Flexi\Modules\Home\Domain;

use CubaDevOps\Flexi\Domain\Interfaces\DTOInterface;
use InvalidArgumentException;

class HomePageDTO implements DTOInterface
{
    private string $template;

    public function __construct(string $template)
    {
        $this->template = $template;
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data): DTOInterface
    {
        if (!self::validate($data)) {
            throw new InvalidArgumentException('Invalid data provided for ' . self::class);
        }
        return new self($data['template']);
    }

    /**
     * @param array $data
     * @return bool
     */
    public static function validate(array $data): bool
    {
        return isset($data['template']);
    }

    public function __toString(): string
    {
        return $this->template;
    }

    /**
     * @param string $name
     * @return string
     */
    public function get(string $name): string
    {
        return $this->toArray()[$name];
    }

    /**
     * @return string[]
     *
     * @psalm-return array{route: string}
     */
    public function toArray(): array
    {
        return [
            'template' => $this->template,
        ];
    }
}
