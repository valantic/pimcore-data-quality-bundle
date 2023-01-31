<?php

namespace Valantic\DataQualityBundle\Repository;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Listing;

class DataObjectRepository
{
    public const PIMCORE_DATA_OBJECT_NAMESPACE = 'Pimcore\Model\DataObject';
    public const PIMCORE_DATA_OBJECT_LISTING = 'Listing';

    public function getListing(string $className): Listing
    {
        $listingName = self::PIMCORE_DATA_OBJECT_NAMESPACE . '\\' . $className . '\\' . self::PIMCORE_DATA_OBJECT_LISTING;

        /** @var Listing */
        return new $listingName();
    }

    public function getValue(DataObject $object, string $fieldName): mixed
    {
        $getter = "get$fieldName";

        return $object->$getter();
    }

    public function setValue(DataObject &$object, string $fieldName, mixed $value): void
    {
        $setter = "set$fieldName";
        $object->$setter($value);
    }

    public function update(DataObject $object, array $data = []): DataObject
    {
        foreach ($data as $fieldName => $value) {
            $this->setValue($object, $fieldName, $value);
        }

        return $object->save();
    }
}
