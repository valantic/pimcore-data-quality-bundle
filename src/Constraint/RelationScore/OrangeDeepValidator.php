<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Constraint\RelationScore;

use Valantic\DataQualityBundle\Validation\BaseColorableInterface;

class OrangeDeepValidator extends AbstractDeepValidator
{
    protected function getThresholdKey(): string
    {
        return BaseColorableInterface::COLOR_ORANGE;
    }

    protected function getConstraint(): string
    {
        return OrangeDeepConstraint::class;
    }
}
