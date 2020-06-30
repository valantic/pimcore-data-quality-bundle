<?php

namespace Valantic\DataQualityBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Throwable;

class ConstraintFailureEvent extends Event
{
    public const NAME = 'valantic.data_quality.constraint_failure';

    /**
     * @var Throwable
     */
    protected $throwable;

    /**
     * ConstraintFailureEvent constructor.
     * @param Throwable $throwable
     */
    public function __construct(Throwable $throwable)
    {
        $this->throwable = $throwable;
    }


    /**
     * @return Throwable
     */
    public function getThrowable(): Throwable
    {
        return $this->throwable;
    }
}
