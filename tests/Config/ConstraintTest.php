<?php

namespace Valantic\DataQualityBundle\Tests\Config;

use Valantic\DataQualityBundle\Config\V1\Constraints\ConstraintKeys;
use Valantic\DataQualityBundle\Config\V1\Constraints\Reader;
use Valantic\DataQualityBundle\Config\V1\Constraints\Writer;
use Valantic\DataQualityBundle\Tests\AbstractTestCase;

class ConstraintTest extends AbstractTestCase
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

    /**
     * @var string
     */
    protected $attributeName = 'SomeAttribute';

    /**
     * @var string
     */
    protected $constraintName = 'SomeConstraint';

    /**
     * @var int
     */
    protected $constraintParams = 3;

    /**
     * @var string
     */
    protected $classNameOther = 'OtherNamespace\\OtherClass';

    /**
     * @var string
     */
    protected $attributeNameOther = 'other.attribute';

    /**
     * @var string
     */
    protected $constraintNameOther = 'Custom\\OtherConstraint';

    /**
     * @var array
     */
    protected $constraintParamsOther = ['arg1' => true, 'arg2' => 'yes', 3, null];

    protected function setUp(): void
    {
        $this->deleteConfig();

        $this->reader = $this->getConstraintsReader();
        $this->writer = $this->getConstraintsWriter();
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
        $this->assertCount(3, $this->reader->getConfiguredClasses());

        foreach ($this->reader->getConfiguredClasses() as $configuredClass) {
            $this->assertTrue($this->reader->isClassConfigured($configuredClass));
        }
    }

    public function testReadClassKeys(): void
    {
        $this->activateConfig($this::CONFIG_FULL);
        $className = 'Product';
        $this->assertTrue($this->reader->isClassConfigured($className));
        $configs = $this->reader->getForClass($className);

        $this->assertSame($this->reader->getConfiguredClassAttributes($className), array_keys($configs));

        foreach ($configs as $attribute => $config) {
            $this->assertTrue($this->reader->isClassAttributeConfigured($className, $attribute));

            $this->assertArrayHasKey(ConstraintKeys::KEY_NOTE, $config);
            $this->assertArrayHasKey(ConstraintKeys::KEY_RULES, $config);

            $this->assertSame($this->reader->getRulesForClassAttribute($className, $attribute), $config[ConstraintKeys::KEY_RULES]);
            $this->assertSame($this->reader->getNoteForClassAttribute($className, $attribute), $config[ConstraintKeys::KEY_NOTE]);
        }
    }

    public function testReadMissingClass(): void
    {
        $this->assertSame([], $this->reader->getForClass($this->classNameOther));
    }

    public function testWriteMissingConfig(): void
    {
        $this->assertTrue($this->writer->ensureConfigExists());
    }

    public function testWriteToMissingConfigFile(): void
    {
        $this->assertTrue($this->writer->addClassAttribute($this->className, $this->attributeName));
        $this->assertTrue($this->reader->isClassAttributeConfigured($this->className, $this->attributeName));
    }

    public function testWriteNote(): void
    {
        $this->assertNull($this->reader->getNoteForClassAttribute($this->className, $this->attributeName));

        $this->assertTrue($this->writer->modifyNote($this->className, $this->attributeName, 'lorem'));
        $this->assertSame('lorem', $this->reader->getNoteForClassAttribute($this->className, $this->attributeName));
        $this->assertArrayHasKey(ConstraintKeys::KEY_NOTE, $this->reader->getForClass($this->className)[$this->attributeName]);
        $this->assertSame('lorem', $this->reader->getForClass($this->className)[$this->attributeName][ConstraintKeys::KEY_NOTE]);

        $this->assertTrue($this->writer->modifyNote($this->className, $this->attributeName, 'ipsum'));
        $this->assertSame('ipsum', $this->reader->getNoteForClassAttribute($this->className, $this->attributeName));


        $this->assertTrue($this->writer->deleteNote($this->className, $this->attributeName));
        $this->assertTrue($this->writer->deleteNote($this->className, $this->attributeName));
        $this->assertNull($this->reader->getNoteForClassAttribute($this->className, $this->attributeName));

        $this->assertTrue($this->writer->deleteNote($this->className, $this->attributeNameOther));
        $this->assertNull($this->reader->getNoteForClassAttribute($this->className, $this->attributeNameOther));

        $this->assertNull($this->reader->getNoteForClassAttribute($this->classNameOther, $this->attributeName));
        $this->assertTrue($this->writer->deleteNote($this->classNameOther, $this->attributeName));
        $this->assertNull($this->reader->getNoteForClassAttribute($this->classNameOther, $this->attributeName));
    }

    public function testWriteDoubleAdd(): void
    {
        $this->assertTrue($this->writer->addClassAttribute($this->className, $this->attributeName));
        $this->assertTrue($this->writer->addClassAttribute($this->className, $this->attributeName));
        $this->assertTrue($this->reader->isClassAttributeConfigured($this->className, $this->attributeName));
    }

    public function testWriteToCorruptConfigFile(): void
    {
        $this->activateConfig(self::CONFIG_CORRUPT);
        $this->assertTrue($this->writer->addClassAttribute($this->className, $this->attributeName));
        $this->assertTrue($this->reader->isClassAttributeConfigured($this->className, $this->attributeName));
    }

    public function testWriteToInvalidConfigFile(): void
    {
        $this->activateConfig(self::CONFIG_STRING);

        $this->assertTrue($this->writer->addClassAttribute($this->className, $this->attributeName));
        $this->assertTrue($this->reader->isClassAttributeConfigured($this->className, $this->attributeName));
    }

    public function testWriteDoesNotAffectOtherEntries(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $this->assertCount(3, $this->reader->getConfiguredClasses());

        $this->writer->addClassAttribute($this->className, $this->attributeName);

        $this->assertCount(4, $this->reader->getConfiguredClasses());
    }

    public function testDeleteEntry(): void
    {
        $this->writer->addClassAttribute($this->className, $this->attributeName);
        $this->assertTrue($this->reader->isClassConfigured($this->className));
        $this->assertTrue($this->reader->isClassAttributeConfigured($this->className, $this->attributeName));

        $this->assertTrue($this->writer->deleteClassAttribute($this->className, $this->attributeName));

        $this->assertFalse($this->reader->isClassAttributeConfigured($this->className, $this->attributeName));
    }

    public function testDeleteUnknownEntry(): void
    {
        $this->assertFalse($this->reader->isClassConfigured($this->className));

        $this->assertTrue($this->writer->deleteClassAttribute($this->className, $this->attributeName));

        $this->assertFalse($this->reader->isClassConfigured($this->className));
    }

    public function testSimpleRule(): void
    {
        $this->assertCount(0, $this->reader->getRulesForClassAttribute($this->className, $this->attributeName));

        $this->assertTrue($this->writer->modifyRule($this->className, $this->attributeName, $this->constraintName));

        $this->assertCount(1, $this->reader->getRulesForClassAttribute($this->className, $this->attributeName));

        $this->assertNull($this->reader->getRulesForClassAttribute($this->className, $this->attributeName)[$this->constraintName]);
    }

    public function testRuleEmptyStringParams(): void
    {
        $this->assertTrue($this->writer->modifyRule($this->className, $this->attributeName, $this->constraintName, ''));
        $this->assertNull($this->reader->getRulesForClassAttribute($this->className, $this->attributeName)[$this->constraintName]);
    }

    public function testRuleScalarParams(): void
    {
        $this->assertTrue($this->writer->modifyRule($this->className, $this->attributeName, $this->constraintName, (string)$this->constraintParams));
        $this->assertSame($this->constraintParams, $this->reader->getRulesForClassAttribute($this->className, $this->attributeName)[$this->constraintName]);
    }

    public function testConstraintArrayParams(): void
    {
        $this->assertTrue($this->writer->modifyRule($this->className, $this->attributeName, $this->constraintNameOther, json_encode($this->constraintParamsOther)));
        $this->assertSame($this->constraintParamsOther, $this->reader->getRulesForClassAttribute($this->className, $this->attributeName)[$this->constraintNameOther]);
    }

    public function testMultipleRules(): void
    {
        $this->assertCount(0, $this->reader->getRulesForClassAttribute($this->className, $this->attributeName));
        $this->assertCount(0, $this->reader->getRulesForClassAttribute($this->className, $this->attributeNameOther));

        $this->assertTrue($this->writer->modifyRule($this->className, $this->attributeName, $this->constraintName, json_encode($this->constraintParams)));
        $this->assertTrue($this->writer->modifyRule($this->className, $this->attributeName, $this->constraintNameOther, json_encode($this->constraintParamsOther)));


        $this->assertTrue($this->writer->modifyRule($this->className, $this->attributeNameOther, $this->constraintName, json_encode($this->constraintParams)));
        $this->assertTrue($this->writer->modifyRule($this->className, $this->attributeNameOther, $this->constraintNameOther, json_encode($this->constraintParamsOther)));

        $this->assertCount(2, $this->reader->getRulesForClassAttribute($this->className, $this->attributeName));
        $this->assertCount(2, $this->reader->getRulesForClassAttribute($this->className, $this->attributeNameOther));

        $this->assertArrayHasKey($this->constraintName, $this->reader->getRulesForClassAttribute($this->className, $this->attributeName));
        $this->assertArrayHasKey($this->constraintNameOther, $this->reader->getRulesForClassAttribute($this->className, $this->attributeName));

        $this->assertArrayHasKey($this->constraintName, $this->reader->getRulesForClassAttribute($this->className, $this->attributeNameOther));
        $this->assertArrayHasKey($this->constraintNameOther, $this->reader->getRulesForClassAttribute($this->className, $this->attributeNameOther));
    }

    public function testDeleteRules(): void
    {
        $this->assertCount(0, $this->reader->getRulesForClassAttribute($this->className, $this->attributeName));

        $this->assertTrue($this->writer->modifyRule($this->className, $this->attributeName, $this->constraintName, json_encode($this->constraintParams)));
        $this->assertTrue($this->writer->modifyRule($this->className, $this->attributeName, $this->constraintNameOther, json_encode($this->constraintParamsOther)));

        $this->assertArrayHasKey($this->constraintName, $this->reader->getRulesForClassAttribute($this->className, $this->attributeName));
        $this->assertArrayHasKey($this->constraintNameOther, $this->reader->getRulesForClassAttribute($this->className, $this->attributeName));
        $this->assertCount(2, $this->reader->getRulesForClassAttribute($this->className, $this->attributeName));

        $this->assertTrue($this->writer->deleteRule($this->className, $this->attributeName, $this->constraintName));
        $this->assertTrue($this->writer->deleteRule($this->className, $this->attributeName, $this->constraintNameOther));

        $this->assertArrayNotHasKey($this->constraintName, $this->reader->getRulesForClassAttribute($this->className, $this->attributeName));
        $this->assertArrayNotHasKey($this->constraintNameOther, $this->reader->getRulesForClassAttribute($this->className, $this->attributeName));
        $this->assertCount(0, $this->reader->getRulesForClassAttribute($this->className, $this->attributeName));
    }

    public function testDeleteUnknownRules(): void
    {
        $this->assertTrue($this->writer->modifyRule($this->className, $this->attributeName, $this->constraintName, json_encode($this->constraintParams)));
        $this->assertTrue($this->writer->deleteRule($this->className, $this->attributeName, $this->constraintName));
        $this->assertTrue($this->writer->deleteRule($this->className, $this->attributeName, $this->constraintName));

        $this->assertTrue($this->writer->deleteRule($this->className, $this->attributeName, $this->constraintNameOther));

        $this->assertTrue($this->writer->deleteRule($this->className, $this->attributeNameOther, $this->constraintNameOther));
    }
}
