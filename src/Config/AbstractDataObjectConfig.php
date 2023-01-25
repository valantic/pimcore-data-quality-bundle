<?php

namespace Valantic\DataQualityBundle\Config;

use Pimcore\Model\DataObject\Concrete;

abstract class AbstractDataObjectConfig implements DataObjectConfigInterface
{
    public function getValidationGroups(Concrete $obj): array
    {
        return [];
    }

    public function getIgnoreFallbackLanguage(Concrete $obj): bool
    {
        return false;
    }
}
