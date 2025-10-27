<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Ui;

use CubaDevOps\Flexi\Contracts\Interfaces\TemplateInterface;
use CubaDevOps\Flexi\Infrastructure\Utils\FileHandlerTrait;

class Template implements TemplateInterface
{
    use FileHandlerTrait;

    private string $template_path;

    private string $template_name;

    private string $template_extension;

    public function __construct(string $template_path)
    {
        $normalized_path = $this->normalize($template_path);
        $this->assertTemplateExists($normalized_path);
        $this->template_path = $normalized_path;
        $this->template_name = pathinfo($normalized_path, PATHINFO_BASENAME);
        $this->template_extension = pathinfo(
            $normalized_path,
            PATHINFO_EXTENSION
        );
    }

    private function assertTemplateExists(string $template_path): void
    {
        if (!file_exists($template_path)) {
            throw new \InvalidArgumentException("Template file not found: $template_path");
        }
    }

    public function getContent(): string
    {
        return $this->readFromFile($this->template_path);
    }

    /**
     * Get the absolute path to the template file.
     */
    public function getTemplatePath(): string
    {
        return $this->template_path;
    }

    public function getTemplateName(): string
    {
        return $this->template_name;
    }

    public function getTemplateExtension(): string
    {
        return $this->template_extension;
    }
}
