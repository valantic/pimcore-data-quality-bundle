<?php

namespace Valantic\DataQualityBundle\Validation;

use Exception;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;
use Valantic\DataQualityBundle\Config\V1\Config;

class ValidateAttribute implements Validatable, Scorable
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
     * @var Config
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
     * Validates an attribute of an object.
     *
     * @param Concrete $obj Object to validate
     * @param string $attribute Attribute to validate
     * @param Config $config
     */
    public function __construct(Concrete $obj, string $attribute, Config $config)
    {
        $validationBuilder = Validation::createValidatorBuilder();
        $this->validator = $validationBuilder->getValidator();
        $this->obj = $obj;
        $this->attribute = $attribute;
        $this->config = $config;
        $this->validationConfig = $config->getForObjectAttribute($obj, $attribute);
    }

    /**
     * {@inheritDoc}
     */
    public function validate()
    {
        if (!array_key_exists($this->attribute, $this->obj->getClass()->getFieldDefinitions())) {
            return;
        }

        try {
            $this->violations = $this->validator->validate($this->obj->get($this->attribute), $this->getConstraints());
        } catch (Exception $e) {
            // TODO: emit event
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
