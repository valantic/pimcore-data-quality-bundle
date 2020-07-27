<?php

namespace Valantic\DataQualityBundle\Constraints\RelationScore;

use Valantic\DataQualityBundle\Validation\Colorable;

class OrangeDeepValidator extends AbstractDeepValidator
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
        return OrangeDeepConstraint::class;
    }
}
