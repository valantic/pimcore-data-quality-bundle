<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Tests\Repository;

use Valantic\DataQualityBundle\Constraint\AbstractCustomConstraint;

class SampleConstraintFull extends AbstractCustomConstraint
{
    public string $message = 'The string "{{ string }}" is no nonsense.';
    public mixed $expected;
    public mixed $allowed;

    public function validatedBy(): string
    {
        return SampleValidatorFull::class;
    }

    public function defaultParameter(): ?string
    {
        return 'expected';
    }

    public function optionalParameters(): ?array
    {
        return ['allowed' => 'abc'];
    }

    public function requiredParameters(): ?array
    {
        return ['expected' => 'def'];
    }

    public function getLabel(): string
    {
        return 'Non-Sense!';
    }
}
