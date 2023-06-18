<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation;

use Valantic\DataQualityBundle\Enum\ThresholdEnum;

trait ColorScoreTrait
{
    private array $greenThreshold = [];
    private array $orangeThreshold = [];

    public function color(): string
    {
        return $this->calculateColor($this->score());
    }

    public function colors(): array
    {
        $colors = [];

        if ($this instanceof MultiScorableInterface) {
            foreach ($this->scores() as $language => $score) {
                $colors[$language] = $this->calculateColor($score);
            }
        }

        return $colors;
    }

    /**
     * Perform the actual calculation of the color.
     */
    protected function calculateColor(float $score): string
    {
        $greenThreshold = $this->getGreenThreshold();
        $orangeThreshold = $this->getOrangeThreshold();

        if ($greenThreshold >= 0 && $score >= $greenThreshold) {
            return self::COLOR_GREEN;
        }

        if ($orangeThreshold >= 0 && $score >= $orangeThreshold) {
            return self::COLOR_ORANGE;
        }

        return self::COLOR_RED;
    }

    private function getGreenThreshold(): float
    {
        if (isset($this->greenThreshold[$this->obj::class])) {
            return $this->greenThreshold[$this->obj::class];
        }

        return $this->greenThreshold[$this->obj::class] = $this->configurationRepository->getConfiguredThreshold(
            $this->obj::class,
            ThresholdEnum::green()
        );
    }

    private function getOrangeThreshold(): float
    {
        if (isset($this->orangeThreshold[$this->obj::class])) {
            return $this->orangeThreshold[$this->obj::class];
        }

        return $this->orangeThreshold[$this->obj::class] = $this->configurationRepository->getConfiguredThreshold(
            $this->obj::class,
            ThresholdEnum::orange()
        );
    }
}
