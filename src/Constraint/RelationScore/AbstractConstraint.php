<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Constraint\RelationScore;

use Valantic\DataQualityBundle\Constraint\AbstractCustomConstraint;

abstract class AbstractConstraint extends AbstractCustomConstraint
{
    public string $message = 'The related object score(s) fall below the threshold (IDs: {{ ids }}).';
}
