<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Event;

use Symfony\Contracts\EventDispatcher\Event as BaseEvent;

abstract class Event extends BaseEvent
{
    /**
     * Message to log when in dev/debug mode.
     */
    abstract protected function logMessage(): string;
}
