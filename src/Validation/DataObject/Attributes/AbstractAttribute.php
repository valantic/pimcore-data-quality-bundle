<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation\DataObject\Attributes;

use Pimcore\Model\DataObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Valantic\DataQualityBundle\Event\InvalidConstraintEvent;
use Valantic\DataQualityBundle\Repository\ConfigurationRepository;
use Valantic\DataQualityBundle\Validation\ColorableInterface;
use Valantic\DataQualityBundle\Validation\ColorScoreTrait;
use Valantic\DataQualityBundle\Validation\PassFailInterface;
use Valantic\DataQualityBundle\Validation\ScorableInterface;
use Valantic\DataQualityBundle\Validation\ValidatableInterface;

abstract class AbstractAttribute implements ValidatableInterface, ScorableInterface, ColorableInterface, PassFailInterface
{
    use ColorScoreTrait;
    protected string $attribute;
    protected array $groups;
    protected array $skippedConstraints;
    protected array $constraints;
    protected DataObject\Concrete $obj;
    protected ?array $scores = null;
    protected ?float $score = null;

    /**
     * @var ConstraintViolationListInterface[]
     */
    protected array $violations = [];

    public function __construct(
        protected ConfigurationRepository $configurationRepository,
        protected EventDispatcherInterface $eventDispatcher,
        protected ValidatorInterface $validator,
    ) {
    }

    public function __clone(): void
    {
        unset(
            $this->obj,
            $this->values,
            $this->attribute,
            $this->groups,
            $this->skippedConstraints,
            $this->constraints,
        );

        $this->score = null;
        $this->scores = null;
    }

    public function passes(): bool
    {
        return $this->score() === 1.0;
    }

    abstract public function value(): mixed;

    protected function setConstrains(array $constraints): void
    {
        $this->constraints = [];
        foreach ($constraints as $name => $params) {
            if (!str_contains($name, '\\')) {
                $name = sprintf('Symfony\Component\Validator\Constraints\\%s', $name);
            }

            if (!class_exists($name)) {
                continue;
            }

            try {
                $reflection = new \ReflectionClass($name);

                $subclasses = array_filter($this->skippedConstraints, fn ($skippedConstraint): bool => $reflection->isSubclassOf($skippedConstraint));
            } catch (\ReflectionException) {
                $subclasses = [1];
            }

            if (!empty($subclasses) || in_array($name, $this->skippedConstraints, true)) {
                continue;
            }

            try {
                $instance = new $name(...[$params]);
                $this->constraints[] = $instance;
            } catch (\Throwable $throwable) {
                $this->eventDispatcher->dispatch(new InvalidConstraintEvent($throwable, $name, $params));
            }
        }
    }
}
