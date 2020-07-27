<?php

namespace Valantic\DataQualityBundle\Tests\Repository;

use Valantic\DataQualityBundle\Repository\AbstractCustomConstraint;

class SampleConstraintFull extends AbstractCustomConstraint
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
