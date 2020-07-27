<?php

namespace Valantic\DataQualityBundle\Validation\DataObject\Attributes;

use Exception;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\ModelInterface;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;
use Valantic\DataQualityBundle\Config\V1\Constraints\Reader as ConstraintsConfig;
use Valantic\DataQualityBundle\Config\V1\Meta\MetaKeys;
use Valantic\DataQualityBundle\Config\V1\Meta\Reader as MetaConfig;
use Valantic\DataQualityBundle\Event\ConstraintFailureEvent;
use Valantic\DataQualityBundle\Event\InvalidConstraintEvent;
use Valantic\DataQualityBundle\Service\Information\DefinitionInformation;
use Valantic\DataQualityBundle\Service\Information\DefinitionInformationFactory;
use Valantic\DataQualityBundle\Shared\SafeArray;
use Valantic\DataQualityBundle\Validation\Colorable;
use Valantic\DataQualityBundle\Validation\ColorScoreTrait;
use Valantic\DataQualityBundle\Validation\Scorable;
use Valantic\DataQualityBundle\Validation\Validatable;

abstract class AbstractAttribute implements Validatable, Scorable, Colorable
{
    use SafeArray;
    use ColorScoreTrait;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var Concrete
     */
    protected $obj;

    /**
     * @var string
     */
    protected $attribute;

    /**
     * @var ConstraintsConfig
     */
    protected $constraintsConfig;

    /**
     * @var array
     */
    protected $validationConfig;

    /**
     * Violations found during validation.
     * @var array|ConstraintViolationListInterface
     */
    protected $violations = [];

    /**
     * @var DefinitionInformation
     */
    protected $classInformation;

    /**
     * @var MetaConfig
     */
    protected $metaConfig;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $skippedConstraints;

    /**
     * The maximum nesting level. Used for cycle detection.
     * Is set on the very first call and not modified after.
     * @var int
     */
    protected static $maxNestingLevel = -1;

    /**
     * The root of the validation tree. Used for cycle prevention.
     * Is set on the very first call and not modified after.
     * @var ModelInterface
     */
    protected static $validationRootObject;

    /**
     * Validates an attribute of an object.
     *
     * @param Concrete $obj Object to validate
     * @param string $attribute Attribute to validate
     * @param ConstraintsConfig $constraintsConfig
     * @param MetaConfig $metaConfig
     * @param EventDispatcherInterface $eventDispatcher
     * @param DefinitionInformationFactory $definitionInformationFactory
     * @param ContainerInterface $container
     * @param array $skippedConstraints
     */
    public function __construct(Concrete $obj, string $attribute, ConstraintsConfig $constraintsConfig, MetaConfig $metaConfig, EventDispatcherInterface $eventDispatcher, DefinitionInformationFactory $definitionInformationFactory, ContainerInterface $container, array $skippedConstraints)
    {
        $validationBuilder = Validation::createValidatorBuilder();
        $this->validator = $validationBuilder->getValidator();
        $this->obj = $obj;
        $this->attribute = $attribute;
        $this->constraintsConfig = $constraintsConfig;
        $this->validationConfig = $constraintsConfig->getRulesForObjectAttribute($obj, $attribute);
        $this->metaConfig = $metaConfig;
        $this->eventDispatcher = $eventDispatcher;
        $this->classInformation = $definitionInformationFactory->make($this->obj->getClassName());
        $this->container = $container;
        $this->skippedConstraints = $skippedConstraints;

        if (self::$maxNestingLevel < 0) {
            self::$maxNestingLevel = $this->metaConfig->getForObject($this->obj)[MetaKeys::KEY_NESTING_LIMIT] ?? 1;
        }

        if (!self::$validationRootObject) {
            self::$validationRootObject = clone $this->obj;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function score(): float
    {
        if (!count($this->getConstraints())) {
            return 0;
        }

        return 1 - (count($this->violations) / count($this->getConstraints()));
    }

    /**
     * Get the validation rules for this attribute.
     * @return array
     */
    protected function getRules(): array
    {
        return $this->validationConfig;
    }

    /**
     * Get instantiated constraint classes. Invalid constrains are discarded.
     * @return array
     */
    protected function getConstraints(): array
    {
        if ($this->getNestingLevel() > 2) {
            dd();
        }
        $constraints = [];
        foreach ($this->getRules() as $name => $params) {
            if ($this->getNestingLevel() > self::$maxNestingLevel) {
                continue;
            }

            if (strpos($name, '\\') === false) {
                $name = 'Symfony\Component\Validator\Constraints\\' . $name;
            }

            if (!class_exists($name)) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($name);

                $subclasses = array_filter($this->skippedConstraints, function ($skippedConstraint) use ($reflection) {
                    return $reflection->isSubclassOf($skippedConstraint);
                });
            } catch (\ReflectionException $e) {
                $subclasses = [1];
            }


            if (in_array($name, $this->skippedConstraints, true) || !empty($subclasses)) {
                continue;
            }

            try {
                $instance = new $name(...([$params]));
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
     * Returns the value being validated.
     * @return mixed
     */
    abstract public function value();

    /**
     * Traverses the inheritance tree until a value has been found.
     *
     * @param Concrete $obj
     * @param string|null $locale
     *
     * @return mixed
     * @throws Exception
     */
    protected function valueInherited(Concrete $obj, ?string $locale = null)
    {
        if (!$obj->getParentId() || !($obj->getParent() instanceof Concrete) || $obj->get($this->attribute, $locale)) {
            return $obj->get($this->attribute, $locale);
        }

        return $this->valueInherited($obj->getParent(), $locale);
    }

    /**
     * {@inheritDoc}
     */
    public function validate()
    {
        try {
            $this->violations = $this->validator->validate($this->value(), $this->getConstraints());
        } catch (Throwable $e) {
            $this->eventDispatcher->dispatch(new ConstraintFailureEvent($e, $this->obj->getId(), $this->attribute, $this->violations));
        }
    }

    /**
     * Returns the current nesting level. Used for cycle detection.
     * @return int A positive integer
     */
    protected function getNestingLevel(): int
    {
        return max(count(array_filter(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), function ($trace) {
                return ($trace['class'] ?? '') === __CLASS__ && ($trace['function'] ?? '') === 'validate';
            })) - 1, 0);
    }
}
