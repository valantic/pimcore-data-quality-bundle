<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Tests\Repository;

use Valantic\DataQualityBundle\Repository\AbstractCustomConstraint;

class SampleConstraintFull extends AbstractCustomConstraint
{
    public string $message = 'The string "{{ string }}" is no nonsense.';

    public mixed $expected;

    public mixed $allowed;

    /**
     * {@inheritDoc}
     */
    public function validatedBy(): string
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
