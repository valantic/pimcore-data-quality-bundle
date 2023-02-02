<?php

namespace Valantic\DataQualityBundle\Service;

use Pimcore\Model\DataObject\Concrete;

class CacheService
{
    public function getTags(Concrete $concrete): array
    {
        return [sprintf('valantic_dataquality_object_%d', $concrete->getId())];
    }
}
