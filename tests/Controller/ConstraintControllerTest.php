<?php

namespace Valantic\DataQualityBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use Valantic\DataQualityBundle\Config\V1\Meta\MetaKeys;
use Valantic\DataQualityBundle\Controller\ConstraintConfigController;
use Valantic\DataQualityBundle\Repository\ConstraintDefinitions;
use Valantic\DataQualityBundle\Service\Locales\LocalesList;
use Valantic\DataQualityBundle\Tests\AbstractTestCase;

class ConstraintControllerTest extends AbstractTestCase
{
    /**
     * @var ConstraintConfigController
     */
    protected $controller;

    protected $className = 'SomeClass';

    protected $attributeName = 'some_attribute';

    protected $constraintName = 'that_constraint';

    protected $constraintParams = [1, 'hello', false];

    protected function setUp(): void
    {
        $this->controller = $this->getMockBuilder(ConstraintConfigController::class)
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
        $response = $this->controller->listAction(Request::create('/'), $this->getConstraintsReader());

        $this->assertJson($response->getContent());

        $decoded = json_decode($response->getContent(), false);

        $this->assertSame([], $decoded);
    }

    public function testListFull()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $this->controller->setContainer(self::$container);
        $response = $this->controller->listAction(Request::create('/'), $this->getConstraintsReader());

        $this->assertJson($response->getContent());

        $decoded = json_decode($response->getContent(), true);

        $this->assertCount(10, $decoded);

        foreach ($decoded as $entry) {
            $this->assertArrayHasKey('classname', $entry);
            $this->assertArrayHasKey('attributename', $entry);
            $this->assertArrayHasKey('rules', $entry);
            $this->assertArrayHasKey('rules_count', $entry);
            $this->assertArrayHasKey('note', $entry);

            $this->assertIsArray($entry['rules']);

            $this->assertIsInt($entry['rules_count']);
            $this->assertGreaterThanOrEqual(0, $entry['rules_count']);

            $this->assertTrue(in_array($entry['classname'], $this->controller->getClassNames(), true), $entry['classname']);
        }
    }

    public function testListFiltered()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $response = $this->controller->listAction(Request::create('/', 'GET', ['filterText' => 'Product']), $this->getConstraintsReader());

        $this->assertJson($response->getContent());

        $decoded = json_decode($response->getContent(), true);

        $this->assertCount(6, $decoded);

        foreach ($decoded as $entry) {
            $this->assertSame('Product', $entry['classname']);
        }
    }

    public function testListMatchesConfig()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $this->controller->setContainer(self::$container);
        $response = $this->controller->listAction(Request::create('/', 'GET', ['filterText' => 'email']), $this->getConstraintsReader());

        $this->assertJson($response->getContent());

        $decoded = json_decode($response->getContent(), true);
        $this->assertCount(1, $decoded);

        $entry = $decoded[0];

        $reader = $this->getConstraintsReader();
        $config = $reader->getForClass($entry['classname']);

        $this->assertSame('Customer', $entry['classname']);
        $this->assertSame('email', $entry['attributename']);

        $this->assertSame($reader->getNoteForClassAttribute('Customer', 'email'), $entry['note']);
        $this->assertSameSize($reader->getRulesForClassAttribute('Customer', 'email'), $entry['rules']);
        $this->assertSame(count($reader->getRulesForClassAttribute('Customer', 'email')), $entry['rules_count']);
        foreach ($entry['rules'] as $rule) {
            $this->assertArrayHasKey($rule['constraint'], $reader->getRulesForClassAttribute('Customer', 'email'), $rule['constraint']);
        }
    }

    public function testListClasses()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $response = $this->controller->listClassesAction();
        $this->assertJson($response->getContent());

        $decoded = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('classes', $decoded);
        $this->assertCount(3, $decoded['classes']);

        foreach ($decoded['classes'] as $entry) {
            $this->assertArrayHasKey('name', $entry);
        }
    }

    public function testListAttributesNoClassname()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $response = $this->controller->listAttributesAction(Request::create('/', 'GET'), $this->getConstraintsReader(), $this->getInformationFactory());

        $this->assertJson($response->getContent());
        $decoded = json_decode($response->getContent(), true);

        $this->assertCount(1, $decoded);
        $this->assertArrayHasKey('attributes', $decoded);
        $this->assertCount(0, $decoded['attributes']);
    }

    public function testListAttributes()
    {
        $this->activateConfig(self::CONFIG_FULL);
        $reader = $this->getConstraintsReader();

        $response = $this->controller->listAttributesAction(Request::create('/', 'GET', ['classname' => 'Product']), $this->getConstraintsReader(), $this->getInformationFactory());

        $this->assertJson($response->getContent());
        $decoded = json_decode($response->getContent(), true);

        $this->assertCount(1, $decoded);
        $this->assertArrayHasKey('attributes', $decoded);
        $this->assertCount(3, $decoded['attributes']);

        foreach ($decoded['attributes'] as $attribute) {
            $this->assertArrayHasKey('name', $attribute);
            $this->assertArrayHasKey('type', $attribute);
        }
    }

    public function testAddAttributeMissingData()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getConstraintsReader();
        $this->assertCount(0, $reader->getConfiguredClassAttributes($this->className));
        $response = $this->controller->addAttributeAction(Request::create('/', 'POST'), $this->getConstraintsWriter());

        $this->assertJson($response->getContent());
        $this->assertCount(0, $reader->getConfiguredClassAttributes($this->className));

        $decoded = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertFalse($decoded['status']);
    }

    public function testAddAttributePartialData()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getConstraintsReader();
        $this->assertCount(0, $reader->getConfiguredClassAttributes($this->className));
        $response = $this->controller->addAttributeAction(Request::create('/', 'POST', [
            'classname' => $this->className,
            'attributename' => $this->attributeName,
        ]), $this->getConstraintsWriter());

        $this->assertJson($response->getContent());
        $this->assertCount(1, $reader->getConfiguredClassAttributes($this->className));
        $this->assertTrue($reader->isClassConfigured($this->className));
        $this->assertTrue($reader->isClassAttributeConfigured($this->className, $this->attributeName));
        $this->assertSame([], $reader->getRulesForClassAttribute($this->className, $this->attributeName));
        $this->assertNull($reader->getNoteForClassAttribute($this->className, $this->attributeName));

        $decoded = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testAddAttributeCompleteData()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getConstraintsReader();
        $this->assertCount(0, $reader->getConfiguredClassAttributes($this->className));
        $response = $this->controller->addAttributeAction(Request::create('/', 'POST', [
            'classname' => $this->className,
            'attributename' => $this->attributeName,
            'note' => 'NOTE',
        ]), $this->getConstraintsWriter());

        $this->assertJson($response->getContent());
        $this->assertCount(1, $reader->getConfiguredClassAttributes($this->className));
        $this->assertTrue($reader->isClassConfigured($this->className));
        $this->assertTrue($reader->isClassAttributeConfigured($this->className, $this->attributeName));
        $this->assertSame([], $reader->getRulesForClassAttribute($this->className, $this->attributeName));
        $this->assertSame('NOTE', $reader->getNoteForClassAttribute($this->className, $this->attributeName));

        $decoded = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testDeleteAttributeMissingData()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getConstraintsReader();
        $this->assertCount(6, $reader->getConfiguredClassAttributes('Product'));
        $response = $this->controller->deleteAttributeAction(Request::create('/', 'POST'), $this->getConstraintsWriter());

        $this->assertJson($response->getContent());
        $this->assertCount(6, $reader->getConfiguredClassAttributes('Product'));

        $decoded = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertFalse($decoded['status']);
    }

    public function testDeleteAttributeCompleteData()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getConstraintsReader();
        $this->assertCount(6, $reader->getConfiguredClassAttributes('Product'));
        $response = $this->controller->deleteAttributeAction(Request::create('/', 'POST', [
            'classname' => 'Product',
            'attributename' => 'name',
        ]), $this->getConstraintsWriter());

        $this->assertJson($response->getContent());
        $this->assertCount(5, $reader->getConfiguredClassAttributes('Product'));

        $decoded = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testListConstraints()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $response = $this->controller->listConstraintsAction((new ConstraintDefinitions([])));

        $this->assertJson($response->getContent());
        $decoded = json_decode($response->getContent(), true);

        $this->assertCount(1, $decoded);
        $this->assertArrayHasKey('constraints', $decoded);
        $this->assertCount(49, $decoded['constraints']);

        foreach ($decoded['constraints'] as $constraint) {
            $this->assertArrayHasKey('name', $constraint);
            $this->assertArrayHasKey('label', $constraint);
            $this->assertArrayHasKey('default_parameter', $constraint);
            $this->assertArrayHasKey('required_parameters', $constraint);
            $this->assertArrayHasKey('optional_parameters', $constraint);

            $this->assertIsString($constraint['label']);
            $this->assertNotEmpty($constraint['label']);

            $this->assertIsArray($constraint['required_parameters']);
            $this->assertIsArray($constraint['optional_parameters']);
        }
    }


    public function testAddConstraintMissingData()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getConstraintsReader();
        $this->assertCount(0, $reader->getConfiguredClassAttributes($this->className));
        $response = $this->controller->addConstraintAction(Request::create('/', 'POST', [
            'classname' => $this->className,
            'attributename' => $this->attributeName,
        ]), $this->getConstraintsWriter());

        $this->assertJson($response->getContent());
        $this->assertCount(0, $reader->getConfiguredClassAttributes($this->className));

        $decoded = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertFalse($decoded['status']);
    }

    public function testAddConstraintPartialData()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getConstraintsReader();
        $this->assertCount(0, $reader->getConfiguredClassAttributes($this->className));
        $response = $this->controller->addConstraintAction(Request::create('/', 'POST', [
            'classname' => $this->className,
            'attributename' => $this->attributeName,
            'constraint' => $this->constraintName,
        ]), $this->getConstraintsWriter());

        $this->assertJson($response->getContent());
        $this->assertCount(1, $reader->getConfiguredClassAttributes($this->className));
        $this->assertTrue($reader->isClassConfigured($this->className));
        $this->assertTrue($reader->isClassAttributeConfigured($this->className, $this->attributeName));
        $this->assertSame([$this->constraintName => null], $reader->getRulesForClassAttribute($this->className, $this->attributeName));

        $decoded = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testAddConstraintCompleteData()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getConstraintsReader();
        $this->assertCount(0, $reader->getConfiguredClassAttributes($this->className));
        $response = $this->controller->addConstraintAction(Request::create('/', 'POST', [
            'classname' => $this->className,
            'attributename' => $this->attributeName,
            'constraint' => $this->constraintName,
            'params' => json_encode($this->constraintParams),
        ]), $this->getConstraintsWriter());

        $this->assertJson($response->getContent());
        $this->assertCount(1, $reader->getConfiguredClassAttributes($this->className));
        $this->assertTrue($reader->isClassConfigured($this->className));
        $this->assertTrue($reader->isClassAttributeConfigured($this->className, $this->attributeName));
        $this->assertSame([$this->constraintName => $this->constraintParams], $reader->getRulesForClassAttribute($this->className, $this->attributeName));

        $decoded = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testDeleteConstraintMissingData()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getConstraintsReader();
        $this->assertCount(2, $reader->getRulesForClassAttribute('Product', 'name'));
        $response = $this->controller->deleteConstraintAction(Request::create('/', 'POST', [
            'classname' => 'Product',
            'attributename' => 'name',
        ]), $this->getConstraintsWriter());

        $this->assertJson($response->getContent());
        $this->assertCount(2, $reader->getRulesForClassAttribute('Product', 'name'));

        $decoded = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertFalse($decoded['status']);
    }

    public function testDeleteConstraintCompleteData()
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getConstraintsReader();
        $this->assertCount(2, $reader->getRulesForClassAttribute('Product', 'name'));
        $response = $this->controller->deleteConstraintAction(Request::create('/', 'POST', [
            'classname' => 'Product',
            'attributename' => 'name',
            'constraint' => 'Length',
        ]), $this->getConstraintsWriter());

        $this->assertJson($response->getContent());
        $this->assertCount(1, $reader->getRulesForClassAttribute('Product', 'name'));

        $decoded = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }
}
