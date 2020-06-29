<?php

namespace Valantic\DataQualityBundle\Repository;

use Symfony\Component\Validator\Constraint;

abstract class AbstractCustomConstraint extends Constraint implements CustomConstraintParameters
{
    /**
     * {@inheritDoc}
     */
    public function defaultParameter(): ?string
    {
        return $this->getDefaultOption();
    }
}
