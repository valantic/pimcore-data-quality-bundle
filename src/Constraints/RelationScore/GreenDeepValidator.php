<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Constraints\RelationScore;

use Valantic\DataQualityBundle\Validation\Colorable;

class GreenDeepValidator extends AbstractDeepValidator
{
    /**
     * {@inheritDoc}
     */
    protected function getThresholdKey(): string
    {
        return Colorable::COLOR_GREEN;
    }

    /**
     * {@inheritDoc}
     */
    protected function getConstraint(): string
    {
        return GreenDeepConstraint::class;
    }
}
