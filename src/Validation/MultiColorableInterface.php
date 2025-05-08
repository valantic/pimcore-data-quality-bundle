<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation;

interface MultiColorableInterface extends BaseColorableInterface
{
    /**
     * Returns an array of colors.
     * The color represents a metric of how many validation constraints passed.
     *
     * @return string[]
     */
    public function colors(): array;
}
