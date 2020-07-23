<?php

namespace Valantic\DataQualityBundle\Constraints\RelationScore;

class OrangeConstraint extends AbstractConstraint
{
    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return OrangeValidator::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return 'OrangeRelationScore';
    }
}
