<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Tests\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Valantic\DataQualityBundle\Controller\MetaConfigController;
use Valantic\DataQualityBundle\Enum\ThresholdEnum;
use Valantic\DataQualityBundle\Repository\ConfigurationRepository;
use Valantic\DataQualityBundle\Service\Locales\LocalesList;
use Valantic\DataQualityBundle\Tests\AbstractTestCase;

use const JSON_THROW_ON_ERROR;

class MetaControllerTest extends AbstractTestCase
{
    /** @var class-string */
    protected string $classNameConfigured = 'Pimcore\Model\DataObject\Product';

    /**
     * @var MetaConfigController
     */
    protected MetaConfigController|MockObject $controller;
    protected ConfigurationRepository $configurationRepository;

    /** @var class-string */
    protected string $className = 'SomeClass';

    protected function setUp(): void
    {
        $this->controller = $this->getMockBuilder(MetaConfigController::class)
            ->onlyMethods(['getClassNames'])
            ->getMock();
        $this->controller
            ->method('getClassNames')
            ->willReturn(['Customer', 'Product', 'Category']);
        $this->controller->setContainer(self::$container);
        $this->configurationRepository = $this->getConfigurationRepository();
    }

    public function testListEmpty(): void
    {
        $configurationRepository = $this->getConfigurationRepository(self::CONFIG_EMPTY);
        $this->controller->setContainer(self::$container);
        $response = $this->controller->listAction(Request::create('/'), $configurationRepository);

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);

        $decoded = json_decode($content, false, 512, JSON_THROW_ON_ERROR);

        $this->assertSame([], $decoded);
    }

    public function testListFull(): void
    {
        $this->controller->setContainer(self::$container);
        $response = $this->controller->listAction(Request::create('/'), $this->configurationRepository);

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(3, $decoded);

        foreach ($decoded as $entry) {
            $this->assertArrayHasKey('classname', $entry);
            $this->assertArrayHasKey('locales', $entry);
            $this->assertArrayHasKey('threshold_green', $entry);
            $this->assertArrayHasKey('threshold_orange', $entry);

            $this->assertIsInt($entry['threshold_green']);
            $this->assertGreaterThanOrEqual(0, $entry['threshold_green']);
            $this->assertLessThanOrEqual(100, $entry['threshold_green']);

            $this->assertIsInt($entry['threshold_orange']);
            $this->assertGreaterThanOrEqual(0, $entry['threshold_orange']);
            $this->assertLessThanOrEqual(100, $entry['threshold_orange']);
        }
    }

    public function testListFiltered(): void
    {
        $response = $this->controller->listAction(
            Request::create('/', 'GET', ['filterText' => 'Product']),
            $this->configurationRepository
        );

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(1, $decoded);

        $this->assertSame($this->classNameConfigured, $decoded[0]['classname']);
    }

    public function testListMatchesConfig(): void
    {
        $this->controller->setContainer(self::$container);
        $response = $this->controller->listAction(Request::create('/', 'GET', ['filterText' => 'Product']), $this->configurationRepository);

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR)[0];

        $this->assertSame($this->configurationRepository->getConfiguredNestingLimit($decoded['classname']), $decoded['nesting_limit']);
        $this->assertSame($this->configurationRepository->getConfiguredLocales($decoded['classname']), $decoded['locales']);
        $this->assertEqualsWithDelta($this->configurationRepository->getConfiguredThreshold($decoded['classname'], ThresholdEnum::green()) * 100, $decoded['threshold_green'], 0.1);
        $this->assertEqualsWithDelta($this->configurationRepository->getConfiguredThreshold($decoded['classname'], ThresholdEnum::orange()) * 100, $decoded['threshold_orange'], 0.1);
    }

    public function testListClasses(): void
    {
        $response = $this->controller->listClassesAction($this->configurationRepository);
        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('classes', $decoded);
        $this->assertCount(3, $decoded['classes']);

        foreach ($decoded['classes'] as $entry) {
            $this->assertArrayHasKey('name', $entry);
        }
    }

    public function testAddMissingData(): void
    {
        $this->assertCount(3, $this->configurationRepository->getConfiguredClasses());
        $response = $this->controller->modifyAction(Request::create('/', 'POST'), $this->configurationRepository);

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(3, $this->configurationRepository->getConfiguredClasses());

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertFalse($decoded['status']);
    }

    public function testAddPartialData(): void
    {
        $this->assertCount(3, $this->configurationRepository->getConfiguredClasses());
        $response = $this->controller->modifyAction(
            Request::create('/', 'POST', ['classname' => $this->className]),
            $this->configurationRepository
        );

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(4, $this->configurationRepository->getConfiguredClasses());
        $this->assertTrue($this->configurationRepository->isClassConfigured($this->className));

        $this->assertSame([], $this->configurationRepository->getConfiguredLocales($this->className));
        $this->assertSame(0.0, $this->configurationRepository->getConfiguredThreshold($this->className, ThresholdEnum::orange()));
        $this->assertSame(0.0, $this->configurationRepository->getConfiguredThreshold($this->className, ThresholdEnum::green()));
        $this->assertSame(1, $this->configurationRepository->getConfiguredNestingLimit($this->className));

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testAddCompleteData(): void
    {
        $this->assertCount(3, $this->configurationRepository->getConfiguredClasses());
        $response = $this->controller->modifyAction(Request::create('/', 'POST', [
            'classname' => $this->className,
            'locales' => ['de', 'en'],
            'threshold_green' => 80,
            'threshold_orange' => 50,
            'nesting_limit' => 2,
        ]), $this->configurationRepository);

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(4, $this->configurationRepository->getConfiguredClasses());
        $this->assertTrue($this->configurationRepository->isClassConfigured($this->className));

        $this->assertSame(['de', 'en'], $this->configurationRepository->getConfiguredLocales($this->className));
        $this->assertSame(0.5, $this->configurationRepository->getConfiguredThreshold($this->className, ThresholdEnum::orange()));
        $this->assertSame(0.8, $this->configurationRepository->getConfiguredThreshold($this->className, ThresholdEnum::green()));
        $this->assertSame(2, $this->configurationRepository->getConfiguredNestingLimit($this->className));

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testDeleteMissingData(): void
    {
        $this->assertCount(3, $this->configurationRepository->getConfiguredClasses());
        $response = $this->controller->deleteAction(Request::create('/', 'POST'), $this->configurationRepository);

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(3, $this->configurationRepository->getConfiguredClasses());

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertFalse($decoded['status']);
    }

    public function testDeleteCompleteData(): void
    {
        $this->assertCount(3, $this->configurationRepository->getConfiguredClasses());
        $response = $this->controller->deleteAction(Request::create('/', 'POST', ['classname' => $this->classNameConfigured]), $this->configurationRepository);

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(3, $this->configurationRepository->getConfiguredClasses());
        $this->assertEmpty($this->configurationRepository->getConfigForClass($this->classNameConfigured));

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testLocalesList(): void
    {
        $locales = ['de', 'en'];

        $localesList = $this->createMock(LocalesList::class);
        $localesList->method('all')->willReturn($locales);

        $response = $this->controller->listLocalesAction($localesList);

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('locales', $decoded);
        $this->assertSameSize($locales, $decoded['locales']);

        foreach ($decoded['locales'] as $entry) {
            $this->assertArrayHasKey('locale', $entry);
            $this->assertContains($entry['locale'], $locales, $entry['locale']);
        }
    }
}
