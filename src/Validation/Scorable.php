<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation;

interface Scorable
{
    /**
     * Returns a score between 0 and 1 (inclusive) where 0 is the lowest
     * and 1 is the highest achievable score.
     * The score represents a metric of how many validation constraints passed.
     */
    public function score(): float;
}
