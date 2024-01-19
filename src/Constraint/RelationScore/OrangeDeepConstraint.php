<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Constraint\RelationScore;

class OrangeDeepConstraint extends AbstractConstraint
{
    public function validatedBy(): string
    {
        return OrangeDeepValidator::class;
    }

    public function getLabel(): string
    {
        return 'OrangeDeepRelationScore';
    }
}
