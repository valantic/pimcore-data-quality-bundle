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

    public function testListEmpty()
    {
        $this->controller->setContainer(self::$container);
        $response = $this->controller->listAction(Request::create('/'), $this->getMetaReader());

        $this->assertJson($response->getContent());

        $decoded = json_decode($response->getContent(), false);

        $this->assertSame([], $decoded);
    }

    public function testListFull()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $this->controller->setContainer(self::$container);
        $response = $this->controller->listAction(Request::create('/'), $this->getMetaReader());

        $this->assertJson($response->getContent());

        $decoded = json_decode($response->getContent(), true);

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

    public function testListFiltered()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $response = $this->controller->listAction(Request::create('/', 'GET', ['filterText' => 'Product']), $this->getMetaReader());

        $this->assertJson($response->getContent());

        $decoded = json_decode($response->getContent(), true);

        $this->assertCount(1, $decoded);

        $this->assertSame('Product', $decoded[0]['classname']);
    }

    public function testListMatchesConfig()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $this->controller->setContainer(self::$container);
        $response = $this->controller->listAction(Request::create('/', 'GET', ['filterText' => 'Product']), $this->getMetaReader());

        $this->assertJson($response->getContent());

        $decoded = json_decode($response->getContent(), true)[0];

        $reader = $this->getMetaReader();
        $config = $reader->getForClass($decoded['classname']);

        $this->assertSame($config[MetaKeys::KEY_LOCALES], $decoded['locales']);
        $this->assertEqualsWithDelta($config[MetaKeys::KEY_THRESHOLD_GREEN] * 100, $decoded['threshold_green'], 0.1);
        $this->assertEqualsWithDelta($config[MetaKeys::KEY_THRESHOLD_ORANGE] * 100, $decoded['threshold_orange'], 0.1);
    }

    public function testListClasses()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $response = $this->controller->listClassesAction($this->getMetaReader());
        $this->assertJson($response->getContent());

        $decoded = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('classes', $decoded);
        $this->assertCount(1, $decoded['classes']);

        foreach ($decoded['classes'] as $entry) {
            $this->assertArrayHasKey('name', $entry);
        }
    }

    public function testAddMissingData()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getMetaReader();
        $this->assertCount(2, $reader->getConfiguredClasses());
        $response = $this->controller->modifyAction(Request::create('/', 'POST'), $this->getMetaWriter());

        $this->assertJson($response->getContent());
        $this->assertCount(2, $reader->getConfiguredClasses());

        $decoded = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertFalse($decoded['status']);
    }

    public function testAddPartialData()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getMetaReader();
        $this->assertCount(2, $reader->getConfiguredClasses());
        $response = $this->controller->modifyAction(Request::create('/', 'POST', ['classname' => $this->className]), $this->getMetaWriter());

        $this->assertJson($response->getContent());
        $this->assertCount(3, $reader->getConfiguredClasses());
        $this->assertTrue($reader->isClassConfigured($this->className));

        $this->assertSame([], $reader->getForClass($this->className)[MetaKeys::KEY_LOCALES]);
        $this->assertSame(0, $reader->getForClass($this->className)[MetaKeys::KEY_THRESHOLD_ORANGE]);
        $this->assertSame(0, $reader->getForClass($this->className)[MetaKeys::KEY_THRESHOLD_GREEN]);

        $decoded = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testAddCompleteData()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getMetaReader();
        $this->assertCount(2, $reader->getConfiguredClasses());
        $response = $this->controller->modifyAction(Request::create('/', 'POST', [
            'classname' => $this->className,
            'locales' => ['de', 'en'],
            'threshold_green' => 80,
            'threshold_orange' => 50,
        ]), $this->getMetaWriter());

        $this->assertJson($response->getContent());
        $this->assertCount(3, $reader->getConfiguredClasses());
        $this->assertTrue($reader->isClassConfigured($this->className));

        $this->assertSame(['de', 'en'], $reader->getForClass($this->className)[MetaKeys::KEY_LOCALES]);
        $this->assertSame(0.5, $reader->getForClass($this->className)[MetaKeys::KEY_THRESHOLD_ORANGE]);
        $this->assertSame(0.8, $reader->getForClass($this->className)[MetaKeys::KEY_THRESHOLD_GREEN]);

        $decoded = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testDeleteMissingData()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getMetaReader();
        $this->assertCount(2, $reader->getConfiguredClasses());
        $response = $this->controller->deleteAction(Request::create('/', 'POST'), $this->getMetaWriter());

        $this->assertJson($response->getContent());
        $this->assertCount(2, $reader->getConfiguredClasses());

        $decoded = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertFalse($decoded['status']);
    }

    public function testDeleteCompleteData()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getMetaReader();
        $this->assertCount(2, $reader->getConfiguredClasses());
        $response = $this->controller->deleteAction(Request::create('/', 'POST', ['classname' => 'Product']), $this->getMetaWriter());

        $this->assertJson($response->getContent());
        $this->assertCount(1, $reader->getConfiguredClasses());

        $decoded = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testLocalesList()
    {
        $this->activateConfig(self::CONFIG_EMPTY);
        $locales = ['de', 'en'];

        $localesList = $this->createMock(LocalesList::class);
        $localesList->method('all')->willReturn($locales);

        $response = $this->controller->listLocalesAction($localesList);

        $this->assertJson($response->getContent());
        $decoded = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('locales', $decoded);
        $this->assertSameSize($locales, $decoded['locales']);

        foreach ($decoded['locales'] as $entry) {
            $this->assertArrayHasKey('locale', $entry);
            $this->assertTrue(in_array($entry['locale'], $locales), $entry['locale']);
        }
    }
}
