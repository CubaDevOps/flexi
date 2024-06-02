<?php

namespace CubaDevOps\Flexi\Domain\Classes;

use CubaDevOps\Flexi\Domain\Interfaces\TemplateEngineInterface;

class HtmlRender implements TemplateEngineInterface
{
    public function render(Template $template, $vars = []): string
    {
        $template_content = $this->header(). $template->getContent() . $this->footer();

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

    /**
     * @return string
     */
    public function header(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function footer(): string
    {
        return '';
    }
}
