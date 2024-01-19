<?php

namespace Valantic\DataQualityBundle\Repository;

use Pimcore\Model\DataObject\Concrete;
use Valantic\DataQualityBundle\Config\DataObjectConfigInterface;
use Valantic\DataQualityBundle\Config\DefaultDataObjectConfig;

class DataObjectConfigRepository
{
    /**
     * @var array<class-string,DataObjectConfigInterface>
     */
    protected ?array $configs = null;

    public function __construct(
        protected iterable $configInterfaces,
        protected DefaultDataObjectConfig $defaultConfig,
    ) {
    }

    /**
     * @return DataObjectConfigInterface[]
     */
    public function all(): array
    {
        return $this->getConfigs();
    }

    /**
     * @param class-string<Concrete> $className
     */
    public function get(string $className): DataObjectConfigInterface
    {
        return $this->getConfigs()[$className] ?? $this->defaultConfig;
    }

    private function getConfigs(): array
    {
        if ($this->configs === null) {
            foreach ($this->configInterfaces as $configuration) {
                /** @var DefaultDataObjectConfig $configuration */
                if ($configuration::isDefault()) {
                    $this->defaultConfig = $configuration;
                    continue;
                }
                $this->configs[$configuration->getClass()] = $configuration;
            }
        }

        return $this->configs ?: [];
    }
}
