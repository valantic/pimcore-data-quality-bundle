<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Tests\Service\Formatters;

use Valantic\DataQualityBundle\Service\Formatters\ValueFormatter;
use Valantic\DataQualityBundle\Tests\AbstractTestCase;

class ValueFormatterTest extends AbstractTestCase
{
    public function testSimple(): void
    {
        $formatter = new ValueFormatter();
        $text = 'lorem ispum';
        $this->assertSame($text, $formatter->format($text));
    }

    public function testTrim(): void
    {
        $formatter = new ValueFormatter();
        $text = ' abcde   ';
        $this->assertSame('abcde', $formatter->format($text));
    }

    public function testTrimArray(): void
    {
        $formatter = new ValueFormatter();
        $text = [' abcde  '];
        $this->assertSame('abcde', $formatter->format($text)[0]);
    }

    public function testLength(): void
    {
        $formatter = new ValueFormatter();
        $text = 'abcde';
        $textRepeated = str_repeat($text, 100);
        $this->assertStringStartsWith($text, $formatter->format($textRepeated));
        $this->assertStringEndsWith(' […]', $formatter->format($textRepeated));
        $this->assertTrue(strlen($textRepeated) > strlen((string) $formatter->format($textRepeated)));
        $this->assertSame(80 + 6, strlen((string) $formatter->format($textRepeated)));
    }

    public function testLengthExact(): void
    {
        $formatter = new ValueFormatter();
        $text = 'a';
        $textRepeated = str_repeat($text, 80);
        $this->assertSame($textRepeated, $formatter->format($textRepeated));
    }

    public function testLengthOneoff(): void
    {
        $formatter = new ValueFormatter();
        $text = 'a';

        $textRepeated = str_repeat($text, 79);
        $this->assertStringEndsNotWith(' […]', $formatter->format($textRepeated));
        $this->assertSame($textRepeated, $formatter->format($textRepeated));

        $textRepeated = str_repeat($text, 81);
        $this->assertStringEndsWith(' […]', $formatter->format($textRepeated));
        $this->assertNotSame($textRepeated, $formatter->format($textRepeated));
    }

    public function testTags(): void
    {
        $formatter = new ValueFormatter();
        $text = '<p>Hello world</p>';
        $this->assertStringNotContainsString('<p>', $formatter->format($text));
        $this->assertSame('Hello world', $formatter->format($text));
    }

    public function testTagsArray(): void
    {
        $formatter = new ValueFormatter();
        $text = ['<p>Hello world</p>'];
        $this->assertStringNotContainsString('<p>', $formatter->format($text)[0]);
        $this->assertSame('Hello world', $formatter->format($text)[0]);
    }

    public function testArraySimple(): void
    {
        $formatter = new ValueFormatter();
        $text = ['lorem ispum'];
        $this->assertSame($text, $formatter->format($text));
    }

    public function testArrayLength(): void
    {
        $formatter = new ValueFormatter();
        $text = ['abcde', '12345'];
        $textRepeated = [str_repeat('abcde', 100), str_repeat('12345', 100)];
        $this->assertSameSize($textRepeated, $formatter->format($textRepeated));
        foreach ($text as $i => $t) {
            $this->assertStringStartsWith($t, $formatter->format($textRepeated)[$i]);
            $this->assertStringEndsWith(' […]', $formatter->format($textRepeated)[$i]);
            $this->assertTrue(strlen($textRepeated[$i]) > strlen((string) $formatter->format($textRepeated)[$i]));
        }
    }

    public function testArrayTags(): void
    {
        $formatter = new ValueFormatter();
        $text = '<p>Hello world</p>';
        $this->assertStringNotContainsString('<p>', $formatter->format($text));
        $this->assertSame('Hello world', $formatter->format($text));
    }
}
