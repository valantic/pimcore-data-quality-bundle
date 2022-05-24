<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Config\V1;

interface WriterInterface
{
    /**
     * Ensures the config file exists.
     */
    public function ensureConfigExists(): bool;
}
