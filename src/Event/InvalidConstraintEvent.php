<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Event;

class InvalidConstraintEvent extends Event
{
    public const NAME = 'valantic.data_quality.invalid_constraint';

    public function __construct(protected \Throwable $throwable, protected string $name, protected mixed $params)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParams(): mixed
    {
        return $this->params;
    }

    public function getThrowable(): \Throwable
    {
        return $this->throwable;
    }

    protected function logMessage(): string
    {
        return sprintf(
            "Constraint %s with parameters %s failed to execute.\nMessage: %s\nTrace: %s",
            $this->getName(),
            json_encode($this->getParams(), \JSON_THROW_ON_ERROR),
            $this->getThrowable()->getMessage(),
            $this->getThrowable()->getTraceAsString()
        );
    }
}
