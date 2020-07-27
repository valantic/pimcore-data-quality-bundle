<?php

namespace Valantic\DataQualityBundle\Tests\Repository;

use Valantic\DataQualityBundle\Repository\AbstractCustomConstraint;

class SampleConstraintMinimal extends AbstractCustomConstraint
{
    /**
     * @var string
     */
    public $message = 'The string "{{ string }}" is no nonsense.';

    /**
     * @var mixed
     */
    public $expected;

    /**
     * @var mixed
     */
    public $allowed;

    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return SampleValidatorFull::class;
    }
}
