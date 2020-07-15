<?php

namespace Valantic\DataQualityBundle\Tests\Config;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Valantic\DataQualityBundle\Config\V1\Meta\Reader;
use Valantic\DataQualityBundle\Tests\AbstractTestCase;

class MetaTest extends AbstractTestCase
{
    protected $config;

    protected function setUp(): void
    {
        $this->config = new Reader(new EventDispatcher());
    }

    public function testReaderInstantiated()
    {
        $this->assertInstanceOf(Reader::class, $this->config);
    }

    public function testMissingConfig()
    {
        self::cleanUp();
        $this->assertIsArray($this->config->getConfiguredClasses());
        $this->assertCount(0, $this->config->getConfiguredClasses());
    }

    public function testCorruptConfig()
    {
        $this->activateConfig($this::CONFIG_CORRUPT);
        $this->assertIsArray($this->config->getConfiguredClasses());
        $this->assertCount(0, $this->config->getConfiguredClasses());
    }

    public function testClasses()
    {
        $this->activateConfig($this::CONFIG_FULL);
        $this->assertIsArray($this->config->getConfiguredClasses());
        $this->assertCount(2, $this->config->getConfiguredClasses());

        foreach ($this->config->getConfiguredClasses() as $configuredClass) {
            $this->assertTrue($this->config->isClassConfigured($configuredClass));
        }

        // TODO: getForClass()
    }
}
