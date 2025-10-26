<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts;

interface TemplateEngineContract
{
    /**
     * Render a template with the given variables.
     *
     * @param TemplateContract|string $template The template object or path to template file
     * @param array $vars Variables to replace in the template
     * @return string The rendered content
     */
    public function render($template, $vars = []): string;
}