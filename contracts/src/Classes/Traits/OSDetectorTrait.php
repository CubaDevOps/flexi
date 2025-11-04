<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Classes\Traits;

trait OSDetectorTrait
{
    public function isWindows(): bool
    {
        return 'Windows' === PHP_OS_FAMILY;
    }

    public function isUnix(): bool
    {
        return in_array(PHP_OS_FAMILY, ['Linux', 'Darwin', 'BSD', 'Solaris'], true);
    }

    public function isLinux(): bool
    {
        return 'Linux' === PHP_OS_FAMILY;
    }

    public function isMac(): bool
    {
        return 'Darwin' === PHP_OS_FAMILY;
    }

    public function isSolaris(): bool
    {
        return 'Solaris' === PHP_OS_FAMILY;
    }
}
