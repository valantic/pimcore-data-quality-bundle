<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation\DataObject\Attributes;

use Pimcore\Model\DataObject\Concrete;
use Throwable;

class RelationAttribute extends AbstractAttribute
{
    public function value(): array
    {
        try {
            $relation = $this->valueInherited($this->obj);
        } catch (Throwable) {
            return [];
        }

        $ids = [];

        if (is_array($relation)) {
            foreach ($relation as $item) {
                $ids[] = $item->getId();
            }
        } elseif ($relation instanceof Concrete) {
            $ids[] = $relation->getId();
        }

        return array_diff($ids, [self::$validationRootObject->getId()]);
    }
}
