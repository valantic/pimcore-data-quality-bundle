<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Constraints\RelationScore;

abstract class AbstractDeepValidator extends AbstractValidator
{
    /**
     * {@inheritDoc}
     */
    protected bool $skipConstraintOnFurtherValidation = false;
}
