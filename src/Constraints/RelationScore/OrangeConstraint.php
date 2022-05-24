<?php

declare(strict_types=1);

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
