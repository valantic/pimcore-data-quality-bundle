<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Tests\Service\Formatters;

use Pimcore\Bundle\AdminBundle\Security\User\TokenStorageUserResolver;
use Pimcore\Model\User;
use Valantic\DataQualityBundle\Service\Formatters\ValueFormatter;
use Valantic\DataQualityBundle\Service\Formatters\ValuePreviewFormatter;
use Valantic\DataQualityBundle\Tests\AbstractTestCase;

class ValuePreviewFormatterTest extends AbstractTestCase
{
    protected ValuePreviewFormatter $formatter;

    protected function setUp(): void
    {
        $user = (new User())
            ->setId(1)
            ->setLanguage('en');

        $userResolver = $this->createMock(TokenStorageUserResolver::class);
        $userResolver->method('getUser')->willReturn($user);

        $this->formatter = new ValuePreviewFormatter($userResolver);
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
        $user = (new User())
            ->setId(1)
            ->setLanguage('de');

        $userResolver = $this->createMock(TokenStorageUserResolver::class);
        $userResolver->method('getUser')->willReturn($user);

        $formatter = new ValuePreviewFormatter($userResolver);

        $text = ['de' => 'Deutscher Text', 'en' => 'english text'];
        $this->assertSame($text['de'], $formatter->format($text));
    }

    public function testEmptyDefaultLocale(): void
    {
        $text = ['de' => '', 'en' => 'english text'];
        $this->assertSame($text['en'], $this->formatter->format($text));
    }

    public function testNoDefaultLocale(): void
    {
        $text = ['fr' => 'texte français', 'de' => 'german text'];
        $this->assertStringContainsString($text['fr'], $this->formatter->format($text));
        $this->assertStringContainsString(', ', $this->formatter->format($text));
        $this->assertStringContainsString($text['de'], $this->formatter->format($text));
    }

    public function testEmptyLocales(): void
    {
        $text = ['fr' => ''];
        $this->assertSame('', $this->formatter->format($text));
    }

    public function testInvalidLocales(): void
    {
        $text = ['de' => null, 'en' => false];
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
