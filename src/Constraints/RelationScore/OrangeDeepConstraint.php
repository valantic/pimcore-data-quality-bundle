<?php

namespace Valantic\DataQualityBundle\Constraints\RelationScore;

class OrangeDeepConstraint extends AbstractConstraint
{
    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return OrangeDeepValidator::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return 'OrangeDeepRelationScore';
    }
}
