<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Tests\Repository;

use Valantic\DataQualityBundle\Repository\AbstractCustomConstraint;

class SampleConstraintMinimal extends AbstractCustomConstraint
{
    public string  $message = 'The string "{{ string }}" is no nonsense.';

    public mixed  $expected;

    public mixed $allowed;

    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return SampleValidatorFull::class;
    }
}
