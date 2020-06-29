<?php

namespace Valantic\DataQualityBundle\Repository;

use Symfony\Component\Validator\Constraint;

abstract class AbstractCustomConstraint extends Constraint implements CustomConstraintParameters
{
    /**
     * {@inheritDoc}
     */
    public function getDefaultOption(): ?string
    {
        return $this->defaultParameter();
    }
    /**
     * {@inheritDoc}
     */
    public function getRequiredOptions(): array
    {
        return array_keys($this->requiredParameters());
    }
}
