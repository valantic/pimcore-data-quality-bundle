<?php

namespace Valantic\DataQualityBundle\Validation\DataObject\Attributes;

use Throwable;

class RelationAttribute extends AbstractAttribute
{
    /**
     * {@inheritDoc}
     */
    public function value()
    {
        try {
            $relation = $this->valueInherited($this->obj, null);
        } catch (Throwable $throwable) {
            return [];
        }

        $ids = [];

        foreach ($relation as $item) {
            $ids[] = $item->getId();
        }

        return $ids;
    }
}
