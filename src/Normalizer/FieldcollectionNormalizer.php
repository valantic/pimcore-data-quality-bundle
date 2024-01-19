<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Normalizer;

use Pimcore\Model\DataObject;

class FieldcollectionNormalizer extends AbstractNormalizer
{
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return
            parent::supportsNormalization($data, $format, $context)
            && $data instanceof DataObject\Fieldcollection\Data\AbstractData;
    }

    public function normalize($object, ?string $format = null, array $context = []): mixed
    {
        $parts = explode('.', (string) $context['resource_attribute'], 3);
        $getter = 'get' . ucfirst($parts[2]);
        if (method_exists($object, $getter)) {
            return $this->normalizer->normalize($object->get($parts[2]), $format, $context);
        }

        return null;
    }
}
