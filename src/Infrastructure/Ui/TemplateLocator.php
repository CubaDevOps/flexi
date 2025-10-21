<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Ui;

use CubaDevOps\Flexi\Domain\Interfaces\TemplateInterface;
use CubaDevOps\Flexi\Domain\Interfaces\TemplateLocatorInterface;

class TemplateLocator implements TemplateLocatorInterface
{
    /**
     * {@inheritDoc}
     */
    public function locate(string $templatePath): TemplateInterface
    {
        return new Template($templatePath);
    }
}
