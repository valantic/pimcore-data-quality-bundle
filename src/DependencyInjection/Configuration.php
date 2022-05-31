<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Valantic\DataQualityBundle\Enum\ThresholdEnum;

class Configuration implements ConfigurationInterface
{
    public const CONFIG_KEY_CLASSES = 'classes';
    public const CONFIG_KEY_CLASSES_CONFIG = 'config';
    public const CONFIG_KEY_CLASSES_CONFIG_LOCALES = 'locales';
    public const CONFIG_KEY_CLASSES_CONFIG_NESTING_LIMIT = 'nesting_limit';
    public const CONFIG_KEY_CLASSES_CONFIG_THRESHOLDS = 'thresholds';
    public const CONFIG_KEY_CLASSES_CONSTRAINTS = 'constraints';
    public const CONFIG_KEY_CLASSES_CONSTRAINTS_RULES = 'rules';
    public const CONFIG_KEY_CLASSES_CONSTRAINTS_CONSTRAINT_NOTE = 'note';
    public const CONFIG_VALUE_CLASSES_CONFIG_NESTING_LIMIT = 1;
    protected const SYMFONY_CONSTRAINTS_NAMESPACE = 'Symfony\\Component\\Validator\\Constraints\\';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        return (new TreeBuilder('valantic_data_quality'))
            ->getRootNode()
            ->children()
            ->arrayNode(self::CONFIG_KEY_CLASSES)
            ->arrayPrototype()
            ->children()
            ->append($this->buildConstraintsNode())
            ->append($this->buildConfigNode())
            ->end()
            ->end()
            ->end()
            ->end()
            ->end();
    }

    protected function buildConstraintsNode(): ArrayNodeDefinition
    {
        return (new TreeBuilder(self::CONFIG_KEY_CLASSES_CONSTRAINTS))->getRootNode()
            ->arrayPrototype()
            ->children()
            ->scalarNode(self::CONFIG_KEY_CLASSES_CONSTRAINTS_CONSTRAINT_NOTE)->end()
            ->variableNode(self::CONFIG_KEY_CLASSES_CONSTRAINTS_RULES)
            ->defaultValue([])
            ->validate()
            ->ifTrue(
                fn(array $constraints): bool => array_reduce(
                    array_keys($constraints),
                    fn($carry, $className): bool => $carry || !(class_exists($className) || class_exists(self::SYMFONY_CONSTRAINTS_NAMESPACE . $className)),
                    false
                )
            )
            ->thenInvalid('Invalid constraint class found. The constraint should either be a FQN or a subclass of ' . self::SYMFONY_CONSTRAINTS_NAMESPACE)
            ->end()
            ->end()
            ->end()
            ->end();
    }

    protected function buildConfigNode(): ArrayNodeDefinition
    {
        return (new TreeBuilder(self::CONFIG_KEY_CLASSES_CONFIG))->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode(self::CONFIG_KEY_CLASSES_CONFIG_LOCALES)
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode(self::CONFIG_KEY_CLASSES_CONFIG_THRESHOLDS)
            ->addDefaultsIfNotSet()
            ->children()
            ->floatNode(ThresholdEnum::THRESHOLD_GREEN->value)->defaultValue(ThresholdEnum::THRESHOLD_GREEN->defaultValue())->end()
            ->floatNode(ThresholdEnum::THRESHOLD_ORANGE->value)->defaultValue(ThresholdEnum::THRESHOLD_ORANGE->defaultValue())->end()
            ->end()
            ->end()
            ->integerNode(self::CONFIG_KEY_CLASSES_CONFIG_NESTING_LIMIT)->min(0)->defaultValue(self::CONFIG_VALUE_CLASSES_CONFIG_NESTING_LIMIT)->end()
            ->end();
    }
}
