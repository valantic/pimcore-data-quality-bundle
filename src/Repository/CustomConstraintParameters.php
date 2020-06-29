<?php

namespace Valantic\DataQualityBundle\Repository;

interface CustomConstraintParameters
{
    /**
     * The name of the default parameter.
     * @return string|null
     */
    public function defaultParameter(): ?string;

    /**
     * Optional parameters. Parameter name is the key and the value can be a scalar or an array.
     * @return array|null
     */
    public function optionalParameters(): ?array;

    /**
     * Required parameters. Parameter name is the key and the value can be a scalar or an array.
     * @return array|null
     */
    public function requiredParameters(): ?array;
}
