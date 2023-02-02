<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\EventListener;

abstract class AbstractListener
{
    protected static bool $isEnabled = true;

    public static function enableListener(): void
    {
        self::$isEnabled = true;
    }

    public static function disableListener(): void
    {
        self::$isEnabled = false;
    }
}
