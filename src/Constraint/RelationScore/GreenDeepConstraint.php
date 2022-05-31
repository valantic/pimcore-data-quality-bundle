<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Constraint\RelationScore;

class GreenDeepConstraint extends AbstractConstraint
{
    public function validatedBy(): string
    {
        return GreenDeepValidator::class;
    }

    public function getLabel(): string
    {
        return 'GreenDeepRelationScore';
    }
}
