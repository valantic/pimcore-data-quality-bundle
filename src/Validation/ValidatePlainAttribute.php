<?php

namespace Valantic\DataQualityBundle\Validation;

use Exception;

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
        } catch (Exception $e) {
            // TODO: emit event
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
