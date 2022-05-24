<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Config\V1;

use Pimcore\Model\DataObject\Concrete;

interface ReaderInterface
{
    /**
     * Get the list of classes than can be validated i.e. are configured.
     */
    public function getConfiguredClasses(): array;

    /**
     * Checks whether $className is configured.
     */
    public function isClassConfigured(string $className): bool;

    /**
     * Given a class name, return the corresponding config.
     *
     * @param string $className Base name or ::class
     */
    public function getForClass(string $className): array;

    /**
     * Given $obj, return the corresponding config.
     */
    public function getForObject(Concrete $obj): array;

    /**
     * Checks whether $obj is configured.
     */
    public function isObjectConfigured(Concrete $obj): bool;
}
