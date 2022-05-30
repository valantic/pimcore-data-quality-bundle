<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Config\V1\Constraints;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Throwable;
use Valantic\DataQualityBundle\Config\V1\AbstractWriter;

class Writer extends AbstractWriter implements ConstraintKeys
{
    /**
     * Write the bundle's config file.
     */
    public function __construct(protected Reader $reader, EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Adds a new config entry for a class-attribute combination if it does not yet exist.
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
     */
    public function modifyRule(string $className, string $attributeName, string $constraint, ?string $params = null): bool
    {
        try {
            $paramsParsed = json_decode($params ?: '', true, 512, \JSON_THROW_ON_ERROR);
        } catch (Throwable) {
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
     */
    public function modifyNote(string $className, string $attributeName, ?string $note = null): bool
    {
        $raw = $this->reader->getCurrentSection();

        $raw[$className][$attributeName][self::KEY_NOTE] = $note;

        return $this->writeConfig($raw);
    }

    /**
     * Deletes a class-attribute note.
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

    /**
     * {@inheritDoc}
     */
    protected function getCurrentSectionName(): string
    {
        return self::CONFIG_SECTION_CONSTRAINTS;
    }
}
