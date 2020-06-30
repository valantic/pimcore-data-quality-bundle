<?php

namespace Valantic\DataQualityBundle\Service;

use InvalidArgumentException;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;

abstract class DefinitionInformation
{
    public const TYPE_PLAIN = 'plain';
    public const TYPE_LOCALIZED = 'localized';
    public const TYPE_OBJECTBRICK = 'objectbrick';
    public const TYPE_FIELDCOLLECTION = 'fieldcollection';
    public const TYPE_CLASSIFICATIONSTORE = 'classificationstore';

    /**
     * The class' base name
     * @var string
     */
    protected $name;

    /**
     * Instantiate a new object to retrieve information about $name.
     * @param string $name
     * @throws InvalidArgumentException
     */
    public function __construct(string $name)
    {
        if (strpos($name, '\\') !== false) {
            $nameParts = explode('\\', $name);
            $name = $nameParts[count($nameParts) - 1];
        }
        $this->name = $name;

        if (!$this->getDefinition()) {
            throw new InvalidArgumentException();
        }
    }

    /**
     * Gets the canonical class name
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns an array of all attributes present in this class keyed by their names.
     * @return array
     */
    public function getAttributesFlattened(): array
    {
        return array_merge_recursive(
            $this->getObjectbrickAttributes(),
            $this->getFieldcollectionAttributes(),
            $this->getClassificationstoreAttributes(),
            $this->getRelationAttributes(),
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
        if ($this->isObjectbrickAttribute($attribute)) {
            return self::TYPE_OBJECTBRICK;
        }
        if ($this->isFieldcollectionAttribute($attribute)) {
            return self::TYPE_FIELDCOLLECTION;
        }
        if ($this->isClassificationstoreAttribute($attribute)) {
            return self::TYPE_CLASSIFICATIONSTORE;
        }

        return null;
    }

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
     * Checks whether $attribute is an objectbrick.
     * @param string $attribute
     * @return bool
     */
    public function isObjectbrickAttribute(string $attribute): bool
    {
        return array_key_exists($attribute, $this->getObjectbrickAttributes());
    }

    /**
     * Checks whether $attribute is a fieldcollection.
     * @param string $attribute
     * @return bool
     */
    public function isFieldcollectionAttribute(string $attribute): bool
    {
        return array_key_exists($attribute, $this->getFieldcollectionAttributes());
    }

    /**
     * Checks whether $attribute is a classificationstore.
     * @param string $attribute
     * @return bool
     */
    public function isClassificationstoreAttribute(string $attribute): bool
    {
        return array_key_exists($attribute, $this->getClassificationstoreAttributes());
    }

    /**
     * Checks whether $attribute is a relation.
     * @param string $attribute
     * @return bool
     */
    public function isRelationAttribute(string $attribute): bool
    {
        return array_key_exists($attribute, $this->getRelationAttributes());
    }

    /**
     * Get the definition of the class.
     * @return AbstractModel|null
     */
    abstract protected function getDefinition(): ?AbstractModel;

    /**
     * Returns an array of all localized attributes present in this class keyed by their names.
     * @return array
     */
    protected function getLocalizedAttributes(): array
    {
        $fieldDefinitions = [];
        foreach ($this->getDefinition()->getFieldDefinitions() as $fieldDefinition) {
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
     * Returns an array of all objectbrick attributes present in this class keyed by their names.
     * @return array
     */
    protected function getObjectbrickAttributes(): array
    {
        $fieldDefinitions = [];
        foreach ($this->getDefinition()->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof Objectbricks) {
                /**
                 * @var $fieldDefinition Objectbricks
                 */
                foreach ($fieldDefinition->getAllowedTypes() as $type) {
                    $attributes = (new ObjectBrickInformation($type))->getAttributesFlattened();
                    foreach ($attributes as $name => $attribute) {
                        $fieldDefinitions[$fieldDefinition->getName() . '.' . $type . '.' . $name] = $attribute;
                    }
                }
            }
        }


        return $fieldDefinitions;
    }

    /**
     * Returns an array of all fieldcollection attributes present in this class keyed by their names.
     * @return array
     */
    protected function getFieldcollectionAttributes(): array
    {
        $fieldDefinitions = [];
        foreach ($this->getDefinition()->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof Fieldcollections) {
                /**
                 * @var $fieldDefinition Fieldcollections
                 */
                foreach ($fieldDefinition->getAllowedTypes() as $type) {
                    $attributes = (new FieldCollectionInformation($type))->getAttributesFlattened();
                    foreach ($attributes as $name => $attribute) {
                        $fieldDefinitions[$fieldDefinition->getName() . '.' . $type . '.' . $name] = $attribute;
                    }
                }
            }
        }

        return $fieldDefinitions;
    }

    /**
     * Returns an array of all classificationstore attributes present in this class keyed by their names.
     * @return array
     */
    protected function getClassificationstoreAttributes(): array
    {
        $fieldDefinitions = [];
        foreach ($this->getDefinition()->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof Classificationstore) {
                /**
                 * @var $fieldDefinition Classificationstore
                 */
                // TODO: finish implementation
                $fieldDefinitions[$fieldDefinition->getName()] = $fieldDefinition;
            }
        }

        return $fieldDefinitions;
    }


    /**
     * Returns an array of all relation attributes present in this class keyed by their names.
     * @return array
     */
    protected function getRelationAttributes(): array
    {
        $fieldDefinitions = [];
        foreach ($this->getDefinition()->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof AbstractRelations) {
                /**
                 * @var $fieldDefinition AbstractRelations
                 */
                $fieldDefinitions[$fieldDefinition->getName()] = $fieldDefinition;
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
        foreach ($this->getDefinition()->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof Localizedfields || $fieldDefinition instanceof Fieldcollections || $fieldDefinition instanceof Objectbricks || $fieldDefinition instanceof Classificationstore || $fieldDefinition instanceof AbstractRelations) {
                continue;
            }
            $fieldDefinitions[$fieldDefinition->getName()] = $fieldDefinition;
        }

        return $fieldDefinitions;
    }
}
