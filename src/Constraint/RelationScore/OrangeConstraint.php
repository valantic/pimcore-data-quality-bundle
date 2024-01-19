<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Constraint\RelationScore;

class OrangeConstraint extends AbstractConstraint
{
    public function validatedBy(): string
    {
        return OrangeValidator::class;
    }

    public function getLabel(): string
    {
        return 'OrangeRelationScore';
    }
}
