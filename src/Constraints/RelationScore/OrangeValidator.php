<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Constraints\RelationScore;

use Valantic\DataQualityBundle\Validation\Colorable;

class OrangeValidator extends AbstractValidator
{
    /**
     * {@inheritDoc}
     */
    protected function getThresholdKey(): string
    {
        return Colorable::COLOR_ORANGE;
    }

    /**
     * {@inheritDoc}
     */
    protected function getConstraint(): string
    {
        return OrangeConstraint::class;
    }
}
