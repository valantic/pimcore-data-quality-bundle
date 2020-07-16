<?php

namespace Valantic\DataQualityBundle\Tests\Shared;

use PHPUnit\Framework\TestCase;

class SafeArrayTest extends TestCase
{
    protected $obj;

    protected function setUp(): void
    {
        $this->obj = new SafeArrayImplementation();
    }

    public function testNotAnArray()
    {
        $this->assertSame([], $this->obj->get(0, 0));
        $this->assertSame([], $this->obj->get(null, null));
        $this->assertSame([], $this->obj->get('', ''));
        $this->assertSame([], $this->obj->get(new \stdClass(), new \stdClass()));
    }

    public function testMissingKey()
    {
        $this->assertSame([], $this->obj->get([], 0));
        $this->assertSame([], $this->obj->get([], 1));
        $this->assertSame([], $this->obj->get([], ''));
        $this->assertSame([], $this->obj->get([], '1'));
    }

    public function testSubarrayNotAnArray()
    {
        $this->assertSame([], $this->obj->get([0], 0));
        $this->assertSame([], $this->obj->get([null], null));
        $this->assertSame([], $this->obj->get([''], ''));
        $this->assertSame([], $this->obj->get([new \stdClass()], 0));
    }

    public function testValidSubarray()
    {
        $this->assertSame([0], $this->obj->get([[0], [1]], 0));
        $this->assertSame([1], $this->obj->get([[0], [1]], 1));

        $this->assertSame([2], $this->obj->get([1 => [1], 2 => [2]], 2));
    }
}
