<?php

namespace Valantic\DataQualityBundle\Tests\Service;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Valantic\DataQualityBundle\Config\V1\Meta\Reader;
use Valantic\DataQualityBundle\Service\Information\ClassInformation;
use Valantic\DataQualityBundle\Service\Information\DefinitionInformation;
use Valantic\DataQualityBundle\Service\Information\DefinitionInformationFactory;
use Valantic\DataQualityBundle\Service\Information\FieldCollectionInformation;
use Valantic\DataQualityBundle\Service\Information\ObjectBrickInformation;
use Valantic\DataQualityBundle\Tests\AbstractTestCase;

class DefinitionInformationTest extends AbstractTestCase
{
    protected $definitionInformation;

    protected $name = 'Product';

    protected $definitionInformationFactory;

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
        $this->definitionInformation = $definitionInformationFactory->make($this->name);
        $this->definitionInformationFactory = $definitionInformationFactory;
    }

    public function testName()
    {
        $this->assertSame($this->name, $this->definitionInformation->getName());
    }

    public function testNamespacedName()
    {
        $this->assertSame($this->name, $this->definitionInformationFactory->make('Pimcore\Model\DataObject\\' . $this->name)->getName());
    }

    public function testAllAttributesHaveAType()
    {
        foreach ($this->definitionInformation->getAllAttributes() as $attribute => $data) {
            $this->assertIsString($this->definitionInformation->getAttributeType($attribute), $attribute);
            $this->assertContains($this->definitionInformation->getAttributeType($attribute), [
                DefinitionInformation::TYPE_PLAIN,
                DefinitionInformation::TYPE_RELATION,
                DefinitionInformation::TYPE_LOCALIZED,
                DefinitionInformation::TYPE_OBJECTBRICK,
                DefinitionInformation::TYPE_FIELDCOLLECTION,
                DefinitionInformation::TYPE_CLASSIFICATIONSTORE,
                DefinitionInformation::TYPE_RELATION,
            ], $attribute);
        }
    }

    public function testAttributeLabels()
    {
        foreach ($this->definitionInformation->getAllAttributes() as $attribute => $data) {
            $this->assertIsString($this->definitionInformation->getAttributeLabel($attribute));

            if (in_array($this->definitionInformation->getAttributeType($attribute), [
                DefinitionInformation::TYPE_OBJECTBRICK,
                DefinitionInformation::TYPE_FIELDCOLLECTION,
            ], true)) {
                $this->assertSame(2, substr_count($this->definitionInformation->getAttributeLabel($attribute), ' > '));
            }
            if (in_array($this->definitionInformation->getAttributeType($attribute), [
                DefinitionInformation::TYPE_PLAIN,
                DefinitionInformation::TYPE_LOCALIZED,
                DefinitionInformation::TYPE_RELATION,
            ], true)) {
                $this->assertSame(0, substr_count($this->definitionInformation->getAttributeLabel($attribute), ' > '));
            }
        }
    }

    public function testUnknownAttribute()
    {
        $this->assertNull($this->definitionInformation->getAttributeType('unknown_attribute'));
        $this->assertSame('', $this->definitionInformation->getAttributeLabel('unknown_attribute'));
    }
}

