<?php

namespace Valantic\DataQualityBundle\Exception;

use Symfony\Contracts\EventDispatcher\Event;

class InvalidConfigEvent extends Event
{
    public const NAME = 'valantic.data_quality.invalid_config';
}
