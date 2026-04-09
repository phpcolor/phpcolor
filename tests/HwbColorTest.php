<?php

declare(strict_types=1);

/*
 * This file is part of the PHPColor library.
 *
 * (c) 2024-present Simon André & Raphaêl Geffroy
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpColor\Color\Tests;

use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\Exception\ParseException;
use PhpColor\Color\HwbColor;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(HwbColor::class)]
final class HwbColorTest extends TestCase
{
    public function testChannelGetters(): void
    {
        $c = new HwbColor(210.0, 0.25, 0.1, 0.6);
        $this->assertSame(210.0, $c->getHue());
        $this->assertSame(0.25, $c->getWhiteness());
        $this->assertSame(0.1, $c->getBlackness());
    }

    public function testConstructorClamps(): void
    {
        // Test that w, b, and alpha are clamped to [0,1]
        $hwb = new HwbColor(0, 1.5, -0.5, 2.0);

        $this->assertEqualsWithDelta(1.0, $hwb->w, 0.001);
        $this->assertEqualsWithDelta(0.0, $hwb->b, 0.001);
        $this->assertEqualsWithDelta(1.0, $hwb->alpha, 0.001);
    }

    public function testConstructorNormalizesHue(): void
    {
        // Test hue normalization - negative hue
        $hwb1 = new HwbColor(-90, 0.0, 0.0);
        $this->assertEqualsWithDelta(270, $hwb1->h, 0.001);

        // Test hue normalization - hue > 360
        $hwb2 = new HwbColor(450, 0.0, 0.0);
        $this->assertEqualsWithDelta(90, $hwb2->h, 0.001);
    }

    public function testFromChannels(): void
    {
        $channels = ['h' => 240, 'w' => 0.2, 'b' => 0.3];
        $hwb = HwbColor::fromChannels($channels, 0.9);

        $this->assertEqualsWithDelta(240, $hwb->h, 0.001);
        $this->assertEqualsWithDelta(0.2, $hwb->w, 0.001);
        $this->assertEqualsWithDelta(0.3, $hwb->b, 0.001);
        $this->assertEqualsWithDelta(0.9, $hwb->alpha, 0.001);
    }

    public function testFromChannelsThrowsOnMissingChannels(): void
    {
        $this->expectException(InvalidColorException::class);
        $this->expectExceptionMessage('Missing HWB channels');

        HwbColor::fromChannels(['h' => 180, 'w' => 0.5]);
    }

    public function testFromSrgb(): void
    {
        // Test conversion from pure red
        $srgb = new SrgbColor(1.0, 0.0, 0.0);
        $hwb = HwbColor::fromSrgb($srgb);

        $this->assertEqualsWithDelta(0.0, $hwb->h, 0.1);
        $this->assertEqualsWithDelta(0.0, $hwb->w, 0.001);
        $this->assertEqualsWithDelta(0.0, $hwb->b, 0.001);
    }

    public function testFromSrgbPreservesAlpha(): void
    {
        $srgb = new SrgbColor(1.0, 0.5, 0.0, 0.7);
        $hwb = HwbColor::fromSrgb($srgb);

        $this->assertEqualsWithDelta(0.7, $hwb->alpha, 0.001);
    }

    public function testFromSrgbWithGray(): void
    {
        // Gray should result in w = gray level, b = 1 - gray level
        $srgb = new SrgbColor(0.5, 0.5, 0.5);
        $hwb = HwbColor::fromSrgb($srgb);

        $this->assertEqualsWithDelta(0.5, $hwb->w, 0.001);
        $this->assertEqualsWithDelta(0.5, $hwb->b, 0.001);
    }

    public function testFromSrgbWithPureGreen(): void
    {
        $srgb = new SrgbColor(0.0, 1.0, 0.0);
        $hwb = HwbColor::fromSrgb($srgb);

        $this->assertEqualsWithDelta(120.0, $hwb->h, 0.1);
        $this->assertEqualsWithDelta(0.0, $hwb->w, 0.001);
        $this->assertEqualsWithDelta(0.0, $hwb->b, 0.001);
    }

    public function testGetAlpha(): void
    {
        $hwb = new HwbColor(0, 0.0, 0.0, 0.75);
        $this->assertEqualsWithDelta(0.75, $hwb->getAlpha(), 0.001);
    }

    public function testGetChannels(): void
    {
        $hwb = new HwbColor(180, 0.3, 0.4, 0.8);
        $channels = $hwb->getChannels();

        $this->assertArrayHasKey('h', $channels);
        $this->assertArrayHasKey('w', $channels);
        $this->assertArrayHasKey('b', $channels);
        $this->assertEqualsWithDelta(180, $channels['h'], 0.001);
        $this->assertEqualsWithDelta(0.3, $channels['w'], 0.001);
        $this->assertEqualsWithDelta(0.4, $channels['b'], 0.001);
    }

    public function testGetSpaceName(): void
    {
        $this->assertSame('hwb', HwbColor::getSpaceName());
    }

    public function testInternalNegativeHueBranchCoverage(): void
    {
        // Force-cover the negative-hue path inside toSrgb() by bypassing the constructor
        // so we can set a negative hue directly (constructor would normalize it).
        $ref = new \ReflectionClass(HwbColor::class);
        /** @var HwbColor $obj */
        $obj = $ref->newInstanceWithoutConstructor();

        // Prepare a valid HWB but with negative fractional turn
        // Expect it to behave like the wrapped positive equivalent (-0.25 turn == 270°)
        $hProp = $ref->getProperty('h');
        $wProp = $ref->getProperty('w');
        $bProp = $ref->getProperty('b');
        $aProp = $ref->getProperty('alpha');
        $hProp->setValue($obj, -0.25);
        $wProp->setValue($obj, 0.2);
        $bProp->setValue($obj, 0.2);
        $aProp->setValue($obj, 1.0);

        $expected = (new HwbColor(270.0, 0.2, 0.2, 1.0))->toSrgb();
        $actual = $obj->toSrgb();

        $this->assertEqualsWithDelta($expected->r, $actual->r, 1e-9);
        $this->assertEqualsWithDelta($expected->g, $actual->g, 1e-9);
        $this->assertEqualsWithDelta($expected->b, $actual->b, 1e-9);
        $this->assertEqualsWithDelta($expected->a, $actual->a, 1e-12);
    }

    #[DataProvider('provideInvalidHwbStrings')]
    public function testParseInvalidHwbString(string $hwbString): void
    {
        $this->expectException(ParseException::class);
        HwbColor::parse($hwbString);
    }

    public static function provideInvalidHwbStrings(): iterable
    {
        yield 'invalid string' => ['hwb(foo)'];
        yield 'missing parenthesis' => ['hwb(120 50% 20%'];
        yield 'too few values' => ['hwb(120 50%)'];
        yield 'non-percentage whiteness' => ['hwb(120 50 20%)'];
        yield 'non-percentage blackness' => ['hwb(120 50% 20)'];
    }

    #[DataProvider('provideValidHwbStrings')]
    public function testParseValidHwbString(string $hwbString, float $h, float $w, float $b, float $a): void
    {
        $color = HwbColor::parse($hwbString);

        $this->assertEqualsWithDelta($h, $color->h, 0.001);
        $this->assertEqualsWithDelta($w, $color->w, 0.001);
        $this->assertEqualsWithDelta($b, $color->b, 0.001);
        $this->assertEqualsWithDelta($a, $color->alpha, 0.001);
    }

    public static function provideValidHwbStrings(): iterable
    {
        yield 'hwb with space-separated values' => ['hwb(120 50% 20%)', 120.0, 0.5, 0.2, 1.0];
        yield 'hwb with alpha' => ['hwb(120 50% 20% / 0.8)', 120.0, 0.5, 0.2, 0.8];
        yield 'hwb with deg unit' => ['hwb(90deg 25% 25%)', 90.0, 0.25, 0.25, 1.0];
        yield 'hwb with rad unit' => ['hwb(1.5708rad 0% 0%)', 90.0, 0.0, 0.0, 1.0];
        yield 'hwb with grad unit' => ['hwb(100grad 100% 0%)', 90.0, 1.0, 0.0, 1.0];
        yield 'hwb with turn unit' => ['hwb(0.25turn 0% 100%)', 90.0, 0.0, 1.0, 1.0];
    }

    public function testParseWithPercentageAlpha(): void
    {
        $hwb = HwbColor::parse('hwb(120 30% 20% / 50%)');

        $this->assertEqualsWithDelta(120, $hwb->h, 0.001);
        $this->assertEqualsWithDelta(0.3, $hwb->w, 0.001);
        $this->assertEqualsWithDelta(0.2, $hwb->b, 0.001);
        $this->assertEqualsWithDelta(0.5, $hwb->alpha, 0.001);
    }

    public function testRoundTripConversion(): void
    {
        // Test that converting to SRGB and back maintains color fidelity
        // Use a color with low w+b so hue information is preserved
        $originalHwb = new HwbColor(210, 0.0, 0.0);
        $srgb = $originalHwb->toSrgb();
        $convertedHwb = HwbColor::fromSrgb($srgb);

        $this->assertEqualsWithDelta($originalHwb->h, $convertedHwb->h, 1.0);
        $this->assertEqualsWithDelta($originalHwb->w, $convertedHwb->w, 0.01);
        $this->assertEqualsWithDelta($originalHwb->b, $convertedHwb->b, 0.01);
    }

    public function testToCss(): void
    {
        $hwb = new HwbColor(180, 0.25, 0.30);
        $css = $hwb->toCss();

        $this->assertStringContainsString('hwb(', $css);
        $this->assertStringContainsString('180', $css);
        $this->assertStringContainsString('25%', $css);
        $this->assertStringContainsString('30%', $css);
    }

    public function testToCssIgnoresOtherSpacesAndKeepsHwbFormat(): void
    {
        $hwb = new HwbColor(200, 0.4, 0.1);

        $cssOklab = $hwb->toCss('oklab');
        $cssSrgb = $hwb->toCss('srgb');

        $this->assertStringStartsWith('hwb(', $cssOklab);
        $this->assertStringStartsWith('hwb(', $cssSrgb);
        $this->assertStringContainsString('200', $cssOklab);
        $this->assertStringContainsString('40%', $cssOklab);
        $this->assertStringContainsString('10%', $cssOklab);
    }

    public function testToCssWithExplicitHwbSpace(): void
    {
        $hwb = new HwbColor(90, 0.1, 0.2);
        $css = $hwb->toCss('hwb');

        $this->assertStringContainsString('hwb(', $css);
        $this->assertStringContainsString('90', $css);
        $this->assertStringContainsString('10%', $css);
        $this->assertStringContainsString('20%', $css);
    }

    public function testToSrgb(): void
    {
        $hwb = new HwbColor(120, 0.0, 0.0);
        $srgb = $hwb->toSrgb();

        // Pure green
        $this->assertEqualsWithDelta(0.0, $srgb->r, 0.001);
        $this->assertEqualsWithDelta(1.0, $srgb->g, 0.001);
        $this->assertEqualsWithDelta(0.0, $srgb->b, 0.001);
    }

    public function testToSrgbWhenWhitenessAndBlacknessExceedOne(): void
    {
        // When w + b >= 1.0, result should be gray
        $hwb = new HwbColor(0, 0.6, 0.5);
        $srgb = $hwb->toSrgb();

        // Should be a gray color where r = g = b
        $this->assertEqualsWithDelta($srgb->r, $srgb->g, 0.001);
        $this->assertEqualsWithDelta($srgb->g, $srgb->b, 0.001);
    }

    public function testToSrgbWithAlpha(): void
    {
        $hwb = new HwbColor(180, 0.2, 0.2, 0.5);
        $srgb = $hwb->toSrgb();

        $this->assertEqualsWithDelta(0.5, $srgb->a, 0.001);
    }

    #[DataProvider('provideDifferentHues')]
    public function testToSrgbWithDifferentHues(float $hue, float $expectedR, float $expectedG, float $expectedB): void
    {
        $hwb = new HwbColor($hue, 0.0, 0.0);
        $srgb = $hwb->toSrgb();

        $this->assertEqualsWithDelta($expectedR, $srgb->r, 0.001);
        $this->assertEqualsWithDelta($expectedG, $srgb->g, 0.001);
        $this->assertEqualsWithDelta($expectedB, $srgb->b, 0.001);
    }

    public static function provideDifferentHues(): iterable
    {
        yield 'red (0°)' => [0, 1.0, 0.0, 0.0];
        yield 'yellow (60°)' => [60, 1.0, 1.0, 0.0];
        yield 'green (120°)' => [120, 0.0, 1.0, 0.0];
        yield 'cyan (180°)' => [180, 0.0, 1.0, 1.0];
        yield 'blue (240°)' => [240, 0.0, 0.0, 1.0];
        yield 'magenta (300°)' => [300, 1.0, 0.0, 1.0];
    }

    // NOTE: negative turn handling is implementation-defined; covered by constructor tests

    public function testToSrgbWithWhitenessAndBlackness(): void
    {
        // HWB with whiteness and blackness
        $hwb = new HwbColor(240, 0.3, 0.3);
        $srgb = $hwb->toSrgb();

        $this->assertEqualsWithDelta(0.3, $srgb->r, 0.01);
        $this->assertEqualsWithDelta(0.3, $srgb->g, 0.01);
        $this->assertEqualsWithDelta(0.7, $srgb->b, 0.01);
    }

    public function testToSrgbWrapsNegativeHue(): void
    {
        // Negative hue triggers the wrap-around branch in toSrgb()
        $cNeg = new HwbColor(-30.0, 0.2, 0.2, 1.0);
        $cPos = new HwbColor(330.0, 0.2, 0.2, 1.0); // -30° == 330°

        $srgbNeg = $cNeg->toSrgb();
        $srgbPos = $cPos->toSrgb();

        // Assert equality with the equivalent wrapped hue
        $this->assertEqualsWithDelta($srgbPos->r, $srgbNeg->r, 1e-9);
        $this->assertEqualsWithDelta($srgbPos->g, $srgbNeg->g, 1e-9);
        $this->assertEqualsWithDelta($srgbPos->b, $srgbNeg->b, 1e-9);
        $this->assertEqualsWithDelta($srgbPos->a, $srgbNeg->a, 1e-12);
    }
}
