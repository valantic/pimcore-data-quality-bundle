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

    protected function setUp(): void
    {
        $this->constraints = new ConstraintDefinitions([]);
    }

    public function testConstraintFormat()
    {
        $this->assertIsArray($this->constraints->all());
        $this->assertCount(49, $this->constraints->all());
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
}
