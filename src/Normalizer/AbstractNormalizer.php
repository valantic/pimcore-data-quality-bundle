<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Normalizer;

use Pimcore\Model\DataObject;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

abstract class AbstractNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return
            array_key_exists('resource_object', $context)
            && array_key_exists('resource_attribute', $context)
            && $context['resource_object'] instanceof DataObject\Concrete;
    }
}
