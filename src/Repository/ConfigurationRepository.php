<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Repository;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;
use Valantic\DataQualityBundle\DependencyInjection\Configuration;
use Valantic\DataQualityBundle\Enum\ThresholdEnum;
use Valantic\DataQualityBundle\Service\Information\DefinitionInformationFactory;
use Throwable;
use Valantic\DataQualityBundle\Shared\SafeArray;

use const JSON_THROW_ON_ERROR;

class ConfigurationRepository
{
    use SafeArray;
    public const CONTAINER_TAG = 'valantic.pimcore_data_quality.config';
    private array $config;
    private bool $isConfigDirty = false;

    public function __construct(
        protected ParameterBagInterface $parameterBag,
        protected DefinitionInformationFactory $definitionInformationFactory,
    ) {
        $config = $this->parameterBag->get(self::CONTAINER_TAG);

        $this->setConfig(is_array($config) ? $config : throw new InvalidArgumentException());
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

    public function getConfigFile(): ?string
    {
        return $this->getConfig()[Configuration::CONFIG_KEY_CONFIG_FILE];
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
                throw new RuntimeException(sprintf('Could not look up %s.', $className));
            }
        } catch (Throwable) {
            throw new InvalidArgumentException();
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
    ): void {
        $config = $this->getConfig();
        $config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_CONFIG] ??= [];
        $config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_CONFIG][Configuration::CONFIG_KEY_CLASSES_CONFIG_LOCALES] = $locales;
        $config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_CONFIG][Configuration::CONFIG_KEY_CLASSES_CONFIG_THRESHOLDS] ??= [];
        $config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_CONFIG][Configuration::CONFIG_KEY_CLASSES_CONFIG_THRESHOLDS][ThresholdEnum::THRESHOLD_GREEN->value] = $thresholdGreen / 100;
        $config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_CONFIG][Configuration::CONFIG_KEY_CLASSES_CONFIG_THRESHOLDS][ThresholdEnum::THRESHOLD_ORANGE->value] = $thresholdOrange / 100;
        $config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_CONFIG][Configuration::CONFIG_KEY_CLASSES_CONFIG_NESTING_LIMIT] = $nestingLimit;
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
        unset($config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_CONFIG]);
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
            $paramsParsed = json_decode($params ?: '', true, 512, JSON_THROW_ON_ERROR);
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
     * Deletes a class-attribute note.
     *
     * @param class-string $className
     */
    public function deleteNote(string $className, string $attributeName): void
    {
        if (!$this->isClassConfigured($className) || !$this->isAttributeConfigured($className, $attributeName)) {
            return;
        }

        $config = $this->getConfig();
        $config[Configuration::CONFIG_KEY_CLASSES][$className][Configuration::CONFIG_KEY_CLASSES_ATTRIBUTES][$attributeName][Configuration::CONFIG_KEY_CLASSES_ATTRIBUTES_NOTE] = null;
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

    private function getConfig(): array
    {
        return $this->config;
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
        return $this->getConfig()[Configuration::CONFIG_KEY_CLASSES];
    }
}
