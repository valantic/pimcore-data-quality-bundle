<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Tests\Config;

use Valantic\DataQualityBundle\Enum\ThresholdEnum;
use Valantic\DataQualityBundle\Repository\ConfigurationRepository;
use Valantic\DataQualityBundle\Tests\AbstractTestCase;

class MetaTest extends AbstractTestCase
{
    protected ConfigurationRepository$configurationRepository;

    /** @var class-string */
    protected string $className = 'SomeClass';

    /** @var class-string */
    protected string $classNameConfigured = 'Pimcore\Model\DataObject\Product';

    protected function setUp(): void
    {
        $this->configurationRepository = $this->getConfigurationRepository();
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

        $this->assertIsInt($this->configurationRepository->getConfiguredNestingLimit($this->classNameConfigured));
        $this->assertIsArray($this->configurationRepository->getConfiguredLocales($this->classNameConfigured));
        $this->assertArrayHasKey(ThresholdEnum::THRESHOLD_ORANGE->value, $this->configurationRepository->getConfiguredThresholds($this->classNameConfigured));
        $this->assertArrayHasKey(ThresholdEnum::THRESHOLD_GREEN->value, $this->configurationRepository->getConfiguredThresholds($this->classNameConfigured));
    }

    public function testReadMissingClass(): void
    {
        $this->assertSame([], $this->configurationRepository->getForClass('UnknownClass'));
    }

    public function testWriteToMissingConfigFile(): void
    {
        $this->configurationRepository->setClassConfig($this->className, ['a', 'b'], 80, 50);
        $this->assertSame([
            'locales' => [
                'a',
                'b',
            ],
            'thresholds' => ['green' => 0.8, 'orange' => 0.5],
            'nesting_limit' => 1,
        ], $this->configurationRepository->getConfigForClass($this->className));
    }

    public function testWriteDoesNotAffectOtherEntries(): void
    {
        $this->assertCount(3, $this->configurationRepository->getConfiguredClasses());

        $this->configurationRepository->setClassConfig($this->classNameConfigured, ['a', 'b'], 80, 50);

        $this->assertCount(3, $this->configurationRepository->getConfiguredClasses());
    }

    public function testWriteUpdates(): void
    {
        $this->configurationRepository->setClassConfig($this->className, [], 80, 0, 1);
        $this->assertSame(0.8, $this->configurationRepository->getConfiguredThreshold($this->className, ThresholdEnum::green()));

        $this->configurationRepository->setClassConfig($this->className, [], 70, 0, 1);
        $this->configurationRepository->setClassConfig($this->className, [], 50, 70, 1);
        $this->assertSame(0.5, $this->configurationRepository->getConfiguredThreshold($this->className, ThresholdEnum::green()));

        $this->configurationRepository->setClassConfig($this->className, [], 0, 0, 1);
        $this->assertSame([], $this->configurationRepository->getConfiguredLocales($this->className));

        $this->configurationRepository->setClassConfig($this->className, ['a'], 0, 0, 1);
        $this->assertSame(['a'], $this->configurationRepository->getConfiguredLocales($this->className));

        $this->configurationRepository->setClassConfig($this->className, ['b'], 0, 0, 1);
        $this->assertSame(['b'], $this->configurationRepository->getConfiguredLocales($this->className));

        $this->configurationRepository->setClassConfig($this->className, ['b'], 0, 0, 1);
        $this->assertSame(1, $this->configurationRepository->getConfiguredNestingLimit($this->className));

        $this->configurationRepository->setClassConfig($this->className, ['b'], 0, 0, 3);
        $this->assertSame(3, $this->configurationRepository->getConfiguredNestingLimit($this->className));

        $this->configurationRepository->setClassConfig($this->className, ['b'], 0, 0, 0);
        $this->assertSame(0, $this->configurationRepository->getConfiguredNestingLimit($this->className));
    }

    public function testDeleteEntry(): void
    {
        $this->configurationRepository->setClassConfig($this->className, [], 80, 0);
        $this->assertNotEmpty($this->configurationRepository->getConfigForClass($this->className));

        $this->configurationRepository->deleteClassConfig($this->className);

        $this->assertEmpty($this->configurationRepository->getConfigForClass($this->className));
    }

    public function testDeleteUnknownEntry(): void
    {
        $this->assertFalse($this->configurationRepository->isClassConfigured($this->className));

        $this->configurationRepository->deleteClassConfig($this->className);

        $this->assertFalse($this->configurationRepository->isClassConfigured($this->className));
    }
}
