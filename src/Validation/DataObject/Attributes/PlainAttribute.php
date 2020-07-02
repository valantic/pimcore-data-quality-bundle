<?php

namespace Valantic\DataQualityBundle\Validation\DataObject\Attributes;

use Pimcore\Model\DataObject\Concrete;
use Throwable;
use Valantic\DataQualityBundle\Event\ConstraintFailureEvent;

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
