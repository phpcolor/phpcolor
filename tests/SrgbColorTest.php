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

use PhpColor\Color\Color;
use PhpColor\Color\ColorInterface;
use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\Exception\ParseException;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(SrgbColor::class)]
final class SrgbColorTest extends ColorTestCase
{
    protected function createColor(): ColorInterface
    {
        return new SrgbColor(0.5, 0.5, 0.5, 0.5);
    }

    protected function getExpectedColorClass(): string
    {
        return SrgbColor::class;
    }

    public static function provideColorSamples(): iterable
    {
        // color, hex, hexWithAlpha, css
        yield 'red' => [
            new SrgbColor(1.0, 0.0, 0.0),
            '#ff0000',
            '#ff0000ff',
            'rgb(255 0 0)',
        ];
        yield 'green' => [
            new SrgbColor(0.0, 1.0, 0.0),
            '#00ff00',
            '#00ff00ff',
            'rgb(0 255 0)',
        ];
        yield 'blue' => [
            new SrgbColor(0.0, 0.0, 1.0),
            '#0000ff',
            '#0000ffff',
            'rgb(0 0 255)',
        ];
        yield 'translucent gray' => [
            new SrgbColor(0.5, 0.5, 0.5, 0.5),
            '#808080',
            '#80808080',
            'rgb(128 128 128 / 0.5)',
        ];
    }

    public static function provideFromInputs(): iterable
    {
        yield [new SrgbColor(1.0, 0.0, 0.0)];
        yield ['#ff0000'];
        yield ['rgb(255 0 0)'];
    }

    public static function provideInvalidCssOutputSpaces(): array
    {
        return [
            ['lab'],
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

    public function testConstructorAllowsExtendedValues(): void
    {
        // Test no upper clamping
        $srgb1 = new SrgbColor(1.5, 2.0, 3.0, 1.5);
        $this->assertSame(1.5, $srgb1->r);
        $this->assertSame(2.0, $srgb1->g);
        $this->assertSame(3.0, $srgb1->b);
        $this->assertSame(1.0, $srgb1->a); // Alpha is still clamped

        // Test no lower clamping
        $srgb2 = new SrgbColor(-0.5, -1.0, -2.0, -0.5);
        $this->assertSame(-0.5, $srgb2->r);
        $this->assertSame(-1.0, $srgb2->g);
        $this->assertSame(-2.0, $srgb2->b);
        $this->assertSame(0.0, $srgb2->a); // Alpha is still clamped
    }

    #[\Override]
    public function testFromChannels(): void
    {
        $channels = ['r' => 0.4, 'g' => 0.5, 'b' => 0.6];
        $srgb = SrgbColor::fromChannels($channels, 0.9);
        $this->assertSame(0.4, $srgb->r);
        $this->assertSame(0.5, $srgb->g);
        $this->assertSame(0.6, $srgb->b);
        $this->assertSame(0.9, $srgb->a);
    }

    public function testFromChannelsWithMissingChannels(): void
    {
        $channels = ['r' => 0.5];
        $srgb = SrgbColor::fromChannels($channels);
        $this->assertSame(0.5, $srgb->r);
        $this->assertSame(0.0, $srgb->g);
        $this->assertSame(0.0, $srgb->b);
        $this->assertSame(1.0, $srgb->a);
    }

    public function testFromHslClampsAlpha(): void
    {
        $hsl = ['h' => 0.0, 's' => 0.0, 'l' => 0.5];
        $srgb1 = SrgbColor::fromHsl($hsl, 1.5);
        $this->assertSame(1.0, $srgb1->a);

        $srgb2 = SrgbColor::fromHsl($hsl, -0.5);
        $this->assertSame(0.0, $srgb2->a);
    }

    public function testFromHslClampsSaturationAndLightness(): void
    {
        $hsl = ['h' => 0.0, 's' => 1.5, 'l' => 1.5];
        $srgb = SrgbColor::fromHsl($hsl);
        // Should not throw and should clamp values
        $this->assertInstanceOf(SrgbColor::class, $srgb);
    }

    public function testFromHslNormalizesHue(): void
    {
        // Test that hue values outside [0, 360) are normalized
        $hsl1 = ['h' => 400.0, 's' => 0.5, 'l' => 0.5];
        $srgb1 = SrgbColor::fromHsl($hsl1);

        $hsl2 = ['h' => 40.0, 's' => 0.5, 'l' => 0.5];
        $srgb2 = SrgbColor::fromHsl($hsl2);

        // 400° = 40° after normalization
        $this->assertEqualsWithDelta($srgb2->r, $srgb1->r, 1e-10);
        $this->assertEqualsWithDelta($srgb2->g, $srgb1->g, 1e-10);
        $this->assertEqualsWithDelta($srgb2->b, $srgb1->b, 1e-10);
    }

    public function testFromHslWithAlpha(): void
    {
        $hsl = ['h' => 120.0, 's' => 1.0, 'l' => 0.5];
        $srgb = SrgbColor::fromHsl($hsl, 0.7);
        $this->assertSame(0.7, $srgb->a);
    }

    public function testFromHslZeroSaturation(): void
    {
        // When saturation is zero, should return grayscale
        $hsl = ['h' => 180.0, 's' => 0.0, 'l' => 0.6];
        $srgb = SrgbColor::fromHsl($hsl);
        $this->assertEqualsWithDelta(0.6, $srgb->r, 1e-10);
        $this->assertEqualsWithDelta(0.6, $srgb->g, 1e-10);
        $this->assertEqualsWithDelta(0.6, $srgb->b, 1e-10);
    }

    public function testFromSrgbReturnsIdentity(): void
    {
        $srgb = new SrgbColor(0.5, 0.5, 0.5);
        $result = SrgbColor::fromSrgb($srgb);
        $this->assertSame($srgb, $result);
    }

    public function testGetAlpha(): void
    {
        $srgb = new SrgbColor(0.0, 0.0, 0.0, 0.75);
        $this->assertSame(0.75, $srgb->getAlpha());
    }

    public function testGetBlue(): void
    {
        $srgb = new SrgbColor(0.71, 0.72, 0.73, 0.47);

        $this->assertSame(0.73, $srgb->getBlue());
        $this->assertSame(0.73, $srgb->b);
    }

    #[\Override]
    public function testGetChannels(): void
    {
        $srgb = new SrgbColor(0.1, 0.2, 0.3, 0.4);
        $channels = $srgb->getChannels();
        $this->assertIsArray($channels);
        $this->assertArrayHasKey('r', $channels);
        $this->assertArrayHasKey('g', $channels);
        $this->assertArrayHasKey('b', $channels);
        $this->assertSame(0.1, $channels['r']);
        $this->assertSame(0.2, $channels['g']);
        $this->assertSame(0.3, $channels['b']);
    }

    public function testGetGreen(): void
    {
        $srgb = new SrgbColor(0.71, 0.72, 0.73, 0.47);

        $this->assertSame(0.72, $srgb->getGreen());
        $this->assertSame(0.72, $srgb->g);
    }

    public function testGetRed(): void
    {
        $srgb = new SrgbColor(0.71, 0.72, 0.73, 0.47);

        $this->assertSame(0.71, $srgb->getRed());
        $this->assertSame(0.71, $srgb->r);
    }

    public function testGetSpaceName(): void
    {
        $this->assertSame('srgb', SrgbColor::getSpaceName());
    }

    public function testHslRoundTrip(): void
    {
        $input = ['h' => 200.0, 's' => 0.5, 'l' => 0.4];
        $srgb = SrgbColor::fromHsl($input);

        $this->assertInstanceOf(SrgbColor::class, $srgb);

        $hsl = $srgb->toHsl();
        $this->assertIsArray($hsl);
        $this->assertArrayHasKey('h', $hsl);
        $this->assertArrayHasKey('s', $hsl);
        $this->assertArrayHasKey('l', $hsl);

        $this->assertIsFloat($hsl['h']);
        $this->assertIsFloat($hsl['s']);
        $this->assertIsFloat($hsl['l']);

        $this->assertGreaterThanOrEqual(0.0, $hsl['s']);
        $this->assertLessThanOrEqual(1.0, $hsl['s']);
        $this->assertGreaterThanOrEqual(0.0, $hsl['l']);
        $this->assertLessThanOrEqual(1.0, $hsl['l']);
    }

    public function testHue2rgbWrapsAboveOne(): void
    {
        // h = 300°, s=1, l=0.5 => magenta; triggers t > 1 path for red component (h + 1/3)
        $srgb = SrgbColor::fromHsl(['h' => 300.0, 's' => 1.0, 'l' => 0.5]);
        $this->assertEqualsWithDelta(1.0, $srgb->r, 1e-12);
        $this->assertEqualsWithDelta(0.0, $srgb->g, 1e-12);
        $this->assertEqualsWithDelta(1.0, $srgb->b, 1e-12);
    }

    public function testHue2rgbWrapsNegative(): void
    {
        // h = 0°, s=1, l=0.5 => pure red; triggers t < 0 path for blue component (h - 1/3)
        $srgb = SrgbColor::fromHsl(['h' => 0.0, 's' => 1.0, 'l' => 0.5]);
        $this->assertEqualsWithDelta(1.0, $srgb->r, 1e-12);
        $this->assertEqualsWithDelta(0.0, $srgb->g, 1e-12);
        $this->assertEqualsWithDelta(0.0, $srgb->b, 1e-12);
    }

    public function testParseDelegatesToHexForHashInput(): void
    {
        $fromParse = SrgbColor::parse('#ff0000');
        $fromHex = SrgbColor::parseHex('#ff0000');

        $this->assertInstanceOf(SrgbColor::class, $fromParse);
        $this->assertEqualsWithDelta($fromHex->r, $fromParse->r, 1e-12);
        $this->assertEqualsWithDelta($fromHex->g, $fromParse->g, 1e-12);
        $this->assertEqualsWithDelta($fromHex->b, $fromParse->b, 1e-12);
        $this->assertEqualsWithDelta($fromHex->a, $fromParse->a, 1e-12);
    }

    #[DataProvider('provideParseHexCases')]
    public function testParseHex(string $input, ?SrgbColor $expected, ?string $expectedException = null): void
    {
        if (null !== $expectedException) {
            $this->expectException(ParseException::class);
            $this->expectExceptionMessage($expectedException);
        }

        if ($expected instanceof SrgbColor) {
            $c = SrgbColor::parseHex($input);
            $this->assertInstanceOf(SrgbColor::class, $c);
            $this->assertEqualsWithDelta($expected->r, $c->r, 0.0001);
            $this->assertEqualsWithDelta($expected->g, $c->g, 0.0001);
            $this->assertEqualsWithDelta($expected->b, $c->b, 0.0001);
            $this->assertEqualsWithDelta($expected->a, $c->a, 0.0001);
        } else {
            SrgbColor::parseHex($input); // should throw
        }
    }

    public static function provideParseHexCases(): iterable
    {
        yield 'short rgb' => ['#f0a', new SrgbColor(1.0, 0.0 + 0.0 / 255.0, 0.6666667, 1.0)];
        yield 'short rgba' => ['#f0af', new SrgbColor(1.0, 0.0 + 0.0 / 255.0, 0.6666667, 1.0)]; // alpha checked separately
        yield 'long rgb' => ['#ff00aa', new SrgbColor(1.0, 0.0, 170 / 255.0, 1.0)];
        yield 'long rgba' => ['#ff00aa80', new SrgbColor(1.0, 0.0, 170 / 255.0, 128 / 255.0)];
        yield 'invalid chars' => ['#zzzzzz', null, 'Cannot parse color "#zzzzzz".'];
        yield 'invalid length' => ['#abcde', null, 'Cannot parse color "#abcde".'];
    }

    #[DataProvider('provideParseHslCases')]
    public function testParseHsl(string $input, ?SrgbColor $expected, ?string $expectedException = null): void
    {
        if (null !== $expectedException) {
            $this->expectException(ParseException::class);
            $this->expectExceptionMessage($expectedException);
        }

        if ($expected instanceof SrgbColor) {
            $c = SrgbColor::parseHsl($input);
            $this->assertInstanceOf(SrgbColor::class, $c);
        } else {
            SrgbColor::parseHsl($input); // should throw
        }
    }

    public static function provideParseHslCases(): iterable
    {
        yield 'space-separated' => ['hsl(120 100% 25%)', new SrgbColor(0.0, 0.5, 0.0)];
        yield 'comma-separated' => ['hsl(120,100%,25%)', new SrgbColor(0.0, 0.5, 0.0)];
        yield 'with alpha slash' => ['hsl(0 100% 50% / 0.5)', new SrgbColor(1.0, 0.0, 0.0, 0.5)];
        yield 'with alpha comma' => ['hsla(0,100%,50%,0.5)', new SrgbColor(1.0, 0.0, 0.0, 0.5)];
        yield 'malformed' => ['hsl(1 2 3', null, 'Cannot parse color "hsl(1 2 3".'];
        yield 'invalid params' => ['hsl(invalid)', null, 'Cannot parse color "hsl(invalid)".'];
    }

    public function testParseThrowsOnInvalidRgbSyntax(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Cannot parse color');
        SrgbColor::parse('rgb(invalid)');
    }

    public function testToCssColorFunction(): void
    {
        $srgb = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $css = $srgb->toCss('color');
        $this->assertStringStartsWith('color(srgb', $css);
    }

    public function testToCssColorFunctionWithAlpha(): void
    {
        $srgb = new SrgbColor(0.5, 0.5, 0.5, 0.8);
        $css = $srgb->toCss('color-srgb');
        $this->assertStringStartsWith('color(srgb', $css);
        $this->assertStringContainsString('/', $css);
    }

    public function testToCssHsl(): void
    {
        $srgb = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $css = $srgb->toCss('hsl');
        $this->assertStringStartsWith('hsl(', $css);
    }

    public function testToCssHslWithAlpha(): void
    {
        $srgb = new SrgbColor(1.0, 0.0, 0.0, 0.5);
        $css = $srgb->toCss('hsl');
        $this->assertStringStartsWith('hsl(', $css);
        $this->assertStringContainsString('/', $css);
        $this->assertStringContainsString('0.5', $css);
    }

    public function testToCssUnsupportedSpace(): void
    {
        $this->expectException(InvalidColorException::class);
        $srgb = new SrgbColor(1.0, 0.0, 0.0);
        $srgb->toCss('unsupported-space');
    }

    public function testToHslGrayscale(): void
    {
        // When R=G=B, should return zero saturation
        $gray = new SrgbColor(0.5, 0.5, 0.5);
        $hsl = $gray->toHsl();
        $this->assertSame(0.0, $hsl['h']);
        $this->assertSame(0.0, $hsl['s']);
        $this->assertSame(0.5, $hsl['l']);
    }

    public function testToHslHighLightness(): void
    {
        // Test saturation calculation when lightness > 0.5
        $color = new SrgbColor(0.9, 0.7, 0.8);
        $hsl = $color->toHsl();
        $this->assertGreaterThan(0.0, $hsl['s']);
        $this->assertGreaterThan(0.5, $hsl['l']);
    }

    public function testToHslNegativeHue(): void
    {
        // Test case where hue calculation might be negative
        $color = new SrgbColor(0.9, 0.2, 0.8);
        $hsl = $color->toHsl();
        $this->assertGreaterThanOrEqual(0.0, $hsl['h']);
        $this->assertLessThan(360.0, $hsl['h']);
    }

    public function testToHslWithBlueMaximum(): void
    {
        // Test when blue is the maximum component
        $color = new SrgbColor(0.2, 0.3, 0.9);
        $hsl = $color->toHsl();
        $this->assertIsFloat($hsl['h']);
        $this->assertIsFloat($hsl['s']);
        $this->assertIsFloat($hsl['l']);
        $this->assertGreaterThan(180.0, $hsl['h']); // Should be in blue range
        $this->assertLessThan(270.0, $hsl['h']);
    }

    public function testToHslWithGreenMaximum(): void
    {
        // Test when green is the maximum component
        $color = new SrgbColor(0.2, 0.8, 0.3);
        $hsl = $color->toHsl();
        $this->assertIsFloat($hsl['h']);
        $this->assertIsFloat($hsl['s']);
        $this->assertIsFloat($hsl['l']);
        $this->assertGreaterThan(90.0, $hsl['h']); // Should be in green range
        $this->assertLessThan(180.0, $hsl['h']);
    }

    public function testToRecognizesRgbAliasReturnsSameInstance(): void
    {
        $c = new SrgbColor(0.1, 0.2, 0.3, 0.4);
        $converted = $c->to('rgb');

        // Alias "rgb" is normalized to "srgb" by ColorSpaces and returns self
        $this->assertSame($c, $converted);
    }

    public function testToSrgbReturnsIdentity(): void
    {
        $srgb = new SrgbColor(0.1, 0.2, 0.3, 0.4);
        $result = $srgb->toSrgb();
        $this->assertSame($srgb, $result);
    }

    public function testSrgbCanHoldExtendedValues(): void
    {
        $color = new SrgbColor(1.5, -0.2, 0.5);

        $this->assertSame(1.5, $color->r);
        $this->assertSame(-0.2, $color->g);
        $this->assertSame(0.5, $color->b);
    }

    public function testHexOutputIsClamped(): void
    {
        $superRed = new SrgbColor(1.5, 0.0, 0.0);
        $superBlack = new SrgbColor(-0.5, -0.5, -0.5);

        $this->assertSame('#ff0000', $superRed->toHex());
        $this->assertSame('#000000', $superBlack->toHex());
    }

    public function testCssOutputPreservesExtendedValues(): void
    {
        $color = new SrgbColor(1.5, -0.5, 0.5);
        $css = $color->toCss();

        $this->assertSame('rgb(383 -128 128)', $css);
    }

    public function testDefaultAlpha(): void
    {
        $color = new SrgbColor(1.0, 0.0, 0.0);
        $this->assertEqualsWithDelta(1.0, $color->a, 0.01);
    }
}
