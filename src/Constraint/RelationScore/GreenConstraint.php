<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Constraint\RelationScore;

class GreenConstraint extends AbstractConstraint
{
    public function validatedBy(): string
    {
        return GreenValidator::class;
    }

    public function getLabel(): string
    {
        return 'GreenRelationScore';
    }
}
