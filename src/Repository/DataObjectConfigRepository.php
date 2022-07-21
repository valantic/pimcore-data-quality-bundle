<?php

namespace Valantic\DataQualityBundle\Repository;

use Pimcore\Model\DataObject\Concrete;
use Valantic\DataQualityBundle\Config\DataObjectConfigInterface;
use Valantic\DataQualityBundle\Config\DefaultDataObjectConfig;

class DataObjectConfigRepository
{
    /**
     * @var DataObjectConfigInterface[]
     */
    protected array $configs;
    protected DataObjectConfigInterface$defaultConfig;

    public function __construct(iterable $documents, DefaultDataObjectConfig $defaultConfig)
    {
        $this->configs = $this->iterableToArray($documents);
        $this->defaultConfig = $defaultConfig;
    }

    /**
     * @return DataObjectConfigInterface[]
     */
    public function all(): array
    {
        return $this->configs;
    }

    /**
     * @param class-string<Concrete> $className
     */
    public function get(string $className): DataObjectConfigInterface
    {
        return $this->configs[$className] ?? $this->defaultConfig;
    }

    private function iterableToArray(iterable $iterables): array
    {
        $arr = [];

        foreach ($iterables as $iterable) {
            /** @var DataObjectConfigInterface $iterable */
            if ($iterable::isDefault()) {
                $this->defaultConfig = $iterable;
                continue;
            }
            $arr[$iterable->getClass()] = $iterable;
        }

        return $arr;
    }
}
