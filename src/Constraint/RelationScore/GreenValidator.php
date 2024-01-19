<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Constraint\RelationScore;

use Valantic\DataQualityBundle\Validation\ColorableInterface;

class GreenValidator extends AbstractValidator
{
    protected function getThresholdKey(): string
    {
        return ColorableInterface::COLOR_GREEN;
    }

    protected function getConstraint(): string
    {
        return GreenConstraint::class;
    }
}
