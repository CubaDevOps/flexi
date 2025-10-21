<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Interfaces;

interface TemplateEngineInterface
{
    public function header(): string;

    /**
     * Render a template with the given variables.
     *
     * @param TemplateInterface|string $template The template object or path to template file
     * @param array $vars Variables to replace in the template
     * @return string The rendered content
     */
    public function render($template, $vars = []): string;

    public function footer(): string;
}
