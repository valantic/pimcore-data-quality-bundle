<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Tests\Config;

use Valantic\DataQualityBundle\Repository\ConfigurationRepository;
use Valantic\DataQualityBundle\Tests\AbstractTestCase;
use const JSON_THROW_ON_ERROR;

class ConstraintTest extends AbstractTestCase
{
    protected ConfigurationRepository $configurationRepository;

    /** @var class-string */
    protected string $className = 'Pimcore\Model\DataObject\Customer';
    protected string $attributeName = 'name';
    protected string $constraintName = 'NotBlank';
    protected int $constraintParams = 3;

    /** @var class-string */
    protected string $classNameOther = 'OtherNamespace\\OtherClass';
    protected string $attributeNameOther = 'other.attribute';
    protected string $constraintNameOther = 'Custom\\OtherConstraint';
    protected array $constraintParamsOther = ['arg1' => true, 'arg2' => 'yes', 3, null];

    /** @var class-string */
    protected string $classNameConfigured = 'Pimcore\Model\DataObject\Product';

    protected function setUp(): void
    {
        $this->configurationRepository = $this->getConfigurationRepository();
    }

    public function testReadMissingConfig(): void
    {
        $configurationRepository = $this->getConfigurationRepository(self::CONFIG_EMPTY);
        $this->assertIsArray($configurationRepository->getConfiguredClasses());
        $this->assertCount(0, $configurationRepository->getConfiguredClasses());
    }

    public function testReadClassesAreConfigured(): void
    {
        $this->assertIsArray($this->configurationRepository->getConfiguredClasses());
        $this->assertCount(3, $this->configurationRepository->getConfiguredClasses());

        foreach ($this->configurationRepository->getConfiguredClasses() as $configuredClass) {
            $this->assertTrue($this->configurationRepository->isClassConfigured($configuredClass));
        }
    }

    public function testReadClassKeys(): void
    {
        $this->assertTrue($this->configurationRepository->isClassConfigured($this->classNameConfigured));

        foreach ($this->configurationRepository->getConfiguredAttributes($this->classNameConfigured) as $attribute) {
            $this->assertTrue($this->configurationRepository->isAttributeConfigured($this->classNameConfigured, $attribute));

            $this->assertIsArray($this->configurationRepository->getRulesForAttribute($this->classNameConfigured, $attribute));
        }
    }

    public function testReadMissingClass(): void
    {
        $this->assertSame([], $this->configurationRepository->getForClass($this->classNameOther));
    }

    public function testWriteToMissingConfigFile(): void
    {
        $this->configurationRepository->addClassAttribute($this->className, $this->attributeName);
        $this->assertTrue($this->configurationRepository->isAttributeConfigured($this->className, $this->attributeName));
    }

    public function testWriteNote(): void
    {
        $configurationRepository = $this->getConfigurationRepository(self::CONFIG_EMPTY);
        $this->assertNull($configurationRepository->getNoteForAttribute($this->className, $this->attributeName));

        $configurationRepository->modifyNote($this->className, $this->attributeName, 'lorem');
        $this->assertSame('lorem', $configurationRepository->getNoteForAttribute($this->className, $this->attributeName));

        $configurationRepository->modifyNote($this->className, $this->attributeName, 'ipsum');
        $this->assertSame('ipsum', $configurationRepository->getNoteForAttribute($this->className, $this->attributeName));

        $configurationRepository->deleteNote($this->className, $this->attributeName);
        $configurationRepository->deleteNote($this->className, $this->attributeName);
        $this->assertNull($configurationRepository->getNoteForAttribute($this->className, $this->attributeName));

        $configurationRepository->deleteNote($this->className, $this->attributeNameOther);
        $this->assertNull($configurationRepository->getNoteForAttribute($this->className, $this->attributeNameOther));

        $this->assertNull($configurationRepository->getNoteForAttribute($this->classNameOther, $this->attributeName));
        $configurationRepository->deleteNote($this->classNameOther, $this->attributeName);
        $this->assertNull($configurationRepository->getNoteForAttribute($this->classNameOther, $this->attributeName));
    }

    public function testWriteDoubleAdd(): void
    {
        $this->configurationRepository->addClassAttribute($this->className, $this->attributeName);
        $this->configurationRepository->addClassAttribute($this->className, $this->attributeName);
        $this->assertTrue($this->configurationRepository->isAttributeConfigured($this->className, $this->attributeName));
    }

    public function testDeleteEntry(): void
    {
        $this->configurationRepository->addClassAttribute($this->className, $this->attributeName);
        $this->assertTrue($this->configurationRepository->isClassConfigured($this->className));
        $this->assertTrue($this->configurationRepository->isAttributeConfigured($this->className, $this->attributeName));

        $this->configurationRepository->deleteClassAttribute($this->className, $this->attributeName);

        $this->assertFalse($this->configurationRepository->isAttributeConfigured($this->className, $this->attributeName));
    }

    public function testSimpleRule(): void
    {
        $configurationRepository = $this->getConfigurationRepository(self::CONFIG_EMPTY);
        $this->assertCount(0, $configurationRepository->getRulesForAttribute($this->className, $this->attributeName));

        $configurationRepository->modifyRule($this->className, $this->attributeName, $this->constraintName);

        $this->assertCount(1, $configurationRepository->getRulesForAttribute($this->className, $this->attributeName));

        $this->assertNull($configurationRepository->getRulesForAttribute($this->className, $this->attributeName)[$this->constraintName]);
    }

    public function testRuleEmptyStringParams(): void
    {
        $configurationRepository = $this->getConfigurationRepository(self::CONFIG_EMPTY);
        $configurationRepository->modifyRule($this->className, $this->attributeName, $this->constraintName, '');
        $this->assertEmpty($configurationRepository->getRulesForAttribute($this->className, $this->attributeName)[$this->constraintName]);
    }

    public function testRuleScalarParams(): void
    {
        $this->configurationRepository->modifyRule($this->className, $this->attributeName, $this->constraintName, (string) $this->constraintParams);
        $this->assertSame($this->constraintParams, $this->configurationRepository->getRulesForAttribute($this->className, $this->attributeName)[$this->constraintName]);
    }

    public function testConstraintArrayParams(): void
    {
        $this->configurationRepository->modifyRule($this->className, $this->attributeName, $this->constraintNameOther, json_encode($this->constraintParamsOther, JSON_THROW_ON_ERROR));
        $this->assertSame($this->constraintParamsOther, $this->configurationRepository->getRulesForAttribute($this->className, $this->attributeName)[$this->constraintNameOther]);
    }

    public function testMultipleRules(): void
    {
        $configurationRepository = $this->getConfigurationRepository(self::CONFIG_EMPTY);

        $this->assertCount(0, $configurationRepository->getRulesForAttribute($this->className, $this->attributeName));
        $this->assertCount(0, $configurationRepository->getRulesForAttribute($this->className, $this->attributeNameOther));

        $configurationRepository->modifyRule($this->className, $this->attributeName, $this->constraintName, json_encode($this->constraintParams, JSON_THROW_ON_ERROR));
        $configurationRepository->modifyRule($this->className, $this->attributeName, $this->constraintNameOther, json_encode($this->constraintParamsOther, JSON_THROW_ON_ERROR));

        $configurationRepository->modifyRule($this->className, $this->attributeNameOther, $this->constraintName, json_encode($this->constraintParams, JSON_THROW_ON_ERROR));
        $configurationRepository->modifyRule($this->className, $this->attributeNameOther, $this->constraintNameOther, json_encode($this->constraintParamsOther, JSON_THROW_ON_ERROR));

        $this->assertCount(2, $configurationRepository->getRulesForAttribute($this->className, $this->attributeName));
        $this->assertCount(2, $configurationRepository->getRulesForAttribute($this->className, $this->attributeNameOther));

        $this->assertArrayHasKey($this->constraintName, $configurationRepository->getRulesForAttribute($this->className, $this->attributeName));
        $this->assertArrayHasKey($this->constraintNameOther, $configurationRepository->getRulesForAttribute($this->className, $this->attributeName));

        $this->assertArrayHasKey($this->constraintName, $configurationRepository->getRulesForAttribute($this->className, $this->attributeNameOther));
        $this->assertArrayHasKey($this->constraintNameOther, $configurationRepository->getRulesForAttribute($this->className, $this->attributeNameOther));

        $configurationRepository->persist();
    }

    public function testDeleteRules(): void
    {
        $configurationRepository = $this->getConfigurationRepository(self::CONFIG_EMPTY);

        $this->assertCount(0, $configurationRepository->getRulesForAttribute($this->className, $this->attributeName));

        $configurationRepository->modifyRule($this->className, $this->attributeName, $this->constraintName, json_encode($this->constraintParams, JSON_THROW_ON_ERROR));
        $configurationRepository->modifyRule($this->className, $this->attributeName, $this->constraintNameOther, json_encode($this->constraintParamsOther, JSON_THROW_ON_ERROR));

        $this->assertArrayHasKey($this->constraintName, $configurationRepository->getRulesForAttribute($this->className, $this->attributeName));
        $this->assertArrayHasKey($this->constraintNameOther, $configurationRepository->getRulesForAttribute($this->className, $this->attributeName));
        $this->assertCount(2, $configurationRepository->getRulesForAttribute($this->className, $this->attributeName));

        $configurationRepository->deleteRule($this->className, $this->attributeName, $this->constraintName);
        $configurationRepository->deleteRule($this->className, $this->attributeName, $this->constraintNameOther);

        $this->assertArrayNotHasKey($this->constraintName, $configurationRepository->getRulesForAttribute($this->className, $this->attributeName));
        $this->assertArrayNotHasKey($this->constraintNameOther, $configurationRepository->getRulesForAttribute($this->className, $this->attributeName));
        $this->assertCount(0, $configurationRepository->getRulesForAttribute($this->className, $this->attributeName));
    }
}
