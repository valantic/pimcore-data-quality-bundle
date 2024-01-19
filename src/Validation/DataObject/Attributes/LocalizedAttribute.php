<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation\DataObject\Attributes;

use Pimcore\Model\DataObject;
use Valantic\DataQualityBundle\Event\ConstraintFailureEvent;
use Valantic\DataQualityBundle\Validation\MultiColorableInterface;
use Valantic\DataQualityBundle\Validation\MultiScorableInterface;

class LocalizedAttribute extends AbstractAttribute implements MultiScorableInterface, MultiColorableInterface
{
    protected array $values;

    public function configure(
        DataObject\Concrete $obj,
        string $attribute,
        array $values,
        array $groups,
        array $skippedConstraints,
        array $constraints,
    ): void {
        $this->obj = $obj;
        $this->values = $values;
        $this->attribute = $attribute;
        $this->groups = $groups;
        $this->skippedConstraints = $skippedConstraints;

        $this->setConstrains($constraints);
    }

    public function validate(): void
    {
        try {
            foreach ($this->values as $locale => $value) {
                $this->violations[$locale] = $this->validator->validate($value, $this->constraints, $this->groups);
            }
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

        return $this->score = array_sum($this->scores()) / count($this->violations);
    }

    public function scores(): array
    {
        if ($this->scores !== null) {
            return $this->scores;
        }

        if (count($this->constraints) === 0) {
            return [];
        }

        $scores = [];
        foreach ($this->violations as $locale => $violation) {
            $scores[$locale] = 1 - (count($violation) / count($this->constraints));
        }

        return $this->scores = $scores;
    }

    public function value(): array
    {
        return $this->values;
    }
}
