<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Valantic\DataQualityBundle\Enum\ThresholdEnum;

class Configuration implements ConfigurationInterface
{
    public const CONFIG_KEY = 'valantic_data_quality';
    public const CONFIG_KEY_CLASSES = 'classes';
    public const CONFIG_KEY_CLASSES_CONFIG = 'config';
    public const CONFIG_KEY_CLASSES_CONFIG_LOCALES = 'locales';
    public const CONFIG_KEY_CLASSES_CONFIG_NESTING_LIMIT = 'nesting_limit';
    public const CONFIG_KEY_CLASSES_CONFIG_THRESHOLDS = 'thresholds';
    public const CONFIG_KEY_CLASSES_ATTRIBUTES = 'attributes';
    public const CONFIG_KEY_CLASSES_ATTRIBUTES_RULES = 'rules';
    public const CONFIG_KEY_CLASSES_ATTRIBUTES_NOTE = 'note';
    public const CONFIG_KEY_CONFIG_FILE = 'config_file';
    public const CONFIG_VALUE_CLASSES_CONFIG_NESTING_LIMIT = 1;
    protected const SYMFONY_CONSTRAINTS_NAMESPACE = 'Symfony\\Component\\Validator\\Constraints\\';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        return (new TreeBuilder(self::CONFIG_KEY))
            ->getRootNode()
            ->children()
            ->scalarNode(self::CONFIG_KEY_CONFIG_FILE)
            ->info('File where the bundle settings are stored. If empty, changes made via the UI/API are not persisted.')
            ->example('%kernel.project_dir%/config/data_quality.yaml')
            ->defaultNull()
            ->validate()
            ->ifTrue(fn(string $path) => $path !== null && !file_exists($path))
            ->thenInvalid('Value must be empty or point to a valid file')
            ->end()
            ->end()
            ->arrayNode(self::CONFIG_KEY_CLASSES)
            ->arrayPrototype()
            ->info('One entry per data object class defining constraints and config. Key is the FQN of the class.')
            ->children()
            ->append($this->buildAttributesNode())
            ->append($this->buildConfigNode())
            ->end()
            ->end()
            ->end()
            ->end()
            ->end();
    }

    protected function buildAttributesNode(): ArrayNodeDefinition
    {
        return (new TreeBuilder(self::CONFIG_KEY_CLASSES_ATTRIBUTES))->getRootNode()
            ->info('One entry represents an attribute (or relation.attribute)')
            ->arrayPrototype()
            ->children()
            ->scalarNode(self::CONFIG_KEY_CLASSES_ATTRIBUTES_NOTE)->info('Optional note about this attribute')->end()
            ->variableNode(self::CONFIG_KEY_CLASSES_ATTRIBUTES_RULES)
            ->info('An array of Symfony Validator constraints for this attribute')
            ->defaultValue([])
            ->validate()
            ->ifTrue(
                fn(array $rules): bool => array_reduce(
                    array_keys($rules),
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
            ->info('Array of locales for which the values are checked in this class')
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode(self::CONFIG_KEY_CLASSES_CONFIG_THRESHOLDS)
            ->info('The thresholds where an object turns from red to orange or from orange to green')
            ->addDefaultsIfNotSet()
            ->children()
            ->floatNode(ThresholdEnum::green()->value)->defaultValue(ThresholdEnum::green()->defaultValue())->end()
            ->floatNode(ThresholdEnum::orange()->value)->defaultValue(ThresholdEnum::orange()->defaultValue())->end()
            ->end()
            ->end()
            ->integerNode(self::CONFIG_KEY_CLASSES_CONFIG_NESTING_LIMIT)
            ->info('The maximum number of levels/relations are resolved when validating an attribute. Useful to prevent circular references.')
            ->min(0)
            ->defaultValue(self::CONFIG_VALUE_CLASSES_CONFIG_NESTING_LIMIT)
            ->end()
            ->end();
    }
}
