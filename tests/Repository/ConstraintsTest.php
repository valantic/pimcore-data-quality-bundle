<?php

namespace Valantic\DataQualityBundle\Tests\Repository;

use Valantic\DataQualityBundle\Repository\ConstraintDefinitions;
use Valantic\DataQualityBundle\Tests\AbstractTestCase;

class ConstraintsTest extends AbstractTestCase
{
    // TODO: add tests for custom constraints

    /**
     * @var ConstraintDefinitions
     */
    protected $constraints;

    /**
     * @var SampleConstraintFull
     */
    protected $customConstraintFull;

    /**
     * @var SampleConstraintMinimal
     */
    protected $customConstraintMinimal;

    protected function setUp(): void
    {
        $this->customConstraintFull = new SampleConstraintFull();
        $this->customConstraintMinimal = new SampleConstraintMinimal();
        $iter = new \ArrayObject([
            'string',
            123,
            false,
            null,
            SampleConstraintFull::class,
            $this->customConstraintFull,
            $this->customConstraintMinimal,
        ]);
        $this->constraints = new ConstraintDefinitions($iter);
    }

    public function testConstraintFormat()
    {
        $this->assertIsArray($this->constraints->all());
        $this->assertCount(51, $this->constraints->all());
        foreach ($this->constraints->all() as $name => $constraint) {
            $this->assertIsArray($constraint, $name);
            if (array_key_exists('parameters', $constraint)) {
                $parameters = $constraint['parameters'];
                $this->assertIsArray($parameters, $name);

                if (array_key_exists('default', $parameters)) {
                    $this->assertIsString($parameters['default'], $name);
                }
                if (array_key_exists('required', $parameters)) {
                    $this->assertIsArray($parameters['required'], $name);
                }
                if (array_key_exists('optional', $parameters)) {
                    $this->assertIsArray($parameters['optional'], $name);
                }
            }
        }
    }

    public function testCustomConstraintFull()
    {
        $this->assertIsArray($this->constraints->all());
        $this->assertArrayHasKey(SampleConstraintFull::class, $this->constraints->all());
        $repoConstraint = $this->constraints->all()[SampleConstraintFull::class];
        $this->assertIsArray($repoConstraint);
        $this->assertSame($this->customConstraintFull->getLabel(), $repoConstraint['label']);
        $this->assertSame($this->customConstraintFull->getDefaultOption(), $repoConstraint['parameters']['default']);
        $this->assertSame($this->customConstraintFull->requiredParameters(), $repoConstraint['parameters']['required']);
        $this->assertSame($this->customConstraintFull->optionalParameters(), $repoConstraint['parameters']['optional']);
    }

    public function testCustomConstraintMinimal()
    {
        $this->assertIsArray($this->constraints->all());
        $this->assertArrayHasKey(SampleConstraintMinimal::class, $this->constraints->all());
        $repoConstraint = $this->constraints->all()[SampleConstraintMinimal::class];
        $this->assertIsArray($repoConstraint);
        $this->assertSame('SampleConstraintMinimal', $repoConstraint['label']);
        $this->assertArrayNotHasKey('default', $repoConstraint['parameters']);
        $this->assertArrayNotHasKey('required', $repoConstraint['parameters']);
        $this->assertArrayNotHasKey('optional', $repoConstraint['parameters']);
    }
}
