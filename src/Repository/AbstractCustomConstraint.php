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
     * Needs to return an empty array as otherwise it'll be instantiated in ConstraintDefinitions, causing an exception.
     * @return array
     */
    public function getRequiredOptions(): array
    {
        return [];
    }
}
