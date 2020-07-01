<?php

namespace Valantic\DataQualityBundle\Validation\DataObject\Attributes;

use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Throwable;
use Valantic\DataQualityBundle\Event\ConstraintFailureEvent;

class RelationAttribute extends AbstractAttribute
{
    /**
     * {@inheritDoc}
     */
    public function validate()
    {
        if (!$this->classInformation->isRelationAttribute($this->attribute)) {
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
        /**
         * @var $relation AbstractRelations
         */
        $relation = $this->obj->get($this->attribute);

        if (is_object($relation)) {
            return $relation->getId();
        }

        $ids = [];

        foreach ($relation as $item) {
            $ids[] = $item->getId();
        }

        return $ids;
    }
}
