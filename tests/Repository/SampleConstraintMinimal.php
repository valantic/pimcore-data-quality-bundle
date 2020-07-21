<?php

namespace Valantic\DataQualityBundle\Tests\Repository;

use Valantic\DataQualityBundle\Repository\AbstractCustomConstraint;

class SampleConstraintMinimal extends AbstractCustomConstraint
{
    public $message = 'The string "{{ string }}" is no nonsense.';

    public $expected;

    public $allowed;

    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return SampleValidatorFull::class;
    }
}
