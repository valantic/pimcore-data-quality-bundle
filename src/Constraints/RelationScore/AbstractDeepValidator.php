<?php

namespace Valantic\DataQualityBundle\Constraints\RelationScore;

abstract class AbstractDeepValidator extends AbstractValidator
{
    /**
     * {@inheritDoc}
     */
    protected $skipConstraintOnFurtherValidation = false;
}
