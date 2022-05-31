<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Constraint;

use Symfony\Component\Validator\Constraint;

abstract class AbstractCustomConstraint extends Constraint implements CustomConstraintParameters
{
    public function getDefaultOption(): ?string
    {
        return $this->defaultParameter();
    }

    public function defaultParameter(): ?string
    {
        return null;
    }

    public function optionalParameters(): ?array
    {
        return null;
    }

    public function requiredParameters(): ?array
    {
        return null;
    }

    public function getLabel(): string
    {
        $parts = explode('\\', static::class);

        return (string) array_pop($parts);
    }
}
