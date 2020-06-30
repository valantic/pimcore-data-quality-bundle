<?php

namespace Valantic\DataQualityBundle\Service;

use InvalidArgumentException;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;

class ClassInformation
{
    public const TYPE_PLAIN = 'plain';
    public const TYPE_LOCALIZED = 'localized';
    /**
     * The class' base name
     * @var string
     */
    protected $className;

    /**
     * Instantiate a new object to retrieve information about $className.
     * @param string $className
     * @throws InvalidArgumentException If $className does not exist as Pimcore DataObject
     */
    public function __construct(string $className)
    {
        if (strpos($className, '\\') !== false) {
            $nameParts = explode('\\', $className);
            $className = $nameParts[count($nameParts) - 1];
        }
        $this->className = $className;

        if (!$this->getClassDefinition()) {
            throw new InvalidArgumentException();
        }
    }

    /**
     * Gets the canonical class name
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Returns an array of all attributes present in this class keyed by their names.
     * @return array
     */
    public function getAttributesFlattened(): array
    {
        return array_merge_recursive(
            $this->getLocalizedAttributes(),
            $this->getPlainAttributes()
        );
    }

    /**
     * Get the type of a class attribute.
     * @param string $attribute
     * @return string|null
     */
    public function getAttributeType(string $attribute): ?string
    {
        if ($this->isPlainAttribute($attribute)) {
            return self::TYPE_PLAIN;
        }
        if ($this->isLocalizedAttribute($attribute)) {
            return self::TYPE_LOCALIZED;
        }

        return null;

    /**
     * Checks whether $attribute is plain.
     * @param string $attribute
     * @return bool
     */
    public function isPlainAttribute(string $attribute): bool
    {
        return array_key_exists($attribute, $this->getPlainAttributes());
    }

    /**
     * Checks whether $attribute is localized.
     * @param string $attribute
     * @return bool
     */
    public function isLocalizedAttribute(string $attribute): bool
    {
        return array_key_exists($attribute, $this->getLocalizedAttributes());
    }

    /**
     * Get the definition of the class.
     * @return ClassDefinition|null
     */
    protected function getClassDefinition(): ?ClassDefinition
    {
        return ClassDefinition::getByName($this->className);
    }

    /**
     * Returns an array of all localized attributes present in this class keyed by their names.
     * @return array
     */
    protected function getLocalizedAttributes(): array
    {
        $fieldDefinitions = [];
        foreach ($this->getClassDefinition()->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof Localizedfields) {
                /**
                 * @var $fieldDefinition Localizedfields
                 */
                foreach ($fieldDefinition->getChildren() as $child) {
                    $fieldDefinitions[$child->getName()] = $child;
                }
            }
        }

        return $fieldDefinitions;
    }

    /**
     * Returns an array of all plain attributes present in this class keyed by their names.
     * @return array
     */
    protected function getPlainAttributes(): array
    {
        $fieldDefinitions = [];
        foreach ($this->getClassDefinition()->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof Localizedfields || $fieldDefinition instanceof Fieldcollections || $fieldDefinition instanceof Objectbricks || $fieldDefinition instanceof Classificationstore) {
                continue;
            }
            $fieldDefinitions[$fieldDefinition->getName()] = $fieldDefinition;
        }

        return $fieldDefinitions;
    }
}
