<?php

namespace Valantic\DataQualityBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use Valantic\DataQualityBundle\Config\V1\Meta\MetaKeys;
use Valantic\DataQualityBundle\Controller\MetaConfigController;
use Valantic\DataQualityBundle\Service\Locales\LocalesList;
use Valantic\DataQualityBundle\Tests\AbstractTestCase;

class MetaControllerTest extends AbstractTestCase
{
    /**
     * @var MetaConfigController
     */
    protected $controller;

    /**
     * @var string
     */
    protected $className = 'SomeClass';

    protected function setUp(): void
    {
        $this->controller = $this->getMockBuilder(MetaConfigController::class)
            ->onlyMethods(['getClassNames'])
            ->getMock();
        $this->controller
            ->method('getClassNames')
            ->willReturn(['Customer', 'Product', 'Category']);
        $this->controller->setContainer(self::$container);
    }

    public function testListEmpty(): void
    {
        $this->controller->setContainer(self::$container);
        $response = $this->controller->listAction(Request::create('/'), $this->getMetaReader());

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string)$content;

        $this->assertJson($content);

        $decoded = json_decode($content, false);

        $this->assertSame([], $decoded);
    }

    public function testListFull(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $this->controller->setContainer(self::$container);
        $response = $this->controller->listAction(Request::create('/'), $this->getMetaReader());

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string)$content;

        $this->assertJson($content);

        $decoded = json_decode($content, true);

        $this->assertCount(2, $decoded);

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
        $this->activateConfig(self::CONFIG_FULL);

        $response = $this->controller->listAction(Request::create('/', 'GET', ['filterText' => 'Product']), $this->getMetaReader());

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string)$content;

        $this->assertJson($content);

        $decoded = json_decode($content, true);

        $this->assertCount(1, $decoded);

        $this->assertSame('Product', $decoded[0]['classname']);
    }

    public function testListMatchesConfig(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $this->controller->setContainer(self::$container);
        $response = $this->controller->listAction(Request::create('/', 'GET', ['filterText' => 'Product']), $this->getMetaReader());

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string)$content;

        $this->assertJson($content);

        $decoded = json_decode($content, true)[0];

        $reader = $this->getMetaReader();
        $config = $reader->getForClass($decoded['classname']);

        $this->assertSame($config[MetaKeys::KEY_NESTING_LIMIT], $decoded['nesting_limit']);
        $this->assertSame($config[MetaKeys::KEY_LOCALES], $decoded['locales']);
        $this->assertEqualsWithDelta($config[MetaKeys::KEY_THRESHOLD_GREEN] * 100, $decoded['threshold_green'], 0.1);
        $this->assertEqualsWithDelta($config[MetaKeys::KEY_THRESHOLD_ORANGE] * 100, $decoded['threshold_orange'], 0.1);
    }

    public function testListClasses(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $response = $this->controller->listClassesAction($this->getMetaReader());
        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string)$content;

        $this->assertJson($content);

        $decoded = json_decode($content, true);

        $this->assertArrayHasKey('classes', $decoded);
        $this->assertCount(1, $decoded['classes']);

        foreach ($decoded['classes'] as $entry) {
            $this->assertArrayHasKey('name', $entry);
        }
    }

    public function testAddMissingData(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getMetaReader();
        $this->assertCount(2, $reader->getConfiguredClasses());
        $response = $this->controller->modifyAction(Request::create('/', 'POST'), $this->getMetaWriter());

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string)$content;

        $this->assertJson($content);
        $this->assertCount(2, $reader->getConfiguredClasses());

        $decoded = json_decode($content, true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertFalse($decoded['status']);
    }

    public function testAddPartialData(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getMetaReader();
        $this->assertCount(2, $reader->getConfiguredClasses());
        $response = $this->controller->modifyAction(Request::create('/', 'POST', ['classname' => $this->className]), $this->getMetaWriter());

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string)$content;

        $this->assertJson($content);
        $this->assertCount(3, $reader->getConfiguredClasses());
        $this->assertTrue($reader->isClassConfigured($this->className));

        $this->assertSame([], $reader->getForClass($this->className)[MetaKeys::KEY_LOCALES]);
        $this->assertSame(0, $reader->getForClass($this->className)[MetaKeys::KEY_THRESHOLD_ORANGE]);
        $this->assertSame(0, $reader->getForClass($this->className)[MetaKeys::KEY_THRESHOLD_GREEN]);
        $this->assertSame(1, $reader->getForClass($this->className)[MetaKeys::KEY_NESTING_LIMIT]);

        $decoded = json_decode($content, true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testAddCompleteData(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getMetaReader();
        $this->assertCount(2, $reader->getConfiguredClasses());
        $response = $this->controller->modifyAction(Request::create('/', 'POST', [
            'classname' => $this->className,
            'locales' => ['de', 'en'],
            'threshold_green' => 80,
            'threshold_orange' => 50,
            'nesting_limit' => 2,
        ]), $this->getMetaWriter());

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string)$content;

        $this->assertJson($content);
        $this->assertCount(3, $reader->getConfiguredClasses());
        $this->assertTrue($reader->isClassConfigured($this->className));

        $this->assertSame(['de', 'en'], $reader->getForClass($this->className)[MetaKeys::KEY_LOCALES]);
        $this->assertSame(0.5, $reader->getForClass($this->className)[MetaKeys::KEY_THRESHOLD_ORANGE]);
        $this->assertSame(0.8, $reader->getForClass($this->className)[MetaKeys::KEY_THRESHOLD_GREEN]);
        $this->assertSame(2, $reader->getForClass($this->className)[MetaKeys::KEY_NESTING_LIMIT]);

        $decoded = json_decode($content, true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testDeleteMissingData(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getMetaReader();
        $this->assertCount(2, $reader->getConfiguredClasses());
        $response = $this->controller->deleteAction(Request::create('/', 'POST'), $this->getMetaWriter());

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string)$content;

        $this->assertJson($content);
        $this->assertCount(2, $reader->getConfiguredClasses());

        $decoded = json_decode($content, true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertFalse($decoded['status']);
    }

    public function testDeleteCompleteData(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getMetaReader();
        $this->assertCount(2, $reader->getConfiguredClasses());
        $response = $this->controller->deleteAction(Request::create('/', 'POST', ['classname' => 'Product']), $this->getMetaWriter());

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string)$content;

        $this->assertJson($content);
        $this->assertCount(1, $reader->getConfiguredClasses());

        $decoded = json_decode($content, true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testLocalesList(): void
    {
        $this->activateConfig(self::CONFIG_EMPTY);
        $locales = ['de', 'en'];

        $localesList = $this->createMock(LocalesList::class);
        $localesList->method('all')->willReturn($locales);

        $response = $this->controller->listLocalesAction($localesList);

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string)$content;

        $this->assertJson($content);
        $decoded = json_decode($content, true);

        $this->assertArrayHasKey('locales', $decoded);
        $this->assertSameSize($locales, $decoded['locales']);

        foreach ($decoded['locales'] as $entry) {
            $this->assertArrayHasKey('locale', $entry);
            $this->assertContains($entry['locale'], $locales, $entry['locale']);
        }
    }
}
