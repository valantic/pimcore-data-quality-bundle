<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation\DataObject\Attributes;

use Pimcore\Model\DataObject;
use Valantic\DataQualityBundle\Event\ConstraintFailureEvent;

class Attribute extends AbstractAttribute
{
    protected mixed $value;

    public function configure(
        DataObject\Concrete $obj,
        string $attribute,
        array $values,
        array $groups,
        array $skippedConstraints,
        array $constraints,
    ): void {
        $this->obj = $obj;
        $this->value = reset($values);
        $this->attribute = $attribute;
        $this->groups = $groups;
        $this->skippedConstraints = $skippedConstraints;

        $this->setConstrains($constraints);
    }

    public function value(): mixed
    {
        return $this->value;
    }

    public function validate(): void
    {
        try {
            $this->violations[] = $this->validator->validate($this->value, $this->constraints, $this->groups);
        } catch (\Throwable $e) {
            $this->eventDispatcher->dispatch(new ConstraintFailureEvent($e, $this->obj->getId(), $this->attribute, $this->violations));
        }
    }

    public function score(): float
    {
        if ($this->score !== null) {
            return $this->score;
        }

        if (count($this->constraints) === 0 || count($this->violations) === 0) {
            return 0;
        }

        $violation = $this->violations[0];

        return $this->score = (1 - (count($violation) / count($this->constraints)));
    }
}
