<?php

namespace Valantic\DataQualityBundle\Shared;

trait ClassBasenameTrait
{
    protected static function classBasename(string|object $class): string
    {
        $class = is_object($class) ? $class::class : $class;

        return basename(str_replace('\\', '/', $class));
    }
}
