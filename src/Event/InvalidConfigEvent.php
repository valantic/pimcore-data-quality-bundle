<?php

namespace Valantic\DataQualityBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class InvalidConfigEvent extends Event
{
    public const NAME = 'valantic.data_quality.invalid_config';
}
