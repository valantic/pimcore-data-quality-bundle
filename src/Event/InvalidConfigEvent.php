<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Event;

class InvalidConfigEvent extends Event
{
    public const NAME = 'valantic.data_quality.invalid_config';

    /**
     * {@inheritDoc}
     */
    protected function logMessage(): string
    {
        return 'Your config file appears to be invalid YAML. Check whether the file exists and contains valid YAML.';
    }
}
