<?php

namespace Valantic\DataQualityBundle\Tests\Repository;

use Valantic\DataQualityBundle\Repository\AbstractCustomConstraint;

class SampleConstraintFull extends AbstractCustomConstraint
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

    /**
     * {@inheritDoc}
     */
    public function defaultParameter(): ?string
    {
        return 'expected';
    }

    /**
     * {@inheritDoc}
     */
    public function optionalParameters(): ?array
    {
        return ['allowed' => 'abc'];
    }

    /**
     * {@inheritDoc}
     */
    public function requiredParameters(): ?array
    {
        return ['expected' => 'def'];
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return 'Non-Sense!';
    }
}
