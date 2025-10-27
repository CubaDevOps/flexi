<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Interfaces;

interface TemplateInterface
{
    /**
     * Get the content of the template file.
     */
    public function getContent(): string;

    /**
     * Get the absolute path to the template file.
     */
    public function getTemplatePath(): string;

    /**
     * Get the template name (filename with extension).
     */
    public function getTemplateName(): string;

    /**
     * Get the template file extension.
     */
    public function getTemplateExtension(): string;
}
