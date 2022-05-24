<?php

declare(strict_types=1);

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
