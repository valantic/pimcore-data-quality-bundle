<?php

namespace Valantic\DataQualityBundle\Constraints\RelationScore;

class GreenConstraint extends AbstractConstraint
{
    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return GreenValidator::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return 'GreenRelationScore';
    }
}
