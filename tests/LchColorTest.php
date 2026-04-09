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
use PhpColor\Color\Exception\ParseException;
use PhpColor\Color\LabColor;
use PhpColor\Color\LchColor;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(LchColor::class)]
final class LchColorTest extends ColorTestCase
{
    protected function createColor(): ColorInterface
    {
        return new LchColor(50.0, 30.0, 120.0);
    }

    protected function getExpectedColorClass(): string
    {
        return LchColor::class;
    }

    public static function provideColorSamples(): iterable
    {
        // color, hex, hexWithAlpha, css
        yield 'red' => [
            new LchColor(53.24, 104.55, 40.0),
            '#ff0000',
            '#ff0000ff',
            'lch(53.24 104.55 40)',
        ];
        yield 'translucent blue' => [
            new LchColor(32.3, 133.81, 306.28, 0.5),
            '#0000ff',
            '#0000ff80',
            'lch(32.3 133.81 306.28 / 0.5)',
        ];
    }

    public static function provideFromInputs(): iterable
    {
        yield [new SrgbColor(1.0, 0.0, 0.0)];
        yield ['#ff0000'];
        yield ['lch(50 40 120)'];
    }

    public static function provideInvalidCssOutputSpaces(): array
    {
        return [
            ['oklab'],
            ['oklch'],
            ['display-p3'],
            ['xyz'],
            ['a98-rgb'],
            ['rec2020'],
            ['prophoto-rgb'],
            ['hsl'],
            ['color'],
        ];
    }

    public function testAchromaticHueReturnsZero(): void
    {
        $gray = new LchColor(50.0, 0.0, 200.0);
        $this->assertSame(0.0, $gray->getChroma());
        $this->assertSame(0.0, $gray->getHue());
    }

    public function testChannelGetters(): void
    {
        $c = new LchColor(60.0, 40.0, 120.0, 0.6);
        $this->assertSame(60.0, $c->getLightness());
        $this->assertSame(40.0, $c->getChroma());
        $this->assertSame(120.0, $c->getHue());
    }

    public function testChromaIsClamped(): void
    {
        $lch = new LchColor(50.0, -10.0, 0.0);
        $this->assertSame(0.0, $lch->c);
    }

    public function testConstruction(): void
    {
        $lch = new LchColor(75.0, 40.0, 240.0, 0.8);

        $this->assertSame(75.0, $lch->l);
        $this->assertSame(40.0, $lch->c);
        $this->assertSame(240.0, $lch->h);
        $this->assertSame(0.8, $lch->alpha);
    }

    public function testConversionToLab(): void
    {
        $lch = new LchColor(50.0, 30.0, 120.0);
        $lab = $lch->to('lab');

        $this->assertInstanceOf(LabColor::class, $lab);
    }

    public function testConversionToLch(): void
    {
        $lch = new LchColor(50.0, 30.0, 120.0);
        $converted = $lch->to('lch');

        $this->assertSame($lch, $converted);
    }

    public function testCssOutputInLabSpace(): void
    {
        $lch = new LchColor(50.0, 30.0, 120.0);
        $css = $lch->toCss('lab');

        $this->assertStringStartsWith('lab(', $css);
    }

    #[\Override]
    public function testCssOutputInRgbSpace(): void
    {
        $lch = new LchColor(50.0, 30.0, 120.0);
        $css = $lch->toCss('rgb');

        $this->assertStringStartsWith('rgb(', $css);
    }

    #[\Override]
    public function testCssOutputInSrgbSpace(): void
    {
        $lch = new LchColor(50.0, 30.0, 120.0);
        $css = $lch->toCss('srgb');

        $this->assertStringStartsWith('rgb(', $css);
    }

    public function testFromLabConversion(): void
    {
        $lab = new LabColor(50.0, 20.0, -10.0);
        $lch = LchColor::fromLab($lab);

        $this->assertInstanceOf(LchColor::class, $lch);
        $this->assertSame(50.0, $lch->l);
        $this->assertGreaterThan(0.0, $lch->c);
        $this->assertGreaterThanOrEqual(0.0, $lch->h);
        $this->assertLessThan(360.0, $lch->h);
    }

    public function testFromSrgbConversion(): void
    {
        $srgb = new SrgbColor(1.0, 0.0, 0.0); // Red
        $lch = LchColor::fromSrgb($srgb);

        $this->assertInstanceOf(LchColor::class, $lch);
        $this->assertGreaterThan(0.0, $lch->l);
        $this->assertGreaterThan(0.0, $lch->c);
    }

    public function testHueIsNormalized(): void
    {
        $lch1 = new LchColor(50.0, 30.0, 450.0); // 450 -> 90
        $this->assertSame(90.0, $lch1->h);

        $lch2 = new LchColor(50.0, 30.0, -30.0); // -30 -> 330
        $this->assertSame(330.0, $lch2->h);
    }

    public function testLightnessIsClamped(): void
    {
        $lch1 = new LchColor(-10.0, 0.0, 0.0);
        $this->assertSame(0.0, $lch1->l);

        $lch2 = new LchColor(150.0, 0.0, 0.0);
        $this->assertSame(100.0, $lch2->l);
    }

    #[DataProvider('provideParseCases')]
    public function testParse(string $input, ?LchColor $expected, ?string $expectedException = null): void
    {
        if (null !== $expectedException) {
            $this->expectException(ParseException::class);
            $this->expectExceptionMessage($expectedException);
        }

        if ($expected instanceof LchColor) {
            $c = LchColor::parse($input);
            $this->assertInstanceOf(LchColor::class, $c);
            $this->assertEqualsWithDelta($expected->l, $c->l, 0.0001);
            $this->assertEqualsWithDelta($expected->c, $c->c, 0.0001);
            $this->assertEqualsWithDelta($expected->h, $c->h, 0.0001);
            $this->assertEqualsWithDelta($expected->alpha, $c->alpha, 0.0001);
        } else {
            LchColor::parse($input); // should throw
        }
    }

    public static function provideParseCases(): iterable
    {
        yield 'space-separated' => ['lch(50 30 120)', new LchColor(50.0, 30.0, 120.0, 1.0)];
        yield 'with alpha' => ['lch(50 30 120 / 0.9)', new LchColor(50.0, 30.0, 120.0, 0.9)];
        yield 'hue units' => ['lch(50 30 0.5turn)', new LchColor(50.0, 30.0, 180.0, 1.0)];
        yield 'percent L' => ['lch(50% 30 120)', new LchColor(50.0, 30.0, 120.0, 1.0)];
        yield 'too few params' => ['lch(50 30)', null, 'Cannot parse color "lch(50 30)".'];
        yield 'malformed' => ['lch(50 30 120', null, 'Cannot parse color "lch(50 30 120".'];
    }

    public function testRoundTripLabConversion(): void
    {
        $originalLab = new LabColor(60.0, 25.0, -15.0, 0.9);
        $lch = LchColor::fromLab($originalLab);
        $convertedLab = $lch->toLab();

        // Allow small tolerance for floating point precision
        $tolerance = 0.0001;
        $this->assertEqualsWithDelta($originalLab->l, $convertedLab->l, $tolerance);
        $this->assertEqualsWithDelta($originalLab->a, $convertedLab->a, $tolerance);
        $this->assertEqualsWithDelta($originalLab->b, $convertedLab->b, $tolerance);
        $this->assertEqualsWithDelta($originalLab->alpha, $convertedLab->alpha, $tolerance);
    }

    public function testRoundTripSrgbConversion(): void
    {
        $originalSrgb = new SrgbColor(0.5, 0.7, 0.2, 0.8);
        $lch = LchColor::fromSrgb($originalSrgb);
        $convertedSrgb = $lch->toSrgb();

        // Allow tolerance for floating point precision and gamut mapping
        $tolerance = 0.01;
        $this->assertEqualsWithDelta($originalSrgb->r, $convertedSrgb->r, $tolerance);
        $this->assertEqualsWithDelta($originalSrgb->g, $convertedSrgb->g, $tolerance);
        $this->assertEqualsWithDelta($originalSrgb->b, $convertedSrgb->b, $tolerance);
        $this->assertEqualsWithDelta($originalSrgb->a, $convertedSrgb->a, $tolerance);
    }

    public function testToLabConversion(): void
    {
        $lch = new LchColor(50.0, 30.0, 120.0);
        $lab = $lch->toLab();

        $this->assertInstanceOf(LabColor::class, $lab);
        $this->assertSame(50.0, $lab->l);
    }

    public function testDefaultAlpha(): void
    {
        $color = new LchColor(50, 50, 180);
        $this->assertEqualsWithDelta(1.0, $color->alpha, 0.01);
    }

    public function testGetAlpha(): void
    {
        $color = new LchColor(50, 50, 180, 0.85);
        $this->assertEqualsWithDelta(0.85, $color->getAlpha(), 0.01);
    }

    #[\Override]
    public function testGetChannels(): void
    {
        $color = new LchColor(50, 50, 180, 0.85);
        $channels = $color->getChannels();

        $this->assertArrayHasKey('l', $channels);
        $this->assertArrayHasKey('c', $channels);
        $this->assertArrayHasKey('h', $channels);
        $this->assertEqualsWithDelta(50, $channels['l'], 0.01);
        $this->assertEqualsWithDelta(50, $channels['c'], 0.01);
        $this->assertEqualsWithDelta(180, $channels['h'], 0.01);
    }

    #[\Override]
    public function testToHex(): void
    {
        $color = new LchColor(50, 50, 180, 1.0);
        $hex = $color->toHex();

        $this->assertStringStartsWith('#', $hex);
        $this->assertSame(7, \strlen($hex));
    }

    public function testToSrgbAndBack(): void
    {
        $original = new LchColor(50, 40, 120, 0.75);
        $srgb = $original->toSrgb();
        $back = LchColor::fromLab(LabColor::fromSrgb($srgb));

        $this->assertEqualsWithDelta($original->l, $back->l, 1.0);
        $this->assertEqualsWithDelta($original->c, $back->c, 1.0);
        $this->assertEqualsWithDelta($original->h, $back->h, 5.0);
        $this->assertEqualsWithDelta($original->alpha, $back->alpha, 0.01);
    }
}
