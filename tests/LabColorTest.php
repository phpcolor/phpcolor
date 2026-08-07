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
use PhpColor\Color\SrgbColor;
use PhpColor\Color\XyzColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(LabColor::class)]
final class LabColorTest extends ColorTestCase
{
    protected function createColor(): ColorInterface
    {
        return new LabColor(50.0, 20.0, -10.0);
    }

    protected function getExpectedColorClass(): string
    {
        return LabColor::class;
    }

    public static function provideColorSamples(): iterable
    {
        // color, hex, hexWithAlpha, css
        yield 'red' => [
            new LabColor(54.29, 80.8, 69.89), // sRGB Red
            '#ff0000',
            '#ff0000ff',
            'lab(54.29 80.8 69.89)',
        ];
        yield 'translucent blue' => [
            new LabColor(29.57, 68.29, -112.03, 0.5), // sRGB Blue
            '#0000ff',
            '#0000ff80',
            'lab(29.57 68.29 -112.03 / 0.5)',
        ];
    }

    public static function provideFromInputs(): iterable
    {
        yield [new SrgbColor(1.0, 0.0, 0.0)];
        yield ['#ff0000'];
        yield ['lab(50 20 -30)'];
    }

    public static function provideInvalidCssOutputSpaces(): array
    {
        return [
            ['lch'],
            ['oklab'],
            ['oklch'],
            ['display-p3'],
            ['xyz'],
            ['a98-rgb'],
            ['rec2020'],
            ['prophoto-rgb'],
        ];
    }

    public function testAllowsNegativeAB(): void
    {
        $lab = new LabColor(50.0, -30.0, 40.0);

        $this->assertSame(-30.0, $lab->a);
        $this->assertSame(40.0, $lab->b);
    }

    public function testChannelGetters(): void
    {
        $c = new LabColor(50.0, 10.0, -20.0, 0.7);
        $this->assertSame(50.0, $c->getLightness());
        $this->assertSame(10.0, $c->getA());
        $this->assertSame(-20.0, $c->getB());
    }

    public function testConstruction(): void
    {
        $lab = new LabColor(75.0, 25.0, -15.0, 0.8);

        $this->assertSame(75.0, $lab->l);
        $this->assertSame(25.0, $lab->a);
        $this->assertSame(-15.0, $lab->b);
        $this->assertSame(0.8, $lab->alpha);
    }

    public function testConversionToLab(): void
    {
        $lab = new LabColor(50.0, 20.0, -10.0);
        $converted = $lab->to('lab');

        $this->assertSame($lab, $converted);
    }

    public function testConversionToXyz(): void
    {
        $lab = new LabColor(50.0, 20.0, -10.0);
        $xyz = $lab->to('xyz');

        $this->assertInstanceOf(XyzColor::class, $xyz);
    }

    public function testCssOutputInLabSpace(): void
    {
        $color = $this->createColor();
        $css = $color->toCss('lab');
        $this->assertStringStartsWith('lab(', $css);
    }

    public function testFromSrgbConversion(): void
    {
        $srgb = new SrgbColor(1.0, 0.0, 0.0); // Red
        $lab = LabColor::fromSrgb($srgb);

        $this->assertInstanceOf(LabColor::class, $lab);
        $this->assertGreaterThan(0.0, $lab->l);
        $this->assertGreaterThan(0.0, $lab->a); // Should have positive a (red direction)
    }

    public function testFromXyzConversion(): void
    {
        $xyz = new XyzColor(0.4, 0.2, 0.1);
        $lab = LabColor::fromXyz($xyz);

        $this->assertInstanceOf(LabColor::class, $lab);
        $this->assertGreaterThan(0.0, $lab->l);
        $this->assertLessThanOrEqual(100.0, $lab->l);
    }

    public function testLightnessIsClamped(): void
    {
        $lab1 = new LabColor(-10.0, 0.0, 0.0);
        $this->assertSame(0.0, $lab1->l);

        $lab2 = new LabColor(150.0, 0.0, 0.0);
        $this->assertSame(100.0, $lab2->l);
    }

    #[DataProvider('provideCssColor4ReferenceValues')]
    public function testMatchesCssColor4ReferenceValues(string $hex, float $l, float $a, float $b): void
    {
        $lab = LabColor::fromSrgb(SrgbColor::parseHex($hex));

        $this->assertEqualsWithDelta($l, $lab->l, 0.05);
        $this->assertEqualsWithDelta($a, $lab->a, 0.05);
        $this->assertEqualsWithDelta($b, $lab->b, 0.05);
    }

    /**
     * @return iterable<string, array{0: string, 1: float, 2: float, 3: float}>
     */
    public static function provideCssColor4ReferenceValues(): iterable
    {
        // lab() coordinates per CSS Color 4, which defines Lab against the D50 reference white.
        yield 'red' => ['#ff0000', 54.290541, 80.804928, 69.890965];
        yield 'green' => ['#00ff00', 87.818534, -79.271061, 80.994581];
        yield 'blue' => ['#0000ff', 29.568302, 68.287365, -112.029710];
        yield 'white' => ['#ffffff', 100.0, 0.0, 0.0];
        yield 'black' => ['#000000', 0.0, 0.0, 0.0];
        yield 'mid gray' => ['#808080', 53.585013, 0.0, 0.0];
        yield 'steel blue' => ['#336699', 41.520824, -4.573090, -33.494195];
        yield 'olive' => ['#7f7f00', 51.765383, -9.394607, 55.708293];
    }

    #[DataProvider('provideNeutrals')]
    public function testNeutralsAreAchromatic(string $hex, float $l): void
    {
        $lab = LabColor::fromSrgb(SrgbColor::parseHex($hex));

        $this->assertEqualsWithDelta($l, $lab->l, 0.001);
        $this->assertEqualsWithDelta(0.0, $lab->a, 0.001);
        $this->assertEqualsWithDelta(0.0, $lab->b, 0.001);
    }

    /**
     * @return iterable<string, array{0: string, 1: float}>
     */
    public static function provideNeutrals(): iterable
    {
        yield 'white' => ['#ffffff', 100.0];
        yield 'black' => ['#000000', 0.0];
        yield 'mid gray' => ['#808080', 53.585013];
        yield 'light gray' => ['#cccccc', 82.045782];
        yield 'dark gray' => ['#333333', 21.246731];
    }

    #[DataProvider('provideRoundTripHexColors')]
    public function testRoundTripFromSrgb(string $hex): void
    {
        $srgb = SrgbColor::parseHex($hex);
        $back = LabColor::fromSrgb($srgb)->toSrgb();

        $this->assertEqualsWithDelta($srgb->r, $back->r, 0.001);
        $this->assertEqualsWithDelta($srgb->g, $back->g, 0.001);
        $this->assertEqualsWithDelta($srgb->b, $back->b, 0.001);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function provideRoundTripHexColors(): iterable
    {
        yield 'red' => ['#ff0000'];
        yield 'green' => ['#00ff00'];
        yield 'blue' => ['#0000ff'];
        yield 'cyan' => ['#00ffff'];
        yield 'magenta' => ['#ff00ff'];
        yield 'yellow' => ['#ffff00'];
        yield 'white' => ['#ffffff'];
        yield 'black' => ['#000000'];
        yield 'mid gray' => ['#808080'];
        yield 'steel blue' => ['#336699'];
        yield 'olive' => ['#7f7f00'];
        yield 'salmon' => ['#fa8072'];
    }

    #[DataProvider('provideParseCases')]
    public function testParse(string $input, ?LabColor $expected, ?string $expectedException = null): void
    {
        if (null !== $expectedException) {
            $this->expectException(ParseException::class);
            $this->expectExceptionMessage($expectedException);
        }

        if ($expected instanceof LabColor) {
            $c = LabColor::parse($input);
            $this->assertInstanceOf(LabColor::class, $c);
            $this->assertEqualsWithDelta($expected->l, $c->l, 0.0001);
            $this->assertEqualsWithDelta($expected->a, $c->a, 0.0001);
            $this->assertEqualsWithDelta($expected->b, $c->b, 0.0001);
            $this->assertEqualsWithDelta($expected->alpha, $c->alpha, 0.0001);
        } else {
            LabColor::parse($input); // should throw
        }
    }

    public static function provideParseCases(): iterable
    {
        yield 'space-separated' => ['lab(50 20 -10)', new LabColor(50.0, 20.0, -10.0, 1.0)];
        yield 'with alpha' => ['lab(50 20 -10 / 0.8)', new LabColor(50.0, 20.0, -10.0, 0.8)];
        yield 'comma syntax' => ['lab(50,20,-10,0.5)', new LabColor(50.0, 20.0, -10.0, 0.5)];
        yield 'percent L' => ['lab(50% 20 -10)', new LabColor(50.0, 20.0, -10.0, 1.0)];
        yield 'too few params' => ['lab(50 20)', null, 'Cannot parse color "lab(50 20)".'];
        yield 'malformed' => ['lab(50 20 -10', null, 'Cannot parse color "lab(50 20 -10".'];
    }

    public function testRoundTripSrgbConversion(): void
    {
        $originalSrgb = new SrgbColor(0.5, 0.7, 0.2, 0.8);
        $lab = LabColor::fromSrgb($originalSrgb);
        $convertedSrgb = $lab->toSrgb();

        // Allow tolerance for floating point precision and gamut mapping
        $tolerance = 0.01;
        $this->assertEqualsWithDelta($originalSrgb->r, $convertedSrgb->r, $tolerance);
        $this->assertEqualsWithDelta($originalSrgb->g, $convertedSrgb->g, $tolerance);
        $this->assertEqualsWithDelta($originalSrgb->b, $convertedSrgb->b, $tolerance);
        $this->assertEqualsWithDelta($originalSrgb->a, $convertedSrgb->a, $tolerance);
    }

    public function testRoundTripXyzConversion(): void
    {
        $originalXyz = new XyzColor(0.3, 0.4, 0.2, 0.9);
        $lab = LabColor::fromXyz($originalXyz);
        $convertedXyz = $lab->toXyz();

        // Allow tolerance for floating point precision
        $tolerance = 0.001;
        $this->assertEqualsWithDelta($originalXyz->x, $convertedXyz->x, $tolerance);
        $this->assertEqualsWithDelta($originalXyz->y, $convertedXyz->y, $tolerance);
        $this->assertEqualsWithDelta($originalXyz->z, $convertedXyz->z, $tolerance);
        $this->assertEqualsWithDelta($originalXyz->alpha, $convertedXyz->alpha, $tolerance);
    }

    public function testToXyzConversion(): void
    {
        $lab = new LabColor(50.0, 20.0, -10.0);
        $xyz = $lab->toXyz();

        $this->assertInstanceOf(XyzColor::class, $xyz);
    }

    public function testDefaultAlpha(): void
    {
        $color = new LabColor(50, 25, -25);
        $this->assertEqualsWithDelta(1.0, $color->alpha, 0.01);
    }

    public function testGetAlpha(): void
    {
        $color = new LabColor(50, 25, -25, 0.7);
        $this->assertEqualsWithDelta(0.7, $color->getAlpha(), 0.01);
    }

    #[\Override]
    public function testGetChannels(): void
    {
        $color = new LabColor(50, 25, -25, 0.7);
        $channels = $color->getChannels();

        $this->assertArrayHasKey('l', $channels);
        $this->assertArrayHasKey('a', $channels);
        $this->assertArrayHasKey('b', $channels);
        $this->assertEqualsWithDelta(50, $channels['l'], 0.01);
        $this->assertEqualsWithDelta(25, $channels['a'], 0.01);
        $this->assertEqualsWithDelta(-25, $channels['b'], 0.01);
    }

    #[\Override]
    public function testToHex(): void
    {
        $color = new LabColor(50, 25, -25, 1.0);
        $hex = $color->toHex();

        $this->assertStringStartsWith('#', $hex);
        $this->assertSame(7, \strlen($hex));
    }

    public function testToSrgbAndBack(): void
    {
        $original = new LabColor(50, 20, -15, 0.85);
        $srgb = $original->toSrgb();
        $back = LabColor::fromSrgb($srgb);

        $this->assertEqualsWithDelta($original->l, $back->l, 1.0);
        $this->assertEqualsWithDelta($original->a, $back->a, 1.0);
        $this->assertEqualsWithDelta($original->b, $back->b, 1.0);
        $this->assertEqualsWithDelta($original->alpha, $back->alpha, 0.01);
    }
}
