<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Normalizer;

use Pimcore\Model\DataObject;

class RelationNormalizer extends AbstractNormalizer
{
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return
            parent::supportsNormalization($data, $format, $context)
            && $data instanceof DataObject\Concrete;
    }

    public function normalize($object, ?string $format = null, array $context = []): int
    {
        return $object->getId();
    }
}
