<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Constraints\RelationScore;

use Valantic\DataQualityBundle\Validation\Colorable;

class GreenValidator extends AbstractValidator
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
        return GreenConstraint::class;
    }
}
