<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Constraint\RelationScore;

use Valantic\DataQualityBundle\Validation\BaseColorableInterface;

class GreenValidator extends AbstractValidator
{
    protected function getThresholdKey(): string
    {
        return BaseColorableInterface::COLOR_GREEN;
    }

    protected function getConstraint(): string
    {
        return GreenConstraint::class;
    }
}
