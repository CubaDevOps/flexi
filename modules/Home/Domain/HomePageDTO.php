<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Home\Domain;

use CubaDevOps\Flexi\Domain\Interfaces\DTOInterface;

class HomePageDTO implements DTOInterface
{
    private string $template;

    public function __construct(string $template)
    {
        $this->template = $template;
    }

    public static function fromArray(array $data): DTOInterface
    {
        if (!self::validate($data)) {
            throw new \InvalidArgumentException('Invalid data provided for '.self::class);
        }

        return new self($data['template']);
    }

    public static function validate(array $data): bool
    {
        return isset($data['template']);
    }

    public function __toString(): string
    {
        return $this->template;
    }

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
