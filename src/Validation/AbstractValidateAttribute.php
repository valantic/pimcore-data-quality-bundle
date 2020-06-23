<?php

namespace Valantic\DataQualityBundle\Validation;

use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Valantic\DataQualityBundle\Config\V1\Constraints\Reader as ConfigReader;
use Valantic\DataQualityBundle\Service\ClassInformation;

abstract class AbstractValidateAttribute implements Validatable, Scorable
{
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
     * @var ConfigReader
     */
    protected $config;

    /**
     * @var array
     */
    protected $validationConfig;

    /**
     * Violations found during validation.
     * @var array
     */
    protected $violations = [];

    /**
     * @var ClassInformation
     */
    protected $classInformation;

    /**
     * Validates an attribute of an object.
     *
     * @param Concrete $obj Object to validate
     * @param string $attribute Attribute to validate
     * @param ConfigReader $config
     */
    public function __construct(Concrete $obj, string $attribute, ConfigReader $config)
    {
        $validationBuilder = Validation::createValidatorBuilder();
        $this->validator = $validationBuilder->getValidator();
        $this->obj = $obj;
        $this->attribute = $attribute;
        $this->config = $config;
        $this->validationConfig = $config->getForObjectAttribute($obj, $attribute);
        $this->classInformation = new ClassInformation($this->obj->getClassName());
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
        $constraints = [];
        foreach ($this->getRules() as $name => $params) {
            if (strpos($name, '\\') === false) {
                $name = 'Symfony\Component\Validator\Constraints\\' . $name;
            }

            if (!class_exists($name)) {
                continue;
            }

            try {
                $constraints[] = new $name(...([$params]));
            } catch (Throwable $throwable) {
                // TODO: emit event
            }
        }

        return $constraints;
    }
}
