<?php

declare(strict_types=1);

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
