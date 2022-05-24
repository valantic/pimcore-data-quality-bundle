<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Tests\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Valantic\DataQualityBundle\Controller\ConstraintConfigController;
use Valantic\DataQualityBundle\Repository\ConstraintDefinitions;
use Valantic\DataQualityBundle\Tests\AbstractTestCase;

class ConstraintControllerTest extends AbstractTestCase
{
    protected MockObject|ConstraintConfigController $controller;

    protected string $className = 'SomeClass';

    protected string $attributeName = 'some_attribute';

    protected string $constraintName = 'that_constraint';

    protected array $constraintParams = [1, 'hello', false];

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

    public function testListEmpty(): void
    {
        $this->controller->setContainer(self::$container);
        $response = $this->controller->listAction(Request::create('/'), $this->getConstraintsReader(), new ConstraintDefinitions([]));

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);

        $decoded = json_decode($content, false, 512, \JSON_THROW_ON_ERROR);

        $this->assertSame([], $decoded);
    }

    public function testListFull(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $this->controller->setContainer(self::$container);
        $response = $this->controller->listAction(Request::create('/'), $this->getConstraintsReader(), new ConstraintDefinitions([]));

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);

        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

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

            $this->assertContains($entry['classname'], $this->controller->getClassNames(), $entry['classname']);
        }
    }

    public function testListFiltered(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $response = $this->controller->listAction(Request::create('/', 'GET', ['filterText' => 'Product']), $this->getConstraintsReader(), new ConstraintDefinitions([]));

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);

        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        $this->assertCount(6, $decoded);

        foreach ($decoded as $entry) {
            $this->assertSame('Product', $entry['classname']);
        }
    }

    public function testListMatchesConfig(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $this->controller->setContainer(self::$container);
        $response = $this->controller->listAction(Request::create('/', 'GET', ['filterText' => 'email']), $this->getConstraintsReader(), new ConstraintDefinitions([]));

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);

        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
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

    public function testListClasses(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $response = $this->controller->listClassesAction();
        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);

        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('classes', $decoded);
        $this->assertCount(3, $decoded['classes']);

        foreach ($decoded['classes'] as $entry) {
            $this->assertArrayHasKey('name', $entry);
        }
    }

    public function testListAttributesNoClassname(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $response = $this->controller->listAttributesAction(Request::create('/', 'GET'), $this->getConstraintsReader(), $this->getInformationFactory());

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        $this->assertCount(1, $decoded);
        $this->assertArrayHasKey('attributes', $decoded);
        $this->assertCount(0, $decoded['attributes']);
    }

    public function testListAttributes(): void
    {
        $this->activateConfig(self::CONFIG_FULL);
        $reader = $this->getConstraintsReader();

        $response = $this->controller->listAttributesAction(Request::create('/', 'GET', ['classname' => 'Product']), $this->getConstraintsReader(), $this->getInformationFactory());

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        $this->assertCount(1, $decoded);
        $this->assertArrayHasKey('attributes', $decoded);
        $this->assertCount(3, $decoded['attributes']);

        foreach ($decoded['attributes'] as $attribute) {
            $this->assertArrayHasKey('name', $attribute);
            $this->assertArrayHasKey('type', $attribute);
        }
    }

    public function testAddAttributeMissingData(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getConstraintsReader();
        $this->assertCount(0, $reader->getConfiguredClassAttributes($this->className));
        $response = $this->controller->addAttributeAction(Request::create('/', 'POST'), $this->getConstraintsWriter());

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(0, $reader->getConfiguredClassAttributes($this->className));

        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertFalse($decoded['status']);
    }

    public function testAddAttributePartialData(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getConstraintsReader();
        $this->assertCount(0, $reader->getConfiguredClassAttributes($this->className));
        $response = $this->controller->addAttributeAction(Request::create('/', 'POST', [
            'classname' => $this->className,
            'attributename' => $this->attributeName,
        ]), $this->getConstraintsWriter());

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(1, $reader->getConfiguredClassAttributes($this->className));
        $this->assertTrue($reader->isClassConfigured($this->className));
        $this->assertTrue($reader->isClassAttributeConfigured($this->className, $this->attributeName));
        $this->assertSame([], $reader->getRulesForClassAttribute($this->className, $this->attributeName));
        $this->assertNull($reader->getNoteForClassAttribute($this->className, $this->attributeName));

        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testAddAttributeCompleteData(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getConstraintsReader();
        $this->assertCount(0, $reader->getConfiguredClassAttributes($this->className));
        $response = $this->controller->addAttributeAction(Request::create('/', 'POST', [
            'classname' => $this->className,
            'attributename' => $this->attributeName,
            'note' => 'NOTE',
        ]), $this->getConstraintsWriter());

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(1, $reader->getConfiguredClassAttributes($this->className));
        $this->assertTrue($reader->isClassConfigured($this->className));
        $this->assertTrue($reader->isClassAttributeConfigured($this->className, $this->attributeName));
        $this->assertSame([], $reader->getRulesForClassAttribute($this->className, $this->attributeName));
        $this->assertSame('NOTE', $reader->getNoteForClassAttribute($this->className, $this->attributeName));

        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testDeleteAttributeMissingData(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getConstraintsReader();
        $this->assertCount(6, $reader->getConfiguredClassAttributes('Product'));
        $response = $this->controller->deleteAttributeAction(Request::create('/', 'POST'), $this->getConstraintsWriter());

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(6, $reader->getConfiguredClassAttributes('Product'));

        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertFalse($decoded['status']);
    }

    public function testDeleteAttributeCompleteData(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getConstraintsReader();
        $this->assertCount(6, $reader->getConfiguredClassAttributes('Product'));
        $response = $this->controller->deleteAttributeAction(Request::create('/', 'POST', [
            'classname' => 'Product',
            'attributename' => 'name',
        ]), $this->getConstraintsWriter());

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(5, $reader->getConfiguredClassAttributes('Product'));

        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testListConstraints(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $response = $this->controller->listConstraintsAction((new ConstraintDefinitions([])));

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

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

    public function testAddConstraintMissingData(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getConstraintsReader();
        $this->assertCount(0, $reader->getConfiguredClassAttributes($this->className));
        $response = $this->controller->addConstraintAction(Request::create('/', 'POST', [
            'classname' => $this->className,
            'attributename' => $this->attributeName,
        ]), $this->getConstraintsWriter());

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(0, $reader->getConfiguredClassAttributes($this->className));

        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertFalse($decoded['status']);
    }

    public function testAddConstraintPartialData(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getConstraintsReader();
        $this->assertCount(0, $reader->getConfiguredClassAttributes($this->className));
        $response = $this->controller->addConstraintAction(Request::create('/', 'POST', [
            'classname' => $this->className,
            'attributename' => $this->attributeName,
            'constraint' => $this->constraintName,
        ]), $this->getConstraintsWriter());

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(1, $reader->getConfiguredClassAttributes($this->className));
        $this->assertTrue($reader->isClassConfigured($this->className));
        $this->assertTrue($reader->isClassAttributeConfigured($this->className, $this->attributeName));
        $this->assertSame([$this->constraintName => null], $reader->getRulesForClassAttribute($this->className, $this->attributeName));

        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testAddConstraintCompleteData(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getConstraintsReader();
        $this->assertCount(0, $reader->getConfiguredClassAttributes($this->className));
        $response = $this->controller->addConstraintAction(Request::create('/', 'POST', [
            'classname' => $this->className,
            'attributename' => $this->attributeName,
            'constraint' => $this->constraintName,
            'params' => json_encode($this->constraintParams, \JSON_THROW_ON_ERROR),
        ]), $this->getConstraintsWriter());

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(1, $reader->getConfiguredClassAttributes($this->className));
        $this->assertTrue($reader->isClassConfigured($this->className));
        $this->assertTrue($reader->isClassAttributeConfigured($this->className, $this->attributeName));
        $this->assertSame([$this->constraintName => $this->constraintParams], $reader->getRulesForClassAttribute($this->className, $this->attributeName));

        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testDeleteConstraintMissingData(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getConstraintsReader();
        $this->assertCount(2, $reader->getRulesForClassAttribute('Product', 'name'));
        $response = $this->controller->deleteConstraintAction(Request::create('/', 'POST', [
            'classname' => 'Product',
            'attributename' => 'name',
        ]), $this->getConstraintsWriter());

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(2, $reader->getRulesForClassAttribute('Product', 'name'));

        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertFalse($decoded['status']);
    }

    public function testDeleteConstraintCompleteData(): void
    {
        $this->activateConfig(self::CONFIG_FULL);

        $reader = $this->getConstraintsReader();
        $this->assertCount(2, $reader->getRulesForClassAttribute('Product', 'name'));
        $response = $this->controller->deleteConstraintAction(Request::create('/', 'POST', [
            'classname' => 'Product',
            'attributename' => 'name',
            'constraint' => 'Length',
        ]), $this->getConstraintsWriter());

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(1, $reader->getRulesForClassAttribute('Product', 'name'));

        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }
}
