<?php

namespace Valantic\DataQualityBundle\Config\V1\Constraints;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Throwable;
use Valantic\DataQualityBundle\Config\V1\AbstractWriter;

class Writer extends AbstractWriter implements ConstraintKeys
{
    /**
     * @var Reader
     */
    protected $reader;

    /**
     * {@inheritDoc}
     */
    protected function getCurrentSectionName(): string
    {
        return self::CONFIG_SECTION_CONSTRAINTS;
    }

    /**
     * Write the bundle's config file.
     *
     * @param Reader $reader
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(Reader $reader, EventDispatcherInterface $eventDispatcher)
    {
        $this->reader = $reader;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Adds a new config entry for a class-attribute combination if it does not yet exist.
     *
     * @param string $className
     * @param string $attributeName
     *
     * @return bool
     */
    public function addClassAttribute(string $className, string $attributeName): bool
    {
        if ($this->reader->isClassAttributeConfigured($className, $attributeName)) {
            return true;
        }

        $raw = $this->getCurrentSection();
        if (!$this->reader->isClassConfigured($className)) {
            $raw[$className] = [];
        }
        $raw[$className][$attributeName] = [self::KEY_NOTE => null, self::KEY_RULES => []];

        return $this->writeConfig($raw);
    }


    /**
     * Adds a new config entry for a class-attribute combination if it does not yet exist.
     *
     * @param string $className
     * @param string $attributeName
     *
     * @return bool
     */
    public function deleteClassAttribute(string $className, string $attributeName): bool
    {
        if (!$this->reader->isClassConfigured($className) || !$this->reader->isClassAttributeConfigured($className, $attributeName)) {
            return true;
        }

        $raw = $this->reader->getCurrentSection();
        unset($raw[$className][$attributeName]);

        return $this->writeConfig($raw);
    }

    /**
     * Adds a new config entry or edits an existing one for a class-attribute rule if it does not yet exist.
     *
     * @param string $className
     * @param string $attributeName
     * @param string $constraint
     * @param string $params
     *
     * @return bool
     */
    public function modifyRule(string $className, string $attributeName, string $constraint, string $params = null): bool
    {
        try {
            $paramsParsed = json_decode($params ?: '', true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $throwable) {
            $paramsParsed = $params;
        }

        if ($paramsParsed === '') {
            $paramsParsed = null;
        }

        $raw = $this->reader->getCurrentSection();

        $raw[$className][$attributeName][self::KEY_RULES][$constraint] = $paramsParsed;

        return $this->writeConfig($raw);
    }

    /**
     * Deletes a class-attribute rule.
     *
     * @param string $className
     * @param string $attributeName
     * @param string $constraint
     *
     * @return bool
     */
    public function deleteRule(string $className, string $attributeName, string $constraint): bool
    {
        if (!$this->reader->isClassConfigured($className) || !$this->reader->isClassAttributeConfigured($className, $attributeName)) {
            return true;
        }

        $raw = $this->reader->getCurrentSection();

        unset($raw[$className][$attributeName][self::KEY_RULES][$constraint]);

        return $this->writeConfig($raw);
    }

    /**
     * Adds a new config entry or edits an existing one for a class-attribute note if it does not yet exist.
     *
     * @param string $className
     * @param string $attributeName
     * @param string|null $note
     *
     * @return bool
     */
    public function modifyNote(string $className, string $attributeName, string $note = null): bool
    {
        $raw = $this->reader->getCurrentSection();

        $raw[$className][$attributeName][self::KEY_NOTE] = $note;

        return $this->writeConfig($raw);
    }

    /**
     * Deletes a class-attribute note.
     *
     * @param string $className
     * @param string $attributeName
     *
     * @return bool
     */
    public function deleteNote(string $className, string $attributeName): bool
    {
        if (!$this->reader->isClassConfigured($className) || !$this->reader->isClassAttributeConfigured($className, $attributeName)) {
            return true;
        }

        $raw = $this->reader->getCurrentSection();

        $raw[$className][$attributeName][self::KEY_NOTE] = null;

        return $this->writeConfig($raw);
    }
}
