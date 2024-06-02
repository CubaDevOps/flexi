<?php

namespace CubaDevOps\Flexi\Domain\Interfaces;

use CubaDevOps\Flexi\Domain\Classes\Template;

interface TemplateEngineInterface
{
    public function header(): string;
    public function render(Template $template, $vars = []): string;

    public function footer(): string;
}
