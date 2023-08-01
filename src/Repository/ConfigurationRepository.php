<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Repository;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;
use Valantic\DataQualityBundle\DependencyInjection\Configuration;
use Valantic\DataQualityBundle\Enum\ThresholdEnum;
use Valantic\DataQualityBundle\Service\Information\DefinitionInformationFactory;
use Throwable;
use Valantic\DataQualityBundle\Shared\SafeArray;

class ConfigurationRepository
{
    use SafeArray;
    public const CONTAINER_TAG = 'valantic.pimcore_data_quality.config';
    protected ?array $config = null;
    protected bool $isConfigDirty = false;

    public function __construct(
        protected ParameterBagInterface $parameterBag,
        protected DefinitionInformationFactory $definitionInformationFactory,
    ) {
    }

    public function persist(): void
    {
        if (!$this->isConfigDirty) {
            return;
        }

        $yaml = Yaml::dump([Configuration::CONFIG_KEY => $this->getConfig()], Yaml::DUMP_OBJECT_AS_MAP);

        if (empty($this->getConfigFile())) {
            return;
        }

        file_put_contents($this->getConfigFile(), $yaml);
    }

    /**
     * Get the list of classes than can be validated i.e. are configured.
     *
     * @return array<int,class-string>
     */
    public function getConfiguredClasses(): array
    {
        return array_keys($this->classes());
    }

    /**
     * Checks whether $className is configured.
     *
     * @param class-string $className
     */
    public function isClassConfigured(string $className): bool
    {
        return in_array($className, $this->getConfiguredClasses(), true);
    }

    /**
     * Given a class name, return the corresponding config.
     *
     * @param class-string $className
     *
     * @return array{attributes:array,config:array}
     */
    public function getForClass(string $className): array
    {
        try {
            $classInformation = $this->definitionInformationFactory->make($className);
            $className = $classInformation->getName();
            if (empty($className)) {
                throw new \RuntimeException(sprintf('Could not look up %s.', $className));
            }
        } catch (Throwable) {
            throw new \InvalidArgumentException();
        }

        if (!$this->isClassConfigured($className)) {
            return [];
        }

        return $this->safeArray($this->classes(), $className);
    }

    /**
     * @param class-string $className
     */
    public function getAttributesForClass(string $className): array
    {
        return $this->getForClass($className)[Configuration::CONFIG_KEY_CLASSES_ATTRIBUTES] ?? [];
    }

    /**
     * @param class-string $className
     */
    public function getConfigForClass(string $className): array
    {
        return $this->getForClass($className)[Configuration::CONFIG_KEY_CLASSES_CONFIG] ?? [];
    }

    /**
     * @param class-string $className
     *
     * @return array<int,string>
     */
    public function getConfiguredLocales(string $className): array
    {
        return $this->getConfigForClass($className)[Configuration::CONFIG_KEY_CLASSES_CONFIG_LOCALES] ?? [];
    }

    /**
     * @param class-string $className
     *
     * @return array<string,float>
     */
    public function getConfiguredThresholds(string $className): array
    {
        return $this->getConfigForClass($className)[Configuration::CONFIG_KEY_CLASSES_CONFIG_THRESHOLDS] ?? [];
    }

    /**
     * @param class-string $className
     */
    public function getConfiguredThreshold(string $className, ThresholdEnum $thresholdEnum): float
    {
        return $this->getConfiguredThresholds($className)[$thresholdEnum->value] ?? $thresholdEnum->defaultValue();
    }

    /**
     * @param class-string $className
     */
    public function getConfiguredNestingLimit(string $className): int
    {
        return $this->getConfigForClass($className)[Configuration::CONFIG_KEY_CLASSES_CONFIG_NESTING_LIMIT] ?? Configuration::CONFIG_VALUE_CLASSES_CONFIG_NESTING_LIMIT;
    }

    /**
     * @param class-string $className
     */
    public function getIgnoreFallbackLanguage(string $className): bool
    {
        return (bool) $this->getConfigForClass($className)[Configuration::CONFIG_KEY_CLASSES_CONFIG_IGNORE_FALLBACK_LANGUAGE];
    }

    /**
     * @param class-string $className
     */
    public function getDisableTabOnObject(string $className): bool
    {
        return (bool) $this->getConfigForClass($className)[Configuration::CONFIG_KEY_CLASSES_CONFIG_DISABLE_TAB_ON_OBJECT];
    }

    /**
     * @param class-string $className
     */
    public function getScoreFieldName(string $className): ?string
    {
        return $this->getConfigForClass($className)[Configuration::CONFIG_KEY_CLASSES_CONFIG_SCORE_FIELD_NAME] ?? null;
    }

    /**
     * Given a class name, return the corresponding rules for $attribute.
     *
     * @param class-string $className Base name or ::class
     */
    public function getRulesForAttribute(string $className, string $attribute): array
    {
        return $this->getForAttribute($className, $attribute)[Configuration::CONFIG_KEY_CLASSES_ATTRIBUTES_RULES] ?? [];
    }

    /**
     * Given a class name, return the corresponding note for $attribute.
     *
     * @param class-string $className Base name or ::class
     */
    public function getNoteForAttribute(string $className, string $attribute): ?string
    {
        return $this->getForAttribute($className, $attribute)[Configuration::CONFIG_KEY_CLASSES_ATTRIBUTES_NOTE] ?? null;
    }

    /**
     * Get the list of attributes of a class than can be validated i.e. are configured.
     *
     * @param class-string $className
     */
    public function getConfiguredAttributes(string $className): array
    {
        return array_keys($this->getAttributesForClass($className));
    }

    /**
     * Checks whether $attributeName in $className is configured.
     *
     * @param class-string $className
     */
    public function isAttributeConfigured(string $className, string $attributeName): bool
    {
        return in_array($attributeName, $this->getConfiguredAttributes($className), true);
    }

    /**
     * Updates (or creates) a config entry for $className.
     *
     * @param class-string $className
     */
    public function setClassConfig(
        string $className,
        array $locales = [],
        int $thresholdGreen = 0,
        int $thresholdOrange = 0,
        int $nestingLimit = 1,
        bool $ignoreFallbackLanguage = false,
        bool $disableTabOnObject = false,
    ): void {
        $config = $this->getConfig();
        $config[Configuration::CONFIG_KEY_CLASSES] ??= [];
        $config[Configuration::CONFIG_KEY_CLASSES][$className] ??= [];
        $config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_CONFIG] ??= [];
        $config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_CONFIG][Configuration::CONFIG_KEY_CLASSES_CONFIG_LOCALES] = $locales;
        $config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_CONFIG][Configuration::CONFIG_KEY_CLASSES_CONFIG_THRESHOLDS] ??= [];
        $config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_CONFIG][Configuration::CONFIG_KEY_CLASSES_CONFIG_THRESHOLDS][ThresholdEnum::green()->value] = $thresholdGreen / 100;
        $config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_CONFIG][Configuration::CONFIG_KEY_CLASSES_CONFIG_THRESHOLDS][ThresholdEnum::orange()->value] = $thresholdOrange / 100;
        $config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_CONFIG][Configuration::CONFIG_KEY_CLASSES_CONFIG_NESTING_LIMIT] = $nestingLimit;
        $config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_CONFIG][Configuration::CONFIG_KEY_CLASSES_CONFIG_IGNORE_FALLBACK_LANGUAGE] = $ignoreFallbackLanguage;
        $config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_CONFIG][Configuration::CONFIG_KEY_CLASSES_CONFIG_DISABLE_TAB_ON_OBJECT] = $disableTabOnObject;

        $this->setConfig($config);
    }

    /**
     * Delete the config entry for $className.
     *
     * @param class-string $className
     */
    public function deleteClassConfig(string $className): void
    {
        if (!$this->isClassConfigured($className)) {
            return;
        }

        $config = $this->getConfig();

        unset($config[Configuration::CONFIG_KEY_CLASSES][$className]);

        $this->setConfig($config);
    }

    /**
     * Adds a new config entry for a class-attribute combination if it does not yet exist.
     *
     * @param class-string $className
     */
    public function addClassAttribute(string $className, string $attributeName): void
    {
        $config = $this->getConfig();
        $config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_ATTRIBUTES][$attributeName] ??= [];
        $this->setConfig($config);
    }

    /**
     * Adds a new config entry for a class-attribute combination if it does not yet exist.
     *
     * @param class-string $className
     */
    public function deleteClassAttribute(string $className, string $attributeName): void
    {
        if (!$this->isClassConfigured($className) || !$this->isAttributeConfigured($className, $attributeName)) {
            return;
        }

        $config = $this->getConfig();
        unset($config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_ATTRIBUTES][$attributeName]);
        $this->setConfig($config);
    }

    /**
     * Adds a new config entry or edits an existing one for a class-attribute rule if it does not yet exist.
     *
     * @param class-string $className
     */
    public function modifyRule(string $className, string $attributeName, string $constraint, ?string $params = null): void
    {
        try {
            $paramsParsed = json_decode($params ?: '', true, 512, \JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            $paramsParsed = $params;
        }

        if ($paramsParsed === '') {
            $paramsParsed = null;
        }

        $config = $this->getConfig();
        $config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_ATTRIBUTES][$attributeName][Configuration::CONFIG_KEY_CLASSES_ATTRIBUTES_RULES] ??= [];
        $config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_ATTRIBUTES][$attributeName][Configuration::CONFIG_KEY_CLASSES_ATTRIBUTES_RULES][$constraint] = $paramsParsed;
        $this->setConfig($config);
    }

    /**
     * Deletes a class-attribute rule.
     *
     * @param class-string $className
     */
    public function deleteRule(string $className, string $attributeName, string $constraint): void
    {
        if (!$this->isClassConfigured($className) || !$this->isAttributeConfigured($className, $attributeName)) {
            return;
        }

        $config = $this->getConfig();
        unset($config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_ATTRIBUTES][$attributeName][Configuration::CONFIG_KEY_CLASSES_ATTRIBUTES_RULES][$constraint]);
        $this->setConfig($config);
    }

    /**
     * Adds a new config entry or edits an existing one for a class-attribute note if it does not yet exist.
     *
     * @param class-string $className
     */
    public function modifyNote(string $className, string $attributeName, ?string $note = null): void
    {
        $config = $this->getConfig();
        $config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_ATTRIBUTES][$attributeName][Configuration::CONFIG_KEY_CLASSES_ATTRIBUTES_NOTE] = $note;
        $this->setConfig($config);
    }

    /**
     * Given a class name, return the corresponding config for $attribute.
     *
     * @param class-string $className Base name or ::class
     */
    protected function getForAttribute(string $className, string $attribute): array
    {
        return $this->getAttributesForClass($className)[$attribute] ?? [];
    }

    private function getConfigFile(): ?string
    {
        $pathsToCheck = [
            Configuration::CONFIGURATION_DIRECTORY,
            PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY,
            PIMCORE_CONFIGURATION_DIRECTORY,
        ];

        foreach ($pathsToCheck as $path) {
            $file = $path . '/data_quality.yaml';

            if (file_exists($file)) {
                return $file;
            }
        }

        return null;
    }

    private function getConfig(): array
    {
        if ($this->config === null) {
            /** @var array $containerConfig */
            $containerConfig = $this->parameterBag->get(self::CONTAINER_TAG);

            $additionalConfig = [];

            if ($this->getConfigFile() !== null) {
                $systemConfig = Yaml::parseFile($this->getConfigFile())[Configuration::CONFIG_KEY] ?? [];

                $configuration = new Configuration();
                $configTree = $configuration->getConfigTreeBuilder()->buildTree();

                $currentConfig = $configTree->normalize($systemConfig);
                $additionalConfig = $configTree->finalize($currentConfig);
            }

            $this->config = array_replace_recursive($containerConfig, $additionalConfig);
        }

        return $this->config ?: [];
    }

    private function setConfig(array $config): void
    {
        $this->config = $config;
        $this->isConfigDirty = true;
    }

    /**
     * @return array<class-string,mixed>
     */
    private function classes(): array
    {
        return $this->getConfig()[Configuration::CONFIG_KEY_CLASSES] ?? [];
    }
}
