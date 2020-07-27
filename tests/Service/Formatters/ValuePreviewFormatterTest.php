<?php

namespace Valantic\DataQualityBundle\Tests\Service\Formatters;

use Valantic\DataQualityBundle\Service\Formatters\ValueFormatter;
use Valantic\DataQualityBundle\Service\Formatters\ValuePreviewFormatter;
use Valantic\DataQualityBundle\Service\Locales\LocalesList;
use Valantic\DataQualityBundle\Tests\AbstractTestCase;

class ValuePreviewFormatterTest extends AbstractTestCase
{
    /**
     * @var ValuePreviewFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        $locales = ['de', 'en'];

        $localesList = $this->createMock(LocalesList::class);
        $localesList->method('all')->willReturn($locales);

        $this->formatter = new ValuePreviewFormatter($localesList);
    }

    public function testSimple(): void
    {
        $text = 'lorem ispum';
        $this->assertSame($text, $this->formatter->format($text));
    }

    public function testPreviewIsShorter(): void
    {
        $formatter = new ValueFormatter();
        $text = 'abcde';
        $textRepeated = str_repeat($text, 100);
        $this->assertTrue(strlen($formatter->format($textRepeated)) > strlen($this->formatter->format($textRepeated)));
    }

    public function testDefaultLocale(): void
    {
        $text = ['de' => 'Deutscher Text', 'en' => 'english text'];
        $this->assertSame($text['de'], $this->formatter->format($text));
    }

    public function testEmptyDefaultLocale(): void
    {
        $text = ['de' => '', 'en' => 'english text'];
        $this->assertSame($text['en'], $this->formatter->format($text));
    }

    public function testNoDefaultLocale(): void
    {
        $text = ['fr' => 'texte français', 'en' => 'english text'];
        $this->assertStringContainsString($text['fr'], $this->formatter->format($text));
        $this->assertStringContainsString(', ', $this->formatter->format($text));
        $this->assertStringContainsString($text['en'], $this->formatter->format($text));
    }

    public function testEmptyLocales(): void
    {
        $text = ['de' => null, 'fr' => '', 'en' => false];
        $this->assertSame('', $this->formatter->format($text));
    }

    public function testLength(): void
    {
        $text = 'abcde';
        $textRepeated = str_repeat($text, 100);
        $this->assertStringStartsWith($text, $this->formatter->format($textRepeated));
        $this->assertStringEndsWith(' […]', $this->formatter->format($textRepeated));
        $this->assertTrue(strlen($textRepeated) > strlen($this->formatter->format($textRepeated)));
        $this->assertSame(50 + 6, strlen($this->formatter->format($textRepeated)));
    }

    public function testLengthExact(): void
    {
        $text = 'a';
        $textRepeated = str_repeat($text, 50);
        $this->assertSame($textRepeated, $this->formatter->format($textRepeated));
    }


    public function testLengthOneoff(): void
    {
        $text = 'a';

        $textRepeated = str_repeat($text, 49);
        $this->assertStringEndsNotWith(' […]', $this->formatter->format($textRepeated));
        $this->assertSame($textRepeated, $this->formatter->format($textRepeated));

        $textRepeated = str_repeat($text, 51);
        $this->assertStringEndsWith(' […]', $this->formatter->format($textRepeated));
        $this->assertNotSame($textRepeated, $this->formatter->format($textRepeated));
    }
}
