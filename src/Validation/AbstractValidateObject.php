<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation;

use Pimcore\Localization\LocaleService;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Tool;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Valantic\DataQualityBundle\Model\AttributeScore;
use Valantic\DataQualityBundle\Model\ObjectScore;
use Valantic\DataQualityBundle\Repository\ConfigurationRepository;
use Valantic\DataQualityBundle\Repository\DataObjectConfigRepository;
use Valantic\DataQualityBundle\Repository\DataObjectRepository;
use Valantic\DataQualityBundle\Service\UserSettingsService;
use Valantic\DataQualityBundle\Service\Formatters\PercentageFormatter;
use Valantic\DataQualityBundle\Service\Information\AbstractDefinitionInformation;
use Valantic\DataQualityBundle\Validation\DataObject\Attributes\AbstractAttribute;
use Valantic\DataQualityBundle\Validation\DataObject\Attributes\Attribute;
use Valantic\DataQualityBundle\Validation\DataObject\Attributes\LocalizedAttribute;

abstract class AbstractValidateObject implements ValidatableInterface, ColorableInterface, PassFailInterface, ScorableInterface
{
    use ColorScoreTrait;
    protected Concrete $obj;
    protected ?array $groups = null;
    protected array $validationConfig;
    protected bool $cacheScores = true;

    /**
     * Validators used for this object.
     *
     * @var AbstractAttribute[]
     */
    protected array $validators = [];
    protected AbstractDefinitionInformation $classInformation;
    protected array $skippedConstraints = [];
    protected ?bool $ignoreFallbackLanguage = null;
    protected array $allowedLocales = [];

    /**
     * Validate an object and all its attributes.
     */
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected ContainerInterface $container,
        protected ConfigurationRepository $configurationRepository,
        protected DataObjectConfigRepository $dataObjectConfigRepository,
        protected TagAwareCacheInterface $cache,
        protected DataObjectRepository $dataObjectRepository,
        protected PercentageFormatter $percentageFormatter,
        protected UserSettingsService $settingsService,
        protected Security $securityService,
        protected NormalizerInterface $normalizer,
        protected LocaleService $localeService,
        protected Attribute $simpleAttribute,
        protected LocalizedAttribute $localizedAttribute,
    ) {
        $this->allowedLocales = Tool::getValidLanguages();
    }

    /**
     * Mark a constraint validator as skipped (useful to prevent recursion/cycles for relations).
     */
    public function addSkippedConstraint(string $constraintValidator): void
    {
        $this->skippedConstraints[] = $constraintValidator;
    }

    /**
     * Calculates the scores for the individual attributes.
     *
     * @return array<string,AttributeScore>
     */
    public function calculateScores(): array
    {
        $attributeScores = [];
        foreach ($this->validators as $attribute => $validator) {
            $score = new AttributeScore(value: $validator->value(), passes: $validator->passes());

            if ($validator instanceof ScorableInterface) {
                $score->setScore($validator->score());
            }

            if ($validator instanceof MultiScorableInterface) {
                $score->setScores($validator->scores());
            }

            if ($validator instanceof ColorableInterface) {
                $score->setColor($validator->color());
            }

            if ($validator instanceof MultiColorableInterface) {
                $score->setColors($validator->colors());
            }

            $attributeScores[$attribute] = $score;
        }

        return $attributeScores;
    }

    /**
     * Get the scores for the whole object.
     */
    public function objectScore(): ObjectScore
    {
        return new ObjectScore(
            $this->color(),
            $this->score(),
            $this instanceof MultiScorableInterface ? $this->scores() : [],
            $this->passes(),
            $this instanceof MultiColorableInterface ? $this->colors() : [],
        );
    }

    public function setIgnoreFallbackLanguage(bool $ignoreFallbackLanguage): void
    {
        $this->ignoreFallbackLanguage = $ignoreFallbackLanguage;
    }

    public function getIgnoreFallbackLanguage(): bool
    {
        if ($this->ignoreFallbackLanguage !== null) {
            return $this->ignoreFallbackLanguage;
        }

        return $this->ignoreFallbackLanguage = $this->dataObjectConfigRepository->get($this->obj::class)->getIgnoreFallbackLanguage($this->obj);
    }

    public function passes(): bool
    {
        return $this->score() === 1.0;
    }

    public function setAllowedLocales(array $locales): void
    {
        $this->allowedLocales = array_intersect($locales, Tool::getValidLanguages());
    }

    public function setCacheScores(bool $cacheScore): void
    {
        $this->cacheScores = $cacheScore;
    }

    public function setGroups(array $groups): void
    {
        $this->groups = $groups;
    }

    public function getGroups(): array
    {
        if ($this->groups !== null) {
            return $this->groups;
        }

        $groups = [];
        foreach ($this->configurationRepository->getConfiguredAttributes($this->obj::class) as $attribute) {
            foreach ($this->configurationRepository->getRulesForAttribute($this->obj::class, $attribute) as $rule) {
                foreach ($rule['groups'] ?? [] as $group) {
                    $groups[] = $group;
                }
            }
        }

        return $this->groups = $groups;
    }

    /**
     * Set the object to validate.
     *
     * @param Concrete $obj the object to validate
     */
    abstract public function setObject(Concrete $obj): void;

    /**
     * Returns a list of all attributes that can be validated i.e. that exist and are configured.
     */
    protected function getValidatableAttributes(): array
    {
        return array_intersect($this->getAttributesInConfig(), $this->getAttributesInObject());
    }

    /**
     * Returns a list of configured attributes.
     */
    protected function getAttributesInConfig(): array
    {
        return array_keys($this->validationConfig);
    }

    /**
     * Returns a list of attributes present in the object.
     */
    protected function getAttributesInObject(): array
    {
        return array_keys($this->classInformation->getAllAttributes());
    }
}
