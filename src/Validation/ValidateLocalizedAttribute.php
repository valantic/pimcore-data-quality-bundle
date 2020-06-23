<?php

namespace Valantic\DataQualityBundle\Validation;

use Exception;
use Pimcore\Tool;
use Throwable;

class ValidateLocalizedAttribute extends AbstractValidateAttribute
{
    /**
     * {@inheritDoc}
     */
    public function validate()
    {
        if (!$this->classInformation->isLocalizedAttribute($this->attribute)) {
            return;
        }

        try {
            foreach ($this->getLocales() as $locale) {
                $this->violations[$locale] = $this->validator->validate($this->obj->get($this->attribute, $locale), $this->getConstraints());
            }
        } catch (Exception $e) {
            // TODO: emit event
        }
    }

    /**
     * {@inheritDoc}
     */
    public function score(): float
    {
        if (!count($this->getConstraints())) {
            return 0;
        }

        $scoreSum = 0;

        foreach ($this->getLocales() as $locale) {
            $scoreSum += 1 - (count($this->violations[$locale]) / count($this->getConstraints()));
        }

        return $scoreSum/count($this->getLocales());
    }

    protected function getLocales(): array
    {
        return Tool::getValidLanguages();
    }
}
