<?php

namespace Valantic\DataQualityBundle\Validation\DataObject\Attributes;

use Throwable;

class PlainAttribute extends AbstractAttribute
{
    /**
     * {@inheritDoc}
     */
    public function value()
    {
        try {
            return $this->valueInherited($this->obj, null);
        } catch (Throwable $throwable) {
            return null;
        }
    }
}
