<?php

namespace Valantic\DataQualityBundle\Repository;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Listing;

class DataObjectRepository
{
    final public const PIMCORE_DATA_OBJECT_NAMESPACE = 'Pimcore\Model\DataObject';
    final public const PIMCORE_DATA_OBJECT_LISTING = 'Listing';

    public function getListing(string $className): Listing
    {
        $listingName = self::PIMCORE_DATA_OBJECT_NAMESPACE . '\\' . $className . '\\' . self::PIMCORE_DATA_OBJECT_LISTING;

        /** @var Listing */
        return new $listingName();
    }

    public function getValue(DataObject $object, string $fieldName): mixed
    {
        return $object->get($fieldName);
    }

    public function setValue(DataObject $object, string $fieldName, mixed $value): void
    {
        $object->set($fieldName, $value);
    }

    public function update(DataObject $object, array $data = []): DataObject
    {
        foreach ($data as $fieldName => $value) {
            $this->setValue($object, $fieldName, $value);
        }

        return $object->save();
    }
}
