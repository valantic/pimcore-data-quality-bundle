<?php

namespace Valantic\DataQualityBundle\Event;

use Throwable;

class ConstraintFailureEvent extends Event
{
    public const NAME = 'valantic.data_quality.constraint_failure';

    /**
     * @var Throwable
     */
    protected $throwable;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $attribute;

    /**
     * @var array
     */
    protected $violations;

    /**
     * ConstraintFailureEvent constructor.
     * @param Throwable $throwable
     * @param int $id
     * @param string $attribute
     * @param array $violations
     */
    public function __construct(Throwable $throwable, int $id, string $attribute, array $violations)
    {
        $this->throwable = $throwable;
        $this->id = $id;
        $this->attribute = $attribute;
        $this->violations = $violations;

        parent::__construct();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAttribute(): string
    {
        return $this->attribute;
    }

    /**
     * @return array
     */
    public function getViolations(): array
    {
        return $this->violations;
    }

    /**
     * @return Throwable
     */
    public function getThrowable(): Throwable
    {
        return $this->throwable;
    }

    /**
     * {@inheritDoc}
     */
    protected function logMessage(): string
    {
        return sprintf("Constraint(s) on ID %d, attribute %s failed (%s).\nMessage: %s\nTrace: %s", $this->getId(), $this->getAttribute(), json_encode($this->getViolations()), $this->getThrowable()->getMessage(), $this->getThrowable()->getTraceAsString());
    }
}
