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

use PhpColor\Color\ColorInterface;
use PhpColor\Color\DisplayP3Color;
use PhpColor\Color\OklabColor;
use PhpColor\Color\OklchColor;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DisplayP3Color::class)]
final class DisplayP3ColorTest extends ColorTestCase
{
    protected function createColor(): ColorInterface
    {
        return new DisplayP3Color(0.5, 0.7, 0.2, 0.8);
    }

    protected function getExpectedColorClass(): string
    {
        return DisplayP3Color::class;
    }

    public static function provideColorSamples(): iterable
    {
        // color, hex, hexWithAlpha, css
        yield 'red' => [
            new DisplayP3Color(0.925, 0.204, 0.137),
            '#ff0200',
            '#ff0200ff',
            'color(display-p3 0.925 0.204 0.137)',
        ];
        yield 'translucent green' => [
            new DisplayP3Color(0.455, 0.91, 0.271, 0.5),
            '#32eb00',
            '#32eb0080',
            'color(display-p3 0.455 0.91 0.271 / 0.5)',
        ];
    }

    public static function provideFromInputs(): iterable
    {
        yield [new SrgbColor(1.0, 0.0, 0.0)];
        yield ['#ff0000'];
        yield ['color(display-p3 0.8 0.3 0.1)'];
    }

    public static function provideInvalidCssOutputSpaces(): array
    {
        return [
            ['lab'],
            ['lch'],
            ['oklab'],
            ['oklch'],
            ['xyz'],
            ['a98-rgb'],
            ['rec2020'],
            ['prophoto-rgb'],
            ['hsl'],
            ['color-srgb'],
        ];
    }

    public function testChannelGetters(): void
    {
        $c = new DisplayP3Color(0.11, 0.22, 0.33, 0.44);
        $this->assertSame(0.11, $c->getRed());
        $this->assertSame(0.22, $c->getGreen());
        $this->assertSame(0.33, $c->getBlue());
    }

    public function testChannelsAreClamped(): void
    {
        $p3 = new DisplayP3Color(-0.1, 1.5, 0.5, 2.0);

        $this->assertSame(0.0, $p3->r);
        $this->assertSame(1.0, $p3->g);
        $this->assertSame(0.5, $p3->b);
        $this->assertSame(1.0, $p3->a);
    }

    public function testConstruction(): void
    {
        $p3 = new DisplayP3Color(0.5, 0.7, 0.2, 0.8);

        $this->assertSame(0.5, $p3->r);
        $this->assertSame(0.7, $p3->g);
        $this->assertSame(0.2, $p3->b);
        $this->assertSame(0.8, $p3->a);
    }

    public function testConversionToDisplayP3(): void
    {
        $p3 = new DisplayP3Color(0.8, 0.3, 0.1);
        $converted = $p3->to('display-p3');

        $this->assertSame($p3, $converted);
    }

    public function testConversionToDisplayP3Alternative(): void
    {
        $p3 = new DisplayP3Color(0.8, 0.3, 0.1);
        $converted = $p3->to('displayp3');

        $this->assertSame($p3, $converted);
    }

    public function testConversionToOklab(): void
    {
        $p3 = new DisplayP3Color(0.8, 0.3, 0.1);
        $oklab = $p3->to('oklab');

        $this->assertInstanceOf(OklabColor::class, $oklab);
    }

    public function testConversionToOklch(): void
    {
        $p3 = new DisplayP3Color(0.8, 0.3, 0.1);
        $oklch = $p3->to('oklch');

        $this->assertInstanceOf(OklchColor::class, $oklch);
    }

    public function testConversionToSrgb(): void
    {
        $p3 = new DisplayP3Color(0.8, 0.3, 0.1);
        $srgb = $p3->to('srgb');

        $this->assertInstanceOf(SrgbColor::class, $srgb);
    }

    public function testCssOutputInDisplayP3Space(): void
    {
        $color = $this->createColor();
        $css = $color->toCss('display-p3');
        $this->assertStringStartsWith('color(display-p3', $css);
    }

    public function testFromSrgbConversion(): void
    {
        $srgb = new SrgbColor(1.0, 0.0, 0.0); // Red
        $p3 = DisplayP3Color::fromSrgb($srgb);

        $this->assertInstanceOf(DisplayP3Color::class, $p3);
        $this->assertGreaterThan(0.5, $p3->r); // Should maintain red color
        $this->assertLessThan(0.3, $p3->g);   // Should have some green due to gamut differences
        $this->assertLessThan(0.2, $p3->b);   // Should have some blue due to gamut differences
    }

    public function testRoundTripConversion(): void
    {
        $originalSrgb = new SrgbColor(0.5, 0.7, 0.2, 0.8);
        $p3 = DisplayP3Color::fromSrgb($originalSrgb);
        $convertedSrgb = $p3->toSrgb();

        // Allow tolerance for floating point precision and gamut mapping
        $tolerance = 0.01;
        $this->assertEqualsWithDelta($originalSrgb->r, $convertedSrgb->r, $tolerance);
        $this->assertEqualsWithDelta($originalSrgb->g, $convertedSrgb->g, $tolerance);
        $this->assertEqualsWithDelta($originalSrgb->b, $convertedSrgb->b, $tolerance);
        $this->assertEqualsWithDelta($originalSrgb->a, $convertedSrgb->a, $tolerance);
    }

    public function testRoundTripPreservesWideGamutColors(): void
    {
        // Pure P3 Red (1, 0, 0) is outside sRGB gamut
        $p3 = new DisplayP3Color(1.0, 0.0, 0.0);

        // Convert to sRGB (should not be clamped)
        $srgb = $p3->toSrgb();

        $this->assertGreaterThan(1.0, $srgb->r, 'Red channel should be > 1.0 in sRGB');
        $this->assertLessThan(0.0, $srgb->g, 'Green channel should be < 0.0 in sRGB');
        $this->assertLessThan(0.0, $srgb->b, 'Blue channel should be < 0.0 in sRGB');

        // Convert back to P3
        $p3Restored = DisplayP3Color::fromSrgb($srgb);

        $this->assertEqualsWithDelta(1.0, $p3Restored->r, 0.01);
        $this->assertEqualsWithDelta(0.0, $p3Restored->g, 0.01);
        $this->assertEqualsWithDelta(0.0, $p3Restored->b, 0.01);
    }

    public function testDefaultAlpha(): void
    {
        $color = new DisplayP3Color(0.8, 0.3, 0.1);
        $this->assertEqualsWithDelta(1.0, $color->a, 0.01);
    }

    public function testGetAlpha(): void
    {
        $color = new DisplayP3Color(0.8, 0.3, 0.1, 0.6);
        $this->assertEqualsWithDelta(0.6, $color->getAlpha(), 0.01);
    }

    #[\Override]
    public function testGetChannels(): void
    {
        $color = new DisplayP3Color(0.8, 0.3, 0.1, 0.6);
        $channels = $color->getChannels();

        $this->assertArrayHasKey('r', $channels);
        $this->assertArrayHasKey('g', $channels);
        $this->assertArrayHasKey('b', $channels);
        $this->assertEqualsWithDelta(0.8, $channels['r'], 0.01);
        $this->assertEqualsWithDelta(0.3, $channels['g'], 0.01);
        $this->assertEqualsWithDelta(0.1, $channels['b'], 0.01);
    }

    #[\Override]
    public function testToHex(): void
    {
        $color = new DisplayP3Color(0.8, 0.3, 0.1, 1.0);
        $hex = $color->toHex();

        $this->assertStringStartsWith('#', $hex);
        $this->assertSame(7, \strlen($hex));
    }

    public function testToSrgbAndBack(): void
    {
        $original = new DisplayP3Color(0.8, 0.3, 0.1, 0.7);
        $srgb = $original->toSrgb();
        $back = DisplayP3Color::fromSrgb($srgb);

        $this->assertEqualsWithDelta($original->r, $back->r, 0.1);
        $this->assertEqualsWithDelta($original->g, $back->g, 0.1);
        $this->assertEqualsWithDelta($original->b, $back->b, 0.1);
        $this->assertEqualsWithDelta($original->a, $back->a, 0.01);
    }
}
