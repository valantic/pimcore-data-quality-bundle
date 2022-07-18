<?php

namespace Valantic\DataQualityBundle\Config;

use Pimcore\Model\DataObject\Concrete;

abstract class AbstractDataObjectConfigInterface implements DataObjectConfigInterface
{
    public function getValidationGroups(Concrete $obj): array
    {
        return [self::VALIDATION_GROUP_DEFAULT];
    }
}
