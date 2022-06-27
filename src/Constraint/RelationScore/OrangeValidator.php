<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Constraint\RelationScore;

use Valantic\DataQualityBundle\Validation\BaseColorableInterface;

class OrangeValidator extends AbstractValidator
{
    protected function getThresholdKey(): string
    {
        return BaseColorableInterface::COLOR_ORANGE;
    }

    protected function getConstraint(): string
    {
        return OrangeConstraint::class;
    }
}
