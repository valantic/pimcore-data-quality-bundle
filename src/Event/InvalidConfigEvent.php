<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Event;

class InvalidConfigEvent extends Event
{
    final public const NAME = 'valantic.data_quality.invalid_config';

    protected function logMessage(): string
    {
        return 'Your config file appears to be invalid YAML. Check whether the file exists and contains valid YAML.';
    }
}
