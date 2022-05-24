<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Config\V1;

use Pimcore\Model\DataObject\Concrete;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Throwable;
use Valantic\DataQualityBundle\Service\Information\DefinitionInformationFactory;

abstract class AbstractReader extends Config implements ReaderInterface
{
    /**
     * Read and write the bundle's configuration.
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        protected DefinitionInformationFactory $definitionInformationFactory
    ) {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getConfiguredClasses(): array
    {
        return array_keys($this->getCurrentSection());
    }

    public function isClassConfigured(string $className): bool
    {
        return in_array($className, $this->getConfiguredClasses(), true);
    }

    /**
     * @param class-string $className
     */
    public function getForClass(string $className): array
    {
        try {
            $classInformation = $this->definitionInformationFactory->make($className);
            $className = $classInformation->getName();
            if (empty($className)) {
                throw new RuntimeException(sprintf('Could not look up %s.', $className));
            }
        } catch (Throwable) {
            return [];
        }

        if (!$this->isClassConfigured($className)) {
            return [];
        }

        return $this->safeArray($this->getCurrentSection(), $className);
    }

    public function getForObject(Concrete $obj): array
    {
        return $this->getForClass($obj->getClassName());
    }

    public function isObjectConfigured(Concrete $obj): bool
    {
        return count($this->getForObject($obj)) > 0;
    }
}
