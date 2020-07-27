<?php

namespace Valantic\DataQualityBundle\Tests\Config;

use Valantic\DataQualityBundle\Config\V1\Meta\MetaKeys;
use Valantic\DataQualityBundle\Config\V1\Meta\Reader;
use Valantic\DataQualityBundle\Config\V1\Meta\Writer;
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

    /**
     * @var string
     */
    protected $className = 'SomeClass';

    protected function setUp(): void
    {
        $this->deleteConfig();

        $this->reader = $this->getMetaReader();
        $this->writer = $this->getMetaWriter();
    }

    public function testReaderInstantiated(): void
    {
        $this->assertInstanceOf(Reader::class, $this->reader);
    }

    public function testReadMissingConfig(): void
    {
        $this->assertIsArray($this->reader->getConfiguredClasses());
        $this->assertCount(0, $this->reader->getConfiguredClasses());
    }

    public function testReadCorruptConfig(): void
    {
        $this->activateConfig($this::CONFIG_CORRUPT);
        $this->assertIsArray($this->reader->getConfiguredClasses());
        $this->assertCount(0, $this->reader->getConfiguredClasses());
    }

    public function testReadStringConfig(): void
    {
        $this->activateConfig($this::CONFIG_STRING);
        $this->assertIsArray($this->reader->getConfiguredClasses());
        $this->assertCount(0, $this->reader->getConfiguredClasses());
    }

    public function testReadClassesAreConfigured(): void
    {
        $this->activateConfig($this::CONFIG_FULL);
        $this->assertIsArray($this->reader->getConfiguredClasses());
        $this->assertCount(2, $this->reader->getConfiguredClasses());

        foreach ($this->reader->getConfiguredClasses() as $configuredClass) {
            $this->assertTrue($this->reader->isClassConfigured($configuredClass));
        }
    }

    public function testReadClassKeys(): void
    {
        $this->activateConfig($this::CONFIG_FULL);
        $className = 'Product';
        $this->assertTrue($this->reader->isClassConfigured($className));
        $config = $this->reader->getForClass($className);

        $this->assertArrayHasKey(MetaKeys::KEY_NESTING_LIMIT, $config);
        $this->assertArrayHasKey(MetaKeys::KEY_LOCALES, $config);
        $this->assertArrayHasKey(MetaKeys::KEY_THRESHOLD_ORANGE, $config);
        $this->assertArrayHasKey(MetaKeys::KEY_THRESHOLD_GREEN, $config);
    }

    public function testReadMissingClass(): void
    {
        $this->assertSame([], $this->reader->getForClass('UnknownClass'));
    }

    public function testWriteMissingConfig(): void
    {
        $this->assertTrue($this->writer->ensureConfigExists());
    }

    public function testWriteToMissingConfigFile(): void
    {
        $this->assertTrue($this->writer->update($this->className, ['a', 'b'], 80, 50));
        $this->assertSame([
            'locales' => [
                'a',
                'b',
            ],
            'threshold_green' => 0.8,
            'threshold_orange' => 0.5,
            'nesting_limit' => 1,
        ], $this->reader->getForClass($this->className));
    }

    public function testWriteToCorruptConfigFile(): void
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
            'nesting_limit' => 1,
        ], $this->reader->getForClass($this->className));
    }

    public function testWriteToInvalidConfigFile(): void
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
            'nesting_limit' => 1,
        ], $this->reader->getForClass($this->className));
    }

    public function testWriteDoesNotAffectOtherEntries(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $this->assertCount(2, $this->reader->getConfiguredClasses());

        $this->writer->update($this->className, ['a', 'b'], 80, 50);

        $this->assertCount(3, $this->reader->getConfiguredClasses());
    }

    public function testWriteUpdates(): void
    {
        $this->writer->update($this->className, [], 80, 0, 1);
        $this->assertSame(0.8, $this->reader->getForClass($this->className)[MetaKeys::KEY_THRESHOLD_GREEN]);

        $this->writer->update($this->className, [], 70, 0, 1);
        $this->writer->update($this->className, [], 50, 70, 1);
        $this->assertSame(0.5, $this->reader->getForClass($this->className)[MetaKeys::KEY_THRESHOLD_GREEN]);

        $this->writer->update($this->className, [], 0, 0, 1);
        $this->assertSame([], $this->reader->getForClass($this->className)[MetaKeys::KEY_LOCALES]);

        $this->writer->update($this->className, ['a'], 0, 0, 1);
        $this->assertSame(['a'], $this->reader->getForClass($this->className)[MetaKeys::KEY_LOCALES]);

        $this->writer->update($this->className, ['b'], 0, 0, 1);
        $this->assertSame(['b'], $this->reader->getForClass($this->className)[MetaKeys::KEY_LOCALES]);

        $this->writer->update($this->className, ['b'], 0, 0, 1);
        $this->assertSame(1, $this->reader->getForClass($this->className)[MetaKeys::KEY_NESTING_LIMIT]);

        $this->writer->update($this->className, ['b'], 0, 0, 3);
        $this->assertSame(3, $this->reader->getForClass($this->className)[MetaKeys::KEY_NESTING_LIMIT]);

        $this->writer->update($this->className, ['b'], 0, 0, 0);
        $this->assertSame(0, $this->reader->getForClass($this->className)[MetaKeys::KEY_NESTING_LIMIT]);
    }

    public function testDeleteEntry(): void
    {
        $this->writer->update($this->className, [], 80, 0);
        $this->assertTrue($this->reader->isClassConfigured($this->className));

        $this->assertTrue($this->writer->delete($this->className));

        $this->assertFalse($this->reader->isClassConfigured($this->className));
    }

    public function testDeleteUnknownEntry(): void
    {
        $this->assertFalse($this->reader->isClassConfigured($this->className));

        $this->assertTrue($this->writer->delete($this->className));

        $this->assertFalse($this->reader->isClassConfigured($this->className));
    }
}
