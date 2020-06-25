<?php

namespace Valantic\DataQualityBundle\Config\V1;

use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Valantic\DataQualityBundle\Service\ClassInformation;
use Throwable;

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
     * Get the list of classes than can be validated i.e. are configured.
     *
     * @return array
     */
    public function getConfiguredClasses(): array
    {
        return array_keys($this->getCurrentSection());
    }

    /**
     * Checks whether $className is configured.
     * @param string $className
     * @return bool
     */
    public function isClassConfigured(string $className): bool
    {
        return in_array($className, $this->getConfiguredClasses(), true);
    }

    /**
     * Given a class name, return the corresponding config.
     *
     * @param string $className Base name or ::class
     * @return array
     */
    public function getForClass(string $className): array
    {
        try {
            $className = (new ClassInformation($className))->getClassName();
        } catch (Throwable $throwable) {
            return [];
        }

        if (!$this->isClassConfigured($className)) {
            return [];
        }

        return $this->safeArray($this->getCurrentSection(), $className);
    }

    /**
     * Given $obj, return the corresponding config.
     *
     * @param Concrete $obj
     * @return array
     */
    public function getForObject(Concrete $obj): array
    {
        return $this->getForClass($obj->getClassName());
    }

    /**
     * Checks whether $obj is configured.
     * @param Concrete $obj
     * @return bool
     */
    public function isObjectConfigured(Concrete $obj): bool
    {
        return count($this->getForObject($obj)) > 0;
    }

}
