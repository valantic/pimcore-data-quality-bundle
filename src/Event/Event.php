<?php

namespace Valantic\DataQualityBundle\Event;

use Pimcore;
use Pimcore\Log\Simple;
use Symfony\Contracts\EventDispatcher\Event as BaseEvent;

abstract class Event extends BaseEvent
{
    public const NAME = 'valantic.data_quality.base';

    public function __construct()
    {
        if (Pimcore::getDebugMode() || Pimcore::getDevMode()) {
            Simple::log('dataquality', sprintf("%s: %s", self::NAME, $this->logMessage()));
        }
    }

    /**
     * Message to log when in dev/debug mode.
     * @return string
     */
    abstract protected function logMessage(): string;
}
