<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Ui;

use CubaDevOps\Flexi\Domain\Interfaces\TemplateEngineInterface;
use CubaDevOps\Flexi\Domain\Interfaces\TemplateInterface;
use CubaDevOps\Flexi\Domain\Interfaces\TemplateLocatorInterface;

class HtmlRender implements TemplateEngineInterface
{
    private TemplateLocatorInterface $template_locator;

    public function __construct(TemplateLocatorInterface $template_locator)
    {
        $this->template_locator = $template_locator;
    }

    /**
     * {@inheritDoc}
     */
    public function render($template, $vars = []): string
    {
        // If a string is provided, locate the template first
        if (is_string($template)) {
            $template = $this->template_locator->locate($template);
        }

        // Now we have a TemplateInterface object
        $template_content = $this->header().$template->getContent().$this->footer();

        return $this->replacePlaceholders($template_content, $vars);
    }

    private function replacePlaceholders(
        string $template_content,
        array $vars
    ): string {
        $placeholders = array_map(
            static fn ($key) => '{{'.$key.'}}',
            array_keys($vars)
        );
        $values = array_values($vars);

        return str_replace($placeholders, $values, $template_content);
    }

    public function header(): string
    {
        return '';
    }

    public function footer(): string
    {
        return '';
    }
}
