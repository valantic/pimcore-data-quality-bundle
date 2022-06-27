<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Constraint\RelationScore;

use Valantic\DataQualityBundle\Validation\BaseColorableInterface;

class GreenDeepValidator extends AbstractDeepValidator
{
    protected function getThresholdKey(): string
    {
        return BaseColorableInterface::COLOR_GREEN;
    }

    protected function getConstraint(): string
    {
        return GreenDeepConstraint::class;
    }
}
