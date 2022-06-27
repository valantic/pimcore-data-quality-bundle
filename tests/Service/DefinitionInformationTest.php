<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Tests\Service;

use Valantic\DataQualityBundle\Service\Information\ClassInformation;
use Valantic\DataQualityBundle\Service\Information\AbstractDefinitionInformation;
use Valantic\DataQualityBundle\Service\Information\DefinitionInformationFactory;
use Valantic\DataQualityBundle\Service\Information\FieldCollectionInformation;
use Valantic\DataQualityBundle\Service\Information\ObjectBrickInformation;
use Valantic\DataQualityBundle\Tests\AbstractTestCase;

class DefinitionInformationTest extends AbstractTestCase
{
    protected AbstractDefinitionInformation $definitionInformation;
    protected string $name = 'Product';
    protected DefinitionInformationFactory $definitionInformationFactory;

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

    public function testName(): void
    {
        $this->assertSame($this->name, $this->definitionInformation->getName());
    }

    public function testAllAttributesHaveAType(): void
    {
        foreach ($this->definitionInformation->getAllAttributes() as $attribute => $data) {
            $this->assertIsString($this->definitionInformation->getAttributeType($attribute), $attribute);
            $this->assertContains($this->definitionInformation->getAttributeType($attribute), [
                AbstractDefinitionInformation::TYPE_PLAIN,
                AbstractDefinitionInformation::TYPE_RELATION,
                AbstractDefinitionInformation::TYPE_LOCALIZED,
                AbstractDefinitionInformation::TYPE_OBJECTBRICK,
                AbstractDefinitionInformation::TYPE_FIELDCOLLECTION,
                AbstractDefinitionInformation::TYPE_CLASSIFICATIONSTORE,
                AbstractDefinitionInformation::TYPE_RELATION,
            ], $attribute);
        }
    }

    public function testAttributeLabels(): void
    {
        foreach ($this->definitionInformation->getAllAttributes() as $attribute => $data) {
            $this->assertIsString($this->definitionInformation->getAttributeLabel($attribute));

            if (in_array($this->definitionInformation->getAttributeType($attribute), [
                AbstractDefinitionInformation::TYPE_OBJECTBRICK,
                AbstractDefinitionInformation::TYPE_FIELDCOLLECTION,
            ], true)) {
                $this->assertSame(2, substr_count($this->definitionInformation->getAttributeLabel($attribute), ' > '));
            }
            if (in_array($this->definitionInformation->getAttributeType($attribute), [
                AbstractDefinitionInformation::TYPE_PLAIN,
                AbstractDefinitionInformation::TYPE_LOCALIZED,
                AbstractDefinitionInformation::TYPE_RELATION,
            ], true)) {
                $this->assertSame(0, substr_count($this->definitionInformation->getAttributeLabel($attribute), ' > '));
            }
        }
    }

    public function testUnknownAttribute(): void
    {
        $this->assertNull($this->definitionInformation->getAttributeType('unknown_attribute'));
        $this->assertSame('', $this->definitionInformation->getAttributeLabel('unknown_attribute'));
    }
}
