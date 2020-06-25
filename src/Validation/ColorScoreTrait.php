<?php

namespace Valantic\DataQualityBundle\Validation;

trait ColorScoreTrait
{
    /**
     * {@inheritDoc}
     */
    public function color(): string
    {
        if ($this->score() >= $this->metaConfig->getForObject($this->obj)[$this->metaConfig::KEY_THRESHOLD_GREEN]) {
            return self::COLOR_GREEN;
        }
        if ($this->score() >= $this->metaConfig->getForObject($this->obj)[$this->metaConfig::KEY_THRESHOLD_ORANGE]) {
            return self::COLOR_ORANGE;
        }

        return self::COLOR_RED;
    }
}
