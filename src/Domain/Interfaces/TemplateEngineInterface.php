<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Interfaces;

use CubaDevOps\Flexi\Infrastructure\Ui\Template;

interface TemplateEngineInterface
{
    public function header(): string;

    public function render(Template $template, $vars = []): string;

    public function footer(): string;
}
