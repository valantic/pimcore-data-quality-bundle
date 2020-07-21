<?php

namespace Valantic\DataQualityBundle\Tests\Service\Formatters;

use Valantic\DataQualityBundle\Service\Formatters\ValueFormatter;
use Valantic\DataQualityBundle\Tests\AbstractTestCase;

class ValueFormatterTest extends AbstractTestCase
{
    public function testSimple()
    {
        $formatter = new ValueFormatter();
        $text = 'lorem ispum';
        $this->assertSame($text, $formatter->format($text));
    }

    public function testLength()
    {
        $formatter = new ValueFormatter();
        $text = 'abcde';
        $textRepeated = str_repeat($text, 100);
        $this->assertStringStartsWith($text, $formatter->format($textRepeated));
        $this->assertStringEndsWith(' […]', $formatter->format($textRepeated));
        $this->assertTrue(strlen($textRepeated) > strlen($formatter->format($textRepeated)));
    }

    public function testTags()
    {
        $formatter = new ValueFormatter();
        $text = '<p>Hello world</p>';
        $this->assertStringNotContainsString('<p>', $formatter->format($text));
        $this->assertSame('Hello world', $formatter->format($text));
    }

    public function testArraySimple()
    {
        $formatter = new ValueFormatter();
        $text = ['lorem ispum'];
        $this->assertSame($text, $formatter->format($text));
    }

    public function testArrayLength()
    {
        $formatter = new ValueFormatter();
        $text = ['abcde', '12345'];
        $textRepeated = [str_repeat('abcde', 100), str_repeat('12345', 100)];
        $this->assertSameSize($textRepeated, $formatter->format($textRepeated));
        foreach ($text as $i => $t) {
            $this->assertStringStartsWith($t, $formatter->format($textRepeated)[$i]);
            $this->assertStringEndsWith(' […]', $formatter->format($textRepeated)[$i]);
            $this->assertTrue(strlen($textRepeated[$i]) > strlen($formatter->format($textRepeated)[$i]));
        }
    }

    public function testArrayTags()
    {
        $formatter = new ValueFormatter();
        $text = '<p>Hello world</p>';
        $this->assertStringNotContainsString('<p>', $formatter->format($text));
        $this->assertSame('Hello world', $formatter->format($text));
    }
}
