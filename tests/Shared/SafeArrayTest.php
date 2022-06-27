<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Tests\Shared;

use PHPUnit\Framework\TestCase;

class SafeArrayTest extends TestCase
{
    protected SafeArrayImplementation $obj;

    protected function setUp(): void
    {
        $this->obj = new SafeArrayImplementation();
    }

    public function testNotAnArray(): void
    {
        $this->assertSame([], $this->obj->get(0, 0));
        $this->assertSame([], $this->obj->get(null, null));
        $this->assertSame([], $this->obj->get('', ''));
    }

    public function testMissingKey(): void
    {
        $this->assertSame([], $this->obj->get([], 0));
        $this->assertSame([], $this->obj->get([], 1));
        $this->assertSame([], $this->obj->get([], ''));
        $this->assertSame([], $this->obj->get([], '1'));
    }

    public function testSubarrayNotAnArray(): void
    {
        $this->assertSame([], $this->obj->get([0], 0));
        $this->assertSame([], $this->obj->get([null], null));
        $this->assertSame([], $this->obj->get([''], ''));
        $this->assertSame([], $this->obj->get([new \stdClass()], 0));
    }

    public function testValidSubarray(): void
    {
        $this->assertSame([0], $this->obj->get([[0], [1]], 0));
        $this->assertSame([1], $this->obj->get([[0], [1]], 1));

        $this->assertSame([2], $this->obj->get([1 => [1], 2 => [2]], 2));
    }
}
