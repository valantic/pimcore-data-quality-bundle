<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation\DataObject\Attributes;

use Pimcore\Model\DataObject\Fieldcollection;
use Throwable;
use Valantic\DataQualityBundle\Event\ConstraintFailureEvent;

class FieldCollectionAttribute extends AbstractAttribute
{
    public function validate(): void
    {
        if (!$this->classInformation->isFieldcollectionAttribute($this->attribute)) {
            return;
        }

        try {
            foreach ($this->value() as $value) {
                $this->violations = array_merge_recursive($this->violations, $this->validator->validate($value, $this->getConstraints()));
            }
        } catch (Throwable $e) {
            $this->eventDispatcher->dispatch(new ConstraintFailureEvent($e, $this->obj->getId(), $this->attribute, $this->violations));
        }
    }

    public function value(): mixed
    {
        [$attribute, $field, $fieldAttribute] = explode('.', $this->attribute, 3);
        $objAttr = $this->obj->get($attribute);
        if (!($objAttr instanceof Fieldcollection)) {
            return [];
        }
        $values = [];
        foreach ($objAttr->getItems() as $i => $item) {
            $values[$i] = $item->get($fieldAttribute);
        }

        return $values;
    }
}
