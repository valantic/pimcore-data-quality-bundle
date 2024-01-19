<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

/**
 * Adds all services with the tags "valantic.dataquality.serializer.encoder" and "valantic.dataquality.serializer.normalizer" as
 * encoders and normalizers to the DataQualityBundle Serializer service.
 *
 * This does exactly the same as the framework serializer pass, but adds encoders/normalizers to our custom admin
 * serializer.
 *
 * @see \Symfony\Component\Serializer\Serializer
 *
 * @internal
 */
final class SerializerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('Valantic\\DataQualityBundle\\Serializer\\Serializer')) {
            return;
        }

        $definition = $container->getDefinition('Valantic\\DataQualityBundle\\Serializer\\Serializer');

        // Looks for all the services tagged "valantic.dataquality.serializer.normalizer" and adds them to the Serializer service
        $normalizers = $this->findAndSortTaggedServices('valantic.dataquality.serializer.normalizer', $container);

        if (empty($normalizers)) {
            throw new RuntimeException('You must tag at least one service as "valantic.dataquality.serializer.normalizer" to use the DataQualityBundle Serializer service');
        }

        // Looks for all the services tagged "valantic.dataquality.serializer.encoders" and adds them to the Serializer service
        $encoders = $this->findAndSortTaggedServices('valantic.dataquality.serializer.encoder', $container);
        if (empty($encoders)) {
            throw new RuntimeException('You must tag at least one service as "valantic.dataquality.serializer.encoder" to use the DataQualityBundle Serializer service');
        }

        $definition->setArguments([
            '$normalizers' => $normalizers,
            '$encoders' => $encoders,
        ]);
    }
}
