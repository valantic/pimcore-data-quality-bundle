<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Event;

use Throwable;

use const JSON_THROW_ON_ERROR;

class ConstraintFailureEvent extends Event
{
    public const NAME = 'valantic.data_quality.constraint_failure';

    /**
     * ConstraintFailureEvent constructor.
     */
    public function __construct(
        protected Throwable $throwable,
        protected int|null $id,
        protected string $attribute,
        protected array $violations,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAttribute(): string
    {
        return $this->attribute;
    }

    public function getViolations(): array
    {
        return $this->violations;
    }

    public function getThrowable(): Throwable
    {
        return $this->throwable;
    }

    protected function logMessage(): string
    {
        return sprintf(
            "Constraint(s) on ID %d, attribute %s failed (%s).\nMessage: %s\nTrace: %s",
            $this->getId(),
            $this->getAttribute(),
            json_encode($this->getViolations(), JSON_THROW_ON_ERROR),
            $this->getThrowable()->getMessage(),
            $this->getThrowable()->getTraceAsString()
        );
    }
}
