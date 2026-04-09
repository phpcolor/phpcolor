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
use PhpColor\Color\OklabColor;
use PhpColor\Color\OklchColor;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(OklchColor::class)]
final class OklchColorTest extends ColorTestCase
{
    protected function createColor(): ColorInterface
    {
        return new OklchColor(0.5, 0.2, 120.0, 0.8);
    }

    protected function getExpectedColorClass(): string
    {
        return OklchColor::class;
    }

    public static function provideColorSamples(): iterable
    {
        // color, hex, hexWithAlpha, css
        yield 'red' => [
            new OklchColor(0.628, 0.258, 29.25),
            '#ff0000',
            '#ff0000ff',
            'oklch(0.628 0.258 29.25)',
        ];
        yield 'translucent blue' => [
            new OklchColor(0.452, 0.314, 264.05, 0.5),
            '#0000ff',
            '#0000ff80',
            'oklch(0.452 0.314 264.05 / 0.5)',
        ];
    }

    public static function provideFromInputs(): iterable
    {
        yield [new SrgbColor(1.0, 0.0, 0.0)];
        yield ['#ff0000'];
        yield ['oklch(0.7 0.15 30)'];
    }

    public static function provideInvalidCssOutputSpaces(): array
    {
        return [
            'lab' => ['lab'],
            'lch' => ['lch'],
            'display-p3' => ['display-p3'],
            'xyz' => ['xyz'],
            'a98' => ['a98-rgb'],
            'rec2020' => ['rec2020'],
            'prophoto-rgb' => ['prophoto-rgb'],
            'hsl' => ['hsl'],
            'color' => ['color'],
        ];
    }

    public function testChannelGetters(): void
    {
        $c = new OklchColor(0.6, 0.2, 30.0, 0.9);
        $this->assertSame(0.6, $c->getLightness());
        $this->assertSame(0.2, $c->getChroma());
        $this->assertSame(30.0, $c->getHue());
    }

    public function testGetHueReturnsZeroForAchromaticColor(): void
    {
        $achromatic = new OklchColor(0.5, 0.0, 180.0);
        $this->assertSame(0.0, $achromatic->getHue());
    }

    public function testChromaIsClamped(): void
    {
        $oklch = new OklchColor(0.5, -0.1, 0.0);
        $this->assertSame(0.0, $oklch->c);
    }

    public function testConstruction(): void
    {
        $oklch = new OklchColor(0.5, 0.2, 120.0, 0.8);

        $this->assertSame(0.5, $oklch->l);
        $this->assertSame(0.2, $oklch->c);
        $this->assertSame(120.0, $oklch->h);
        $this->assertSame(0.8, $oklch->alpha);
    }

    public function testConversionToOklab(): void
    {
        $oklch = new OklchColor(0.7, 0.15, 30.0);
        $oklab = $oklch->to('oklab');

        $this->assertInstanceOf(OklabColor::class, $oklab);
    }

    public function testConversionToOklch(): void
    {
        $oklch = new OklchColor(0.7, 0.15, 30.0);
        $converted = $oklch->to('oklch');

        $this->assertSame($oklch, $converted);
    }

    #[DataProvider('provideHuesForCool')]
    public function testCoolCoversHueRanges(float $startHue, float $amount): void
    {
        $c = new OklchColor(0.6, 0.2, $startHue);
        $cooled = $c->cool($amount);
        $this->assertInstanceOf(OklchColor::class, $cooled->to('oklch'));

        // New hue should move toward target 210° from start hue
        $target = 210.0;
        $newHue = OklchColor::fromSrgb($cooled->toSrgb())->h;

        // Normalize differences to shortest arc
        $diffBefore = abs(fmod($startHue - $target + 540.0, 360.0) - 180.0);
        $diffAfter = abs(fmod($newHue - $target + 540.0, 360.0) - 180.0);

        $this->assertLessThanOrEqual($diffBefore, $diffAfter + 1e-9);
    }

    public static function provideHuesForCool(): iterable
    {
        // Covers: [180,270), [90,180), h>=270, h<90
        yield 'in 180-270 range' => [200.0, 0.5];
        yield 'in 90-180 range' => [120.0, 0.5];
        yield '>= 270 range' => [300.0, 0.5];
        yield '< 90 range' => [60.0, 0.5];
    }

    public function testCssOutputInOklabSpace(): void
    {
        $color = $this->createColor();
        $css = $color->toCss('oklab');
        $this->assertStringStartsWith('oklab(', $css);
    }

    public function testCssOutputInOklchSpace(): void
    {
        $color = $this->createColor();
        $css = $color->toCss('oklch');
        $this->assertStringStartsWith('oklch(', $css);
    }

    public function testFromOklabConversion(): void
    {
        $oklab = new OklabColor(0.7, 0.1, 0.05);
        $oklch = OklchColor::fromOklab($oklab);

        $this->assertInstanceOf(OklchColor::class, $oklch);
        $this->assertSame(0.7, $oklch->l);
        $this->assertGreaterThan(0.0, $oklch->c);
        $this->assertGreaterThanOrEqual(0.0, $oklch->h);
        $this->assertLessThan(360.0, $oklch->h);
    }

    public function testFromSrgbConversion(): void
    {
        $srgb = new SrgbColor(1.0, 0.0, 0.0); // Red
        $oklch = OklchColor::fromSrgb($srgb);

        $this->assertInstanceOf(OklchColor::class, $oklch);
        $this->assertGreaterThan(0.5, $oklch->l); // Should have reasonable lightness
        $this->assertGreaterThan(0.0, $oklch->c); // Should have positive chroma
    }

    public function testFromSrgbWithNegativeHueNormalization(): void
    {
        // Blue produces negative atan2 result, requiring +360 normalization
        $blue = new SrgbColor(0.0, 0.0, 1.0);
        $oklch = OklchColor::fromSrgb($blue);

        $this->assertGreaterThanOrEqual(0.0, $oklch->h);
        $this->assertLessThan(360.0, $oklch->h);
        // Blue hue is around 264 degrees in OKLCH
        $this->assertGreaterThan(180.0, $oklch->h);
    }

    public function testHueIsNormalized(): void
    {
        $oklch1 = new OklchColor(0.5, 0.1, 450.0); // 450 -> 90
        $this->assertSame(90.0, $oklch1->h);

        $oklch2 = new OklchColor(0.5, 0.1, -30.0); // -30 -> 330
        $this->assertSame(330.0, $oklch2->h);
    }

    public function testLightnessIsClamped(): void
    {
        $oklch1 = new OklchColor(-0.1, 0.0, 0.0);
        $this->assertSame(0.0, $oklch1->l);

        $oklch2 = new OklchColor(1.5, 0.0, 0.0);
        $this->assertSame(1.0, $oklch2->l);
    }

    #[DataProvider('provideParseCases')]
    public function testParse(string $input, ?OklchColor $expected, ?string $expectedException = null): void
    {
        if (null !== $expectedException) {
            $this->expectException(ParseException::class);
            $this->expectExceptionMessage($expectedException);
        }

        if ($expected instanceof OklchColor) {
            $c = OklchColor::parse($input);
            $this->assertInstanceOf(OklchColor::class, $c);
            $this->assertEqualsWithDelta($expected->l, $c->l, 0.0001);
            $this->assertEqualsWithDelta($expected->c, $c->c, 0.0001);
            $this->assertEqualsWithDelta($expected->h, $c->h, 0.0001);
            $this->assertEqualsWithDelta($expected->alpha, $c->alpha, 0.0001);
        } else {
            OklchColor::parse($input); // should throw
        }
    }

    public static function provideParseCases(): iterable
    {
        yield 'space-separated' => ['oklch(0.7 0.15 30)', new OklchColor(0.7, 0.15, 30.0, 1.0)];
        yield 'with alpha' => ['oklch(0.7 0.15 30 / 0.9)', new OklchColor(0.7, 0.15, 30.0, 0.9)];
        yield 'hue units' => ['oklch(0.7 0.15 0.5turn)', new OklchColor(0.7, 0.15, 180.0, 1.0)];
        yield 'percent L' => ['oklch(50% 0.15 30)', new OklchColor(0.5, 0.15, 30.0, 1.0)];
        yield 'too few params' => ['oklch(0.7 0.15)', null, 'Cannot parse color "oklch(0.7 0.15)".'];
        yield 'malformed' => ['oklch(0.7 0.15 30', null, 'Cannot parse color "oklch(0.7 0.15 30".'];
    }

    public function testRoundTripOklabConversion(): void
    {
        $originalOklab = new OklabColor(0.6, 0.1, -0.05, 0.9);
        $oklch = OklchColor::fromOklab($originalOklab);
        $convertedOklab = $oklch->toOklab();

        // Allow small tolerance for floating point precision
        $tolerance = 0.0001;
        $this->assertEqualsWithDelta($originalOklab->l, $convertedOklab->l, $tolerance);
        $this->assertEqualsWithDelta($originalOklab->a, $convertedOklab->a, $tolerance);
        $this->assertEqualsWithDelta($originalOklab->b, $convertedOklab->b, $tolerance);
        $this->assertEqualsWithDelta($originalOklab->alpha, $convertedOklab->alpha, $tolerance);
    }

    public function testRoundTripSrgbConversion(): void
    {
        $originalSrgb = new SrgbColor(0.5, 0.7, 0.2, 0.8);
        $oklch = OklchColor::fromSrgb($originalSrgb);
        $convertedSrgb = $oklch->toSrgb();

        // Allow small tolerance for floating point precision
        $tolerance = 0.01;
        $this->assertEqualsWithDelta($originalSrgb->r, $convertedSrgb->r, $tolerance);
        $this->assertEqualsWithDelta($originalSrgb->g, $convertedSrgb->g, $tolerance);
        $this->assertEqualsWithDelta($originalSrgb->b, $convertedSrgb->b, $tolerance);
        $this->assertEqualsWithDelta($originalSrgb->a, $convertedSrgb->a, $tolerance);
    }

    public function testToOklabConversion(): void
    {
        $oklch = new OklchColor(0.7, 0.15, 30.0);
        $oklab = $oklch->toOklab();

        $this->assertInstanceOf(OklabColor::class, $oklab);
        $this->assertSame(0.7, $oklab->l);
    }

    public function testDefaultAlpha(): void
    {
        $color = new OklchColor(0.7, 0.15, 30);
        $this->assertEqualsWithDelta(1.0, $color->alpha, 0.01);
    }

    public function testGetAlpha(): void
    {
        $color = new OklchColor(0.7, 0.15, 30, 0.9);
        $this->assertEqualsWithDelta(0.9, $color->getAlpha(), 0.01);
    }

    #[\Override]
    public function testGetChannels(): void
    {
        $color = new OklchColor(0.7, 0.15, 30, 0.9);
        $channels = $color->getChannels();

        $this->assertArrayHasKey('l', $channels);
        $this->assertArrayHasKey('c', $channels);
        $this->assertArrayHasKey('h', $channels);
        $this->assertEqualsWithDelta(0.7, $channels['l'], 0.01);
        $this->assertEqualsWithDelta(0.15, $channels['c'], 0.01);
        $this->assertEqualsWithDelta(30, $channels['h'], 0.01);
    }

    #[\Override]
    public function testToHex(): void
    {
        $color = new OklchColor(0.7, 0.15, 30, 1.0);
        $hex = $color->toHex();

        $this->assertStringStartsWith('#', $hex);
        $this->assertSame(7, \strlen($hex));
    }

    public function testToSrgbAndBack(): void
    {
        $original = new OklchColor(0.7, 0.15, 30, 0.8);
        $srgb = $original->toSrgb();
        $back = OklchColor::fromSrgb($srgb);

        $this->assertEqualsWithDelta($original->l, $back->l, 0.01);
        $this->assertEqualsWithDelta($original->c, $back->c, 0.01);
        $this->assertEqualsWithDelta($original->h, $back->h, 1.0);
        $this->assertEqualsWithDelta($original->alpha, $back->alpha, 0.01);
    }
}
