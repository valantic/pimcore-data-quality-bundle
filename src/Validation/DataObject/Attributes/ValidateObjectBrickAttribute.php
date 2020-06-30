<?php

namespace Valantic\DataQualityBundle\Validation\DataObject\Attributes;

use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;
use Throwable;
use Valantic\DataQualityBundle\Event\ConstraintFailureEvent;

class ValidateObjectBrickAttribute extends AbstractValidateAttribute
{
    /**
     * {@inheritDoc}
     */
    public function validate()
    {
        if (!$this->classInformation->isObjectbrickAttribute($this->attribute)) {
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
        [$attribute, $brick, $brickAttribute] = explode('.', $this->attribute, 3);
        $objAttr = $this->obj->get($attribute);
        if (!($objAttr instanceof Objectbrick)) {
            return null;
        }
        $brickAttr = $objAttr->{'get' . ucfirst($brick)}();

        if (!($brickAttr instanceof AbstractData)) {
            return null;
        }

        return $brickAttr->{'get' . ucfirst($brickAttribute)}();
    }
}
