<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Classes;

class Template
{
    private string $template_path;
    /**
     * @var array|string
     */
    private $template_name;
    /**
     * @var array|string
     */
    private $template_extension;

    public function __construct(string $template_path)
    {
        $template_path = realpath($template_path);
        $this->assertTemplateExists($template_path);
        $this->template_path = $template_path;
        $this->template_name = pathinfo($template_path, PATHINFO_BASENAME);
        $this->template_extension = pathinfo(
            $template_path,
            PATHINFO_EXTENSION
        );
    }

    public function getContent(): string
    {
        return file_get_contents($this->template_path);
    }

    private function assertTemplateExists(string $template_path): void
    {
        if (!file_exists($template_path)) {
            throw new \InvalidArgumentException("Template file not found: $template_path");
        }
    }

    public function getTemplatePath(): string
    {
        return $this->template_path;
    }

    /**
     * @return array|string
     */
    public function getTemplateName()
    {
        return $this->template_name;
    }

    /**
     * @return array|string
     */
    public function getTemplateExtension()
    {
        return $this->template_extension;
    }
}
