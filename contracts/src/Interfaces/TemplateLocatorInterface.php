<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Interfaces;

interface TemplateLocatorInterface
{
    /**
     * Locate and prepare a template from a given path.
     *
     * @param string $templatePath The path to the template file
     *
     * @return TemplateInterface The prepared template instance
     *
     * @throws \InvalidArgumentException If template is not found
     */
    public function locate(string $templatePath): TemplateInterface;
}
