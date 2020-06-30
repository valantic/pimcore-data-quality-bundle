<?php

namespace Valantic\DataQualityBundle\Validation;

use Throwable;
use Valantic\DataQualityBundle\Event\ConstraintFailureEvent;

class ValidatePlainAttribute extends AbstractValidateAttribute
{
    /**
     * {@inheritDoc}
     */
    public function validate()
    {
        if (!$this->classInformation->isPlainAttribute($this->attribute)) {
            return;
        }

        try {
            $this->violations = $this->validator->validate($this->value(), $this->getConstraints());
        } catch (Throwable $e) {
            $this->eventDispatcher->dispatch(new ConstraintFailureEvent($e));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function value()
    {
        return $this->obj->get($this->attribute);
    }
}
