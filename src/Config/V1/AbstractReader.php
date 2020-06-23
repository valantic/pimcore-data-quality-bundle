<?php

namespace Valantic\DataQualityBundle\Config\V1;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class AbstractReader extends Config
{
    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * Read and write the bundle's configuration.
     *
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Get a config section.
     *
     * @param string $name
     * @return array
     */
    protected function getSection(string $name): array
    {
        return array_key_exists($name, $this->getRaw()) ? $this->getRaw()[$name] : [];
    }

    /**
     * Get the constraints section from the config.
     *
     * @return array
     */
    public function getConstraintsSection(): array
    {
        return $this->getSection(self::CONFIG_SECTION_CONSTRAINTS);
    }

    /**
     * Get the locales section from the config.
     *
     * @return array
     */
    public function getLocalesSection(): array
    {
        return $this->getSection(self::CONFIG_SECTION_LOCALES);
    }

    abstract protected function getCurrentSectionName(): string;

    protected function getCurrentSection(): array
    {
        return $this->getSection($this->getCurrentSectionName());
    }
}
