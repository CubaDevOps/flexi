<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts;

interface TemplateLocatorContract
{
    /**
     * Locate and prepare a template from a given path.
     *
     * @param string $templatePath The path to the template file
     * @return TemplateContract The prepared template instance
     * @throws \InvalidArgumentException If template is not found
     */
    public function locate(string $templatePath): TemplateContract;
}