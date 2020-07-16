<?php

namespace Valantic\DataQualityBundle\Tests\Config;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Valantic\DataQualityBundle\Config\V1\Meta\MetaKeys;
use Valantic\DataQualityBundle\Config\V1\Meta\Reader;
use Valantic\DataQualityBundle\Config\V1\Meta\Writer;
use Valantic\DataQualityBundle\Service\Information\ClassInformation;
use Valantic\DataQualityBundle\Service\Information\DefinitionInformationFactory;
use Valantic\DataQualityBundle\Service\Information\FieldCollectionInformation;
use Valantic\DataQualityBundle\Service\Information\ObjectBrickInformation;
use Valantic\DataQualityBundle\Tests\AbstractTestCase;

class MetaTest extends AbstractTestCase
{
    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var Writer
     */
    protected $writer;

    protected $className = 'SomeClass';

    protected function setUp(): void
    {
        $classInformationStub = $this->getMockBuilder(ClassInformation::class)
            ->onlyMethods(['getDefinition'])
            ->getMock();
        $classInformationStub->method('getDefinition')
            ->willReturn($this->getProductClassDefinition());

        $fieldCollectionInformationStub = $this->getMockBuilder(FieldCollectionInformation::class)
            ->onlyMethods(['getDefinition'])
            ->getMock();
        $fieldCollectionInformationStub
            ->method('getDefinition')
            ->willReturn($this->getAttributeFieldcollectionDefinition());

        $objectBrickInformationStub = $this->getMockBuilder(ObjectBrickInformation::class)
            ->onlyMethods(['getDefinition'])
            ->getMock();
        $objectBrickInformationStub
            ->method('getDefinition')
            ->willReturn($this->getBarcodeObjectbrickDefinition());

        $definitionInformationFactory = new DefinitionInformationFactory($classInformationStub, $fieldCollectionInformationStub, $objectBrickInformationStub);

        $this->deleteConfig();

        $this->reader = new Reader($this->createMock(EventDispatcher::class), $definitionInformationFactory);
        $this->writer = new Writer($this->reader, $this->createMock(EventDispatcher::class));
    }

    protected function tearDown(): void
    {
        self::cleanUp();
    }

    public function testReaderInstantiated()
    {
        $this->assertInstanceOf(Reader::class, $this->reader);
    }

    public function testReadMissingConfig()
    {
        $this->assertIsArray($this->reader->getConfiguredClasses());
        $this->assertCount(0, $this->reader->getConfiguredClasses());
    }

    public function testReadCorruptConfig()
    {
        $this->activateConfig($this::CONFIG_CORRUPT);
        $this->assertIsArray($this->reader->getConfiguredClasses());
        $this->assertCount(0, $this->reader->getConfiguredClasses());
    }

    public function testReadStringConfig()
    {
        $this->activateConfig($this::CONFIG_STRING);
        $this->assertIsArray($this->reader->getConfiguredClasses());
        $this->assertCount(0, $this->reader->getConfiguredClasses());
    }

    public function testReadClassesAreConfigured()
    {
        $this->activateConfig($this::CONFIG_FULL);
        $this->assertIsArray($this->reader->getConfiguredClasses());
        $this->assertCount(2, $this->reader->getConfiguredClasses());

        foreach ($this->reader->getConfiguredClasses() as $configuredClass) {
            $this->assertTrue($this->reader->isClassConfigured($configuredClass));
        }
    }

    public function testReadClassKeys()
    {
        $this->activateConfig($this::CONFIG_FULL);
        $className = 'Product';
        $this->assertTrue($this->reader->isClassConfigured($className));
        $config = $this->reader->getForClass($className);

        $this->assertArrayHasKey(MetaKeys::KEY_LOCALES, $config);
        $this->assertArrayHasKey(MetaKeys::KEY_THRESHOLD_ORANGE, $config);
        $this->assertArrayHasKey(MetaKeys::KEY_THRESHOLD_GREEN, $config);
    }

    public function testReadMissingClass()
    {
        $this->assertSame([], $this->reader->getForClass('UnknownClass'));
    }

    public function testWriteMissingConfig()
    {
        $this->assertTrue($this->writer->ensureConfigExists());
    }

    public function testWriteToMissingConfigFile()
    {
        $this->assertTrue($this->writer->update($this->className, ['a', 'b'], 80, 50));
        $this->assertSame([
            'locales' => [
                'a',
                'b',
            ],
            'threshold_green' => 0.8,
            'threshold_orange' => 0.5,
        ], $this->reader->getForClass($this->className));
    }

    public function testWriteToCorruptConfigFile()
    {
        $this->activateConfig(self::CONFIG_CORRUPT);

        $this->assertTrue($this->writer->update($this->className, ['a', 'b'], 80, 50));
        $this->assertSame([
            'locales' => [
                'a',
                'b',
            ],
            'threshold_green' => 0.8,
            'threshold_orange' => 0.5,
        ], $this->reader->getForClass($this->className));
    }

    public function testWriteToInvalidConfigFile()
    {
        $this->activateConfig(self::CONFIG_STRING);

        $this->assertTrue($this->writer->update($this->className, ['a', 'b'], 80, 50));
        $this->assertSame([
            'locales' => [
                'a',
                'b',
            ],
            'threshold_green' => 0.8,
            'threshold_orange' => 0.5,
        ], $this->reader->getForClass($this->className));
    }

    public function testWriteDoesNotAffectOtherEntries()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $this->assertCount(2, $this->reader->getConfiguredClasses());

        $this->writer->update($this->className, ['a', 'b'], 80, 50);

        $this->assertCount(3, $this->reader->getConfiguredClasses());
    }

    public function testWriteUpdates()
    {
        $this->writer->update($this->className, [], 80, 0);
        $this->assertSame(0.8, $this->reader->getForClass($this->className)[MetaKeys::KEY_THRESHOLD_GREEN]);

        $this->writer->update($this->className, [], 70, 0);
        $this->writer->update($this->className, [], 50, 70);
        $this->assertSame(0.5, $this->reader->getForClass($this->className)[MetaKeys::KEY_THRESHOLD_GREEN]);

        $this->writer->update($this->className, [], 0, 0);
        $this->assertSame([], $this->reader->getForClass($this->className)[MetaKeys::KEY_LOCALES]);

        $this->writer->update($this->className, ['a'], 0, 0);
        $this->assertSame(['a'], $this->reader->getForClass($this->className)[MetaKeys::KEY_LOCALES]);

        $this->writer->update($this->className, ['b'], 0, 0);
        $this->assertSame(['b'], $this->reader->getForClass($this->className)[MetaKeys::KEY_LOCALES]);
    }

    public function testDeleteEntry()
    {
        $this->writer->update($this->className, [], 80, 0);
        $this->assertTrue($this->reader->isClassConfigured($this->className));

        $this->assertTrue($this->writer->delete($this->className));

        $this->assertFalse($this->reader->isClassConfigured($this->className));
    }

    public function testDeleteUnknownEntry()
    {
        $this->assertFalse($this->reader->isClassConfigured($this->className));

        $this->assertTrue($this->writer->delete($this->className));

        $this->assertFalse($this->reader->isClassConfigured($this->className));
    }
}
