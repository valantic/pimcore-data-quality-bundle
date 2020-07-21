<?php

namespace Valantic\DataQualityBundle\Config\V1;

use Pimcore\Model\DataObject\Concrete;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Throwable;
use Valantic\DataQualityBundle\Service\Information\DefinitionInformationFactory;

abstract class AbstractReader extends Config
{
    /**
     * @var DefinitionInformationFactory
     */
    protected $definitionInformationFactory;

    /**
     * Read and write the bundle's configuration.
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param DefinitionInformationFactory $definitionInformationFactory
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, DefinitionInformationFactory $definitionInformationFactory)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->definitionInformationFactory = $definitionInformationFactory;
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
            $classInformation = $this->definitionInformationFactory->make($className);
            $className = $classInformation->getName();
            if(empty($className)){
                throw new RuntimeException(sprintf("Could not look up %s.", $className));
            }
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
