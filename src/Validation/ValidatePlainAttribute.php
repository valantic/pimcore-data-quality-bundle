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
            $this->violations = $this->validator->validate($this->obj->get($this->attribute), $this->getConstraints());
        } catch (Exception $e) {
            // TODO: emit event
        }
    }
}
