<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Constraint;

interface CustomConstraintParametersInterface
{
    /**
     * The name of the default parameter.
     */
    public function defaultParameter(): ?string;

    /**
     * Optional parameters. Parameter name is the key and the value can be a scalar or an array.
     */
    public function optionalParameters(): ?array;

    /**
     * Required parameters. Parameter name is the key and the value can be a scalar or an array.
     */
    public function requiredParameters(): ?array;

    /**
     * Returns a human-readable name of the constraint.
     */
    public function getLabel(): string;
}
