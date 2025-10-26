<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Ui;

use CubaDevOps\Flexi\Contracts\TemplateContract;
use CubaDevOps\Flexi\Contracts\TemplateLocatorContract;

class TemplateLocator implements TemplateLocatorContract
{
    /**
     * {@inheritDoc}
     */
    public function locate(string $templatePath): TemplateContract
    {
        return new Template($templatePath);
    }
}
