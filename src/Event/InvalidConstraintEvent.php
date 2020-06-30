<?php

namespace Valantic\DataQualityBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Throwable;

class InvalidConstraintEvent extends Event
{
    public const NAME = 'valantic.data_quality.invalid_constraint';

    /**
     * @var
     */
    protected $name;

    /**
     * @var
     */
    protected $params;

    /**
     * @var Throwable
     */
    protected $throwable;

    /**
     * InvalidConstraintEvent constructor.
     * @param Throwable $throwable
     * @param $name
     * @param $params
     */
    public function __construct(Throwable $throwable, $name, $params)
    {
        $this->name = $name;
        $this->params = $params;
        $this->throwable = $throwable;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return Throwable
     */
    public function getThrowable(): Throwable
    {
        return $this->throwable;
    }
}
