<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Constraint\RelationScore;

abstract class AbstractDeepValidator extends AbstractValidator
{
    protected bool $skipConstraintOnFurtherValidation = false;
}
