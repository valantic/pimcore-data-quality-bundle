<?php

namespace Valantic\DataQualityBundle\Validation\DataObject\Attributes;

use Pimcore\Model\DataObject\Fieldcollection;
use Throwable;
use Valantic\DataQualityBundle\Event\ConstraintFailureEvent;

class ValidateFieldCollectionAttribute extends AbstractValidateAttribute
{
    /**
     * {@inheritDoc}
     */
    public function validate()
    {
        if (!$this->classInformation->isFieldcollectionAttribute($this->attribute)) {
            return;
        }

        try {
            foreach ($this->value() as $value) {
                $this->violations = array_merge_recursive($this->violations, $this->validator->validate($value, $this->getConstraints()));
            }
        } catch (Throwable $e) {
            $this->eventDispatcher->dispatch(new ConstraintFailureEvent($e));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function value()
    {
        [$attribute, $field, $fieldAttribute] = explode('.', $this->attribute, 3);
        $objAttr = $this->obj->get($attribute);
        if (!($objAttr instanceof Fieldcollection)) {
            return null;
        }
        $values = [];
        foreach ($objAttr->getItems() as $i => $item) {
            $values[$i] = $item->get($fieldAttribute);
        }

        return $values;
    }
}
