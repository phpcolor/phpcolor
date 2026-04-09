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
use PhpColor\Color\SrgbColor;
use PhpColor\Color\XyzColor;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(XyzColor::class)]
final class XyzColorTest extends ColorTestCase
{
    protected function createColor(): ColorInterface
    {
        return new XyzColor(0.5, 0.7, 0.2, 0.8);
    }

    protected function getExpectedColorClass(): string
    {
        return XyzColor::class;
    }

    public static function provideColorSamples(): iterable
    {
        // color, hex, hexWithAlpha, css
        yield 'red' => [
            new XyzColor(0.4124, 0.2126, 0.0193),
            '#ff0000',
            '#ff0000ff',
            'color(xyz-d65 0.4124 0.2126 0.0193)',
        ];
        yield 'translucent blue' => [
            new XyzColor(0.1805, 0.0722, 0.9505, 0.5),
            '#0000ff',
            '#0000ff80',
            'color(xyz-d65 0.1805 0.0722 0.9505 / 0.5)',
        ];
    }

    public static function provideFromInputs(): iterable
    {
        yield [new SrgbColor(1.0, 0.0, 0.0)];
        yield ['#ff0000'];
        yield ['color(xyz-d65 0.4 0.2 0.1)'];
    }

    public static function provideInvalidCssOutputSpaces(): array
    {
        return [
            ['lab'],
            ['lch'],
            ['oklab'],
            ['oklch'],
            ['display-p3'],
            ['a98-rgb'],
            ['rec2020'],
            ['prophoto-rgb'],
            ['hsl'],
            ['color-srgb'],
        ];
    }

    public function testAllowsNegativeValues(): void
    {
        $xyz = new XyzColor(-0.1, 1.5, -0.3, 2.0);

        $this->assertSame(-0.1, $xyz->x);
        $this->assertSame(1.5, $xyz->y);
        $this->assertSame(-0.3, $xyz->z);
        $this->assertSame(1.0, $xyz->alpha); // Alpha is still clamped
    }

    public function testChannelGetters(): void
    {
        $c = new XyzColor(0.3, 0.4, 0.5, 0.7);
        $this->assertSame(0.3, $c->getX());
        $this->assertSame(0.4, $c->getY());
        $this->assertSame(0.5, $c->getZ());
    }

    public function testConstruction(): void
    {
        $xyz = new XyzColor(0.5, 0.7, 0.2, 0.8);

        $this->assertSame(0.5, $xyz->x);
        $this->assertSame(0.7, $xyz->y);
        $this->assertSame(0.2, $xyz->z);
        $this->assertSame(0.8, $xyz->alpha);
    }

    public function testConversionToXyz(): void
    {
        $xyz = new XyzColor(0.4, 0.2, 0.1);
        $converted = $xyz->to('xyz');

        $this->assertSame($xyz, $converted);
    }

    public function testConversionToXyzD65(): void
    {
        $xyz = new XyzColor(0.4, 0.2, 0.1);
        $converted = $xyz->to('xyz-d65');

        $this->assertSame($xyz, $converted);
    }

    public function testCssOutputInXyzD65Space(): void
    {
        $color = $this->createColor();
        $css = $color->toCss('xyz-d65');
        $this->assertStringStartsWith('color(xyz-d65', $css);
    }

    public function testFromSrgbConversion(): void
    {
        $srgb = new SrgbColor(1.0, 0.0, 0.0); // Red
        $xyz = XyzColor::fromSrgb($srgb);

        $this->assertInstanceOf(XyzColor::class, $xyz);
        $this->assertGreaterThan(0.0, $xyz->x);
        $this->assertGreaterThan(0.0, $xyz->y);
        $this->assertGreaterThan(0.0, $xyz->z);
    }

    public function testRoundTripConversion(): void
    {
        $originalSrgb = new SrgbColor(0.5, 0.7, 0.2, 0.8);
        $xyz = XyzColor::fromSrgb($originalSrgb);
        $convertedSrgb = $xyz->toSrgb();

        // Allow tolerance for floating point precision
        $tolerance = 0.001;
        $this->assertEqualsWithDelta($originalSrgb->r, $convertedSrgb->r, $tolerance);
        $this->assertEqualsWithDelta($originalSrgb->g, $convertedSrgb->g, $tolerance);
        $this->assertEqualsWithDelta($originalSrgb->b, $convertedSrgb->b, $tolerance);
        $this->assertEqualsWithDelta($originalSrgb->a, $convertedSrgb->a, $tolerance);
    }

    public function testDefaultAlpha(): void
    {
        $color = new XyzColor(0.4, 0.2, 0.1);
        $this->assertEqualsWithDelta(1.0, $color->alpha, 0.01);
    }

    public function testGetAlpha(): void
    {
        $color = new XyzColor(0.4, 0.2, 0.1, 0.5);
        $this->assertEqualsWithDelta(0.5, $color->getAlpha(), 0.01);
    }

    #[\Override]
    public function testGetChannels(): void
    {
        $color = new XyzColor(0.4, 0.2, 0.1, 0.5);
        $channels = $color->getChannels();

        $this->assertArrayHasKey('x', $channels);
        $this->assertArrayHasKey('y', $channels);
        $this->assertArrayHasKey('z', $channels);
        $this->assertEqualsWithDelta(0.4, $channels['x'], 0.01);
        $this->assertEqualsWithDelta(0.2, $channels['y'], 0.01);
        $this->assertEqualsWithDelta(0.1, $channels['z'], 0.01);
    }

    #[\Override]
    public function testToHex(): void
    {
        $color = new XyzColor(0.4, 0.2, 0.1, 1.0);
        $hex = $color->toHex();

        $this->assertStringStartsWith('#', $hex);
        $this->assertSame(7, \strlen($hex));
    }

    public function testToSrgbAndBack(): void
    {
        $original = new XyzColor(0.3, 0.2, 0.1, 0.9);
        $srgb = $original->toSrgb();
        $back = XyzColor::fromSrgb($srgb);

        $this->assertEqualsWithDelta($original->x, $back->x, 0.01);
        $this->assertEqualsWithDelta($original->y, $back->y, 0.01);
        $this->assertEqualsWithDelta($original->z, $back->z, 0.01);
        $this->assertEqualsWithDelta($original->alpha, $back->alpha, 0.01);
    }
}
