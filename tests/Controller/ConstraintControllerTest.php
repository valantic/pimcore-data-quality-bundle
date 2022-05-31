<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Tests\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Valantic\DataQualityBundle\Controller\ConstraintConfigController;
use Valantic\DataQualityBundle\Repository\ConfigurationRepository;
use Valantic\DataQualityBundle\Repository\ConstraintDefinitions;
use Valantic\DataQualityBundle\Tests\AbstractTestCase;
use const JSON_THROW_ON_ERROR;

class ConstraintControllerTest extends AbstractTestCase
{
    protected MockObject|ConstraintConfigController $controller;

    /** @var class-string */
    protected string $className = 'SomeClass';
    /** @var class-string */
    protected string $classNameConfigured = 'Pimcore\Model\DataObject\Product';

    protected string $attributeName = 'some_attribute';

    protected ConfigurationRepository $configurationRepository;
    protected string $constraintName = 'that_constraint';

    protected array $constraintParams = [1, 'hello', false];

    protected function setUp(): void
    {
        $this->controller = $this->getMockBuilder(ConstraintConfigController::class)
            ->onlyMethods(['getClassNames'])
            ->getMock();
        $this->controller
            ->method('getClassNames')
            ->willReturn(['Pimcore\Model\DataObject\Customer', $this->classNameConfigured, 'Pimcore\Model\DataObject\Category']);
        $this->controller->setContainer(self::$container);
        $this->configurationRepository = $this->getConfigurationRepository();
    }

    public function testListFull(): void
    {
        $this->controller->setContainer(self::$container);
        $response = $this->controller->listAction(
            Request::create('/'),
            new ConstraintDefinitions(null),
            $this->configurationRepository
        );

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

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
        $response = $this->controller->listAction(
            Request::create('/', 'GET', ['filterText' => substr($this->classNameConfigured, -20)]),
            new ConstraintDefinitions(null),
            $this->configurationRepository
        );

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(6, $decoded);

        foreach ($decoded as $entry) {
            $this->assertSame($this->classNameConfigured, $entry['classname']);
        }
    }

    public function testListMatchesConfig(): void
    {
        $this->controller->setContainer(self::$container);
        $response = $this->controller->listAction(
            Request::create('/', 'GET', ['filterText' => 'email']),
            new ConstraintDefinitions(null),
            $this->configurationRepository
        );

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $decoded);

        $entry = $decoded[0];

        $this->assertSame('Pimcore\Model\DataObject\Customer', $entry['classname']);
        $this->assertSame('email', $entry['attributename']);

        $this->assertSame($this->configurationRepository->getNoteForAttribute('Pimcore\Model\DataObject\Customer', 'email'), $entry['note']);
        $this->assertSameSize($this->configurationRepository->getRulesForAttribute('Pimcore\Model\DataObject\Customer', 'email'), $entry['rules']);
        $this->assertSame(count($this->configurationRepository->getRulesForAttribute('Pimcore\Model\DataObject\Customer', 'email')), $entry['rules_count']);
        foreach ($entry['rules'] as $rule) {
            $this->assertArrayHasKey($rule['constraint'], $this->configurationRepository->getRulesForAttribute('Pimcore\Model\DataObject\Customer', 'email'), $rule['constraint']);
        }
    }

    public function testListClasses(): void
    {
        $response = $this->controller->listClassesAction();
        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('classes', $decoded);
        $this->assertCount(3, $decoded['classes']);

        foreach ($decoded['classes'] as $entry) {
            $this->assertArrayHasKey('name', $entry);
        }
    }

    public function testListAttributesNoClassname(): void
    {
        $response = $this->controller->listAttributesAction(
            Request::create('/', 'GET'),
            $this->configurationRepository,
            $this->getInformationFactory()
        );

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(1, $decoded);
        $this->assertArrayHasKey('attributes', $decoded);
        $this->assertCount(0, $decoded['attributes']);
    }

    public function testListAttributes(): void
    {
        $response = $this->controller->listAttributesAction(
            Request::create('/', 'GET', ['classname' => $this->classNameConfigured]),
            $this->configurationRepository,
            $this->getInformationFactory()
        );

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

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
        $this->assertCount(0, $this->configurationRepository->getConfiguredAttributes($this->className));
        $response = $this->controller->addAttributeAction(Request::create('/', 'POST'), $this->configurationRepository);

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(0, $this->configurationRepository->getConfiguredAttributes($this->className));

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertFalse($decoded['status']);
    }

    public function testAddAttributePartialData(): void
    {
        $this->assertCount(0, $this->configurationRepository->getConfiguredAttributes($this->className));
        $response = $this->controller->addAttributeAction(Request::create('/', 'POST', [
            'classname' => $this->className,
            'attributename' => $this->attributeName,
        ]), $this->configurationRepository);

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(1, $this->configurationRepository->getConfiguredAttributes($this->className));
        $this->assertTrue($this->configurationRepository->isClassConfigured($this->className));
        $this->assertTrue($this->configurationRepository->isAttributeConfigured($this->className, $this->attributeName));
        $this->assertSame([], $this->configurationRepository->getRulesForAttribute($this->className, $this->attributeName));
        $this->assertNull($this->configurationRepository->getNoteForAttribute($this->className, $this->attributeName));

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testAddAttributeCompleteData(): void
    {
        $this->assertCount(0, $this->configurationRepository->getConfiguredAttributes($this->className));
        $response = $this->controller->addAttributeAction(Request::create('/', 'POST', [
            'classname' => $this->className,
            'attributename' => $this->attributeName,
            'note' => 'NOTE',
        ]), $this->configurationRepository);

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(1, $this->configurationRepository->getConfiguredAttributes($this->className));
        $this->assertTrue($this->configurationRepository->isClassConfigured($this->className));
        $this->assertTrue($this->configurationRepository->isAttributeConfigured($this->className, $this->attributeName));
        $this->assertSame([], $this->configurationRepository->getRulesForAttribute($this->className, $this->attributeName));
        $this->assertSame('NOTE', $this->configurationRepository->getNoteForAttribute($this->className, $this->attributeName));

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testDeleteAttributeMissingData(): void
    {
        $this->assertCount(6, $this->configurationRepository->getConfiguredAttributes($this->classNameConfigured));
        $response = $this->controller->deleteAttributeAction(Request::create('/', 'POST'), $this->configurationRepository);

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(6, $this->configurationRepository->getConfiguredAttributes($this->classNameConfigured));

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertFalse($decoded['status']);
    }

    public function testDeleteAttributeCompleteData(): void
    {
        $this->assertCount(6, $this->configurationRepository->getConfiguredAttributes($this->classNameConfigured));
        $response = $this->controller->deleteAttributeAction(Request::create('/', 'POST', [
            'classname' => $this->classNameConfigured,
            'attributename' => 'name',
        ]), $this->configurationRepository);

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(5, $this->configurationRepository->getConfiguredAttributes($this->classNameConfigured));

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testListConstraints(): void
    {
        $response = $this->controller->listConstraintsAction((new ConstraintDefinitions(null)));

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

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
        $this->assertFalse($this->configurationRepository->isClassConfigured($this->className));
        $response = $this->controller->addConstraintAction(Request::create('/', 'POST', [
            'classname' => $this->className,
            'attributename' => $this->attributeName,
        ]), $this->configurationRepository);

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(0, $this->configurationRepository->getConfiguredAttributes($this->className));

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertFalse($decoded['status']);
    }

    public function testAddConstraintPartialData(): void
    {
        $this->assertCount(0, $this->configurationRepository->getConfiguredAttributes($this->className));
        $response = $this->controller->addConstraintAction(Request::create('/', 'POST', [
            'classname' => $this->className,
            'attributename' => $this->attributeName,
            'constraint' => $this->constraintName,
        ]), $this->configurationRepository);

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(1, $this->configurationRepository->getConfiguredAttributes($this->className));
        $this->assertTrue($this->configurationRepository->isClassConfigured($this->className));
        $this->assertTrue($this->configurationRepository->isAttributeConfigured($this->className, $this->attributeName));
        $this->assertSame([$this->constraintName => null], $this->configurationRepository->getRulesForAttribute($this->className, $this->attributeName));

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testAddConstraintCompleteData(): void
    {
        $this->assertCount(0, $this->configurationRepository->getConfiguredAttributes($this->className));
        $response = $this->controller->addConstraintAction(Request::create('/', 'POST', [
            'classname' => $this->className,
            'attributename' => $this->attributeName,
            'constraint' => $this->constraintName,
            'params' => json_encode($this->constraintParams, JSON_THROW_ON_ERROR),
        ]), $this->configurationRepository);

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(1, $this->configurationRepository->getConfiguredAttributes($this->className));
        $this->assertTrue($this->configurationRepository->isClassConfigured($this->className));
        $this->assertTrue($this->configurationRepository->isAttributeConfigured($this->className, $this->attributeName));
        $this->assertSame([$this->constraintName => $this->constraintParams], $this->configurationRepository->getRulesForAttribute($this->className, $this->attributeName));

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }

    public function testDeleteConstraintMissingData(): void
    {
        $this->assertCount(2, $this->configurationRepository->getRulesForAttribute($this->classNameConfigured, 'name'));
        $response = $this->controller->deleteConstraintAction(Request::create('/', 'POST', [
            'classname' => $this->classNameConfigured,
            'attributename' => 'name',
        ]), $this->configurationRepository);

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(2, $this->configurationRepository->getRulesForAttribute($this->classNameConfigured, 'name'));

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertFalse($decoded['status']);
    }

    public function testDeleteConstraintCompleteData(): void
    {
        $this->assertCount(2, $this->configurationRepository->getRulesForAttribute($this->classNameConfigured, 'name'));
        $response = $this->controller->deleteConstraintAction(Request::create('/', 'POST', [
            'classname' => $this->classNameConfigured,
            'attributename' => 'name',
            'constraint' => 'Length',
        ]), $this->configurationRepository);

        $content = $response->getContent();
        $this->assertIsString($content);
        $content = (string) $content;

        $this->assertJson($content);
        $this->assertCount(1, $this->configurationRepository->getRulesForAttribute($this->classNameConfigured, 'name'));

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $decoded);
        $this->assertTrue($decoded['status']);
    }
}
