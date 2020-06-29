<?php

namespace Valantic\DataQualityBundle\Validation;

trait ColorScoreTrait
{
    /**
     * {@inheritDoc}
     */
    public function color(): string
    {
        return $this->calculateColor($this->score());
    }

    /**
     * Perform the actual calculation of the color.
     *
     * @param float $score
     * @return string
     */
    protected function calculateColor(float $score): string
    {
        $config = $this->metaConfig->getForObject($this->obj);
        $greenThreshold = $config[$this->metaConfig::KEY_THRESHOLD_GREEN];
        $orangeThreshold = $config[$this->metaConfig::KEY_THRESHOLD_ORANGE];

        if ($greenThreshold >= 0 && $score >= $greenThreshold) {
            return self::COLOR_GREEN;
        }

        if ($orangeThreshold >= 0 && $score >= $orangeThreshold) {
            return self::COLOR_ORANGE;
        }

        return self::COLOR_RED;
    }
}
