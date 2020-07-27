<?php

namespace Valantic\DataQualityBundle\Constraints\RelationScore;

class GreenDeepConstraint extends AbstractConstraint
{
    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return GreenDeepValidator::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return 'GreenDeepRelationScore';
    }
}
