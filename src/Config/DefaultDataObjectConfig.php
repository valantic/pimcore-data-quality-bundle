<?php

namespace Valantic\DataQualityBundle\Config;

use Pimcore\Model\DataObject\Concrete;

final class DefaultDataObjectConfig extends AbstractDataObjectConfig
{
    public function getClass(): string
    {
        return Concrete::class;
    }

    public static function isDefault(): bool
    {
        return true;
    }
}
