<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Repository;

use Symfony\Component\Validator\Constraint;

abstract class AbstractCustomConstraint extends Constraint implements CustomConstraintParameters
{
    /**
     * {@inheritDoc}
     */
    public function getDefaultOption(): ?string
    {
        return $this->defaultParameter();
    }

    /**
     * {@inheritDoc}
     */
    public function defaultParameter(): ?string
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function optionalParameters(): ?array
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function requiredParameters(): ?array
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        $parts = explode('\\', static::class);

        return (string) array_pop($parts);
    }
}
