<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation\DataObject\Attributes;

use Exception;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;
use Valantic\DataQualityBundle\Event\ConstraintFailureEvent;
use Valantic\DataQualityBundle\Event\InvalidConstraintEvent;
use Valantic\DataQualityBundle\Repository\ConfigurationRepository;
use Valantic\DataQualityBundle\Service\Information\AbstractDefinitionInformation;
use Valantic\DataQualityBundle\Service\Information\DefinitionInformationFactory;
use Valantic\DataQualityBundle\Shared\SafeArray;
use Valantic\DataQualityBundle\Validation\ColorableInterface;
use Valantic\DataQualityBundle\Validation\ColorScoreTrait;
use Valantic\DataQualityBundle\Validation\ScorableInterface;
use Valantic\DataQualityBundle\Validation\ValidatableInterface;

use const DEBUG_BACKTRACE_IGNORE_ARGS;

abstract class AbstractAttribute implements ValidatableInterface, ScorableInterface, ColorableInterface
{
    use ColorScoreTrait;
    use SafeArray;
    protected ValidatorInterface $validator;
    protected array $validationConfig;
    protected Concrete $obj;
    protected string $attribute;
    protected array $groups;
    protected array $skippedConstraints;

    /**
     * Violations found during validation.
     *
     * @var array|ConstraintViolationListInterface[]
     */
    protected array $violations = [];
    protected AbstractDefinitionInformation $classInformation;

    /**
     * The maximum nesting level. Used for cycle detection.
     * Is set on the very first call and not modified after.
     */
    protected static int $maxNestingLevel = -1;

    /**
     * The root of the validation tree. Used for cycle prevention.
     * Is set on the very first call and not modified after.
     */
    protected static ElementInterface $validationRootObject;

    /**
     * Validates an attribute of an object.
     */
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected DefinitionInformationFactory $definitionInformationFactory,
        protected ContainerInterface $container,
        protected ConfigurationRepository $configurationRepository,
    ) {
        $validationBuilder = Validation::createValidatorBuilder();
        $this->validator = $validationBuilder->getValidator();
    }

    public function __clone(): void
    {
        unset(
            $this->obj,
            $this->attribute,
            $this->groups,
            $this->skippedConstraints,
            $this->validationConfig
        );
    }

    public function configure(
        Concrete $obj,
        string $attribute,
        array $groups,
        array $skippedConstraints,
    ): void {
        $this->obj = $obj;
        $this->attribute = $attribute;
        $this->groups = $groups;
        $this->skippedConstraints = $skippedConstraints;
        $this->validationConfig = $this->configurationRepository->getRulesForAttribute($obj::class, $attribute);
        $this->classInformation = $this->definitionInformationFactory->make($this->obj::class);
        if (self::$maxNestingLevel < 0) {
            self::$maxNestingLevel = $this->configurationRepository->getConfiguredNestingLimit($this->obj::class);
        }

        if (!isset(self::$validationRootObject)) {
            self::$validationRootObject = clone $this->obj;
        }
    }

    public function score(): float
    {
        if (!count($this->getConstraints())) {
            return 0;
        }

        return 1 - (count($this->violations) / count($this->getConstraints()));
    }

    public function validate(): void
    {
        try {
            $this->violations = $this->validator->validate($this->value(), $this->getConstraints(), $this->groups);
        } catch (Throwable $e) {
            $this->eventDispatcher->dispatch(new ConstraintFailureEvent($e, $this->obj->getId(), $this->attribute, $this->violations));
        }
    }

    /**
     * Returns the value being validated.
     */
    abstract public function value(): mixed;

    /**
     * Get the validation rules for this attribute.
     */
    protected function getRules(): array
    {
        return $this->validationConfig;
    }

    /**
     * Get instantiated constraint classes. Invalid constrains are discarded.
     */
    protected function getConstraints(): array
    {
        if ($this->getNestingLevel() > 2) {
            throw new RuntimeException('Nesting levels deeper than 2 are currently not supported');
        }
        $constraints = [];
        foreach ($this->getRules() as $name => $params) {
            if ($this->getNestingLevel() > self::$maxNestingLevel) {
                continue;
            }

            if (!str_contains($name, '\\')) {
                $name = 'Symfony\Component\Validator\Constraints\\' . $name;
            }

            if (!class_exists($name)) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($name);

                $subclasses = array_filter($this->skippedConstraints, fn($skippedConstraint) => $reflection->isSubclassOf($skippedConstraint));
            } catch (ReflectionException) {
                $subclasses = [1];
            }

            if (!empty($subclasses) || in_array($name, $this->skippedConstraints, true)) {
                continue;
            }

            try {
                $instance = new $name(...[$params]);
                if (method_exists($instance, 'setContainer')) {
                    $instance->setContainer($this->container);
                }
                $constraints[] = $instance;
            } catch (Throwable $throwable) {
                $this->eventDispatcher->dispatch(new InvalidConstraintEvent($throwable, $name, $params));
            }
        }

        return $constraints;
    }

    /**
     * Traverses the inheritance tree until a value has been found.
     *
     * @throws Exception
     */
    protected function valueInherited(Concrete $obj, ?string $locale = null): mixed
    {
        if (!$obj->getParentId() || !($obj->getParent() instanceof Concrete) || $obj->get($this->attribute, $locale)) {
            return $obj->get($this->attribute, $locale);
        }

        return $this->valueInherited($obj->getParent(), $locale);
    }

    /**
     * Returns the current nesting level. Used for cycle detection.
     *
     * @return int A positive integer
     */
    protected function getNestingLevel(): int
    {
        return max(
            count(
                array_filter(
                    debug_backtrace(
                        DEBUG_BACKTRACE_IGNORE_ARGS
                    ),
                    fn($trace): bool => ($trace['class'] ?? '') === self::class && $trace['function'] === 'validate'
                )
            ) - 1,
            0
        );
    }
}
