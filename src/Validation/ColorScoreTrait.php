<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation;

use Valantic\DataQualityBundle\Enum\ThresholdEnum;

trait ColorScoreTrait
{
    public function color(): string
    {
        return $this->calculateColor($this->score());
    }

    /**
     * Perform the actual calculation of the color.
     */
    protected function calculateColor(float $score): string
    {
        $greenThreshold = $this->configurationRepository->getConfiguredThreshold($this->obj::class, ThresholdEnum::THRESHOLD_GREEN);
        $orangeThreshold = $this->configurationRepository->getConfiguredThreshold($this->obj::class, ThresholdEnum::THRESHOLD_ORANGE);

        if ($greenThreshold >= 0 && $score >= $greenThreshold) {
            return self::COLOR_GREEN;
        }

        if ($orangeThreshold >= 0 && $score >= $orangeThreshold) {
            return self::COLOR_ORANGE;
        }

        return self::COLOR_RED;
    }
}
