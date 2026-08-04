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

use PhpColor\Color\A98RgbColor;
use PhpColor\Color\Color;
use PhpColor\Color\ColorInterface;
use PhpColor\Color\DisplayP3Color;
use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\Exception\ParseException;
use PhpColor\Color\HwbColor;
use PhpColor\Color\LabColor;
use PhpColor\Color\LchColor;
use PhpColor\Color\OklabColor;
use PhpColor\Color\OklchColor;
use PhpColor\Color\ProPhotoColor;
use PhpColor\Color\Rec2020Color;
use PhpColor\Color\SrgbColor;
use PhpColor\Color\XyzColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Color::class)]
final class ColorTest extends TestCase
{
    public function testBlack(): void
    {
        $c = Color::black();
        $this->assertInstanceOf(SrgbColor::class, $c);
        $this->assertSame('#000000', $c->toHex());
    }

    public function testBlue(): void
    {
        $c = Color::blue();
        $this->assertInstanceOf(SrgbColor::class, $c);
        $this->assertSame('#0000ff', $c->toHex());
    }

    public function testContrast(): void
    {
        $ratio = Color::contrast('#000', '#fff');
        $this->assertGreaterThanOrEqual(21.0, $ratio);
    }

    public function testDistanceUnknownAlgorithmThrows(): void
    {
        $this->expectException(InvalidColorException::class);
        Color::distance('#000', '#fff', 'unknown');
    }

    public function testDeltaE(): void
    {
        $d = Color::deltaE('#ff0000', '#ff1010');
        $this->assertGreaterThan(0.0, $d);
    }

    public function testGreen(): void
    {
        $c = Color::green();
        $this->assertInstanceOf(SrgbColor::class, $c);
        $this->assertSame('#00ff00', $c->toHex());
    }

    public function testHex(): void
    {
        $c = Color::hex('#f00');

        $this->assertInstanceOf(SrgbColor::class, $c);
        $this->assertSame('#ff0000', strtolower($c->toHex()));
    }

    public function testMixOklab(): void
    {
        $a = Color::parse('rgb(255 0 0)');
        $b = Color::parse('rgb(0 0 255)');

        $m = Color::mix($a, $b, 0.5, 'oklab');
        $this->assertInstanceOf(OklabColor::class, $m);

        // Should produce a perceptually balanced mix
        $srgb = $m->toSrgb();
        $this->assertInstanceOf(SrgbColor::class, $srgb);
    }

    public function testMixOklabDefaultSpace(): void
    {
        $a = Color::parse('rgb(255 0 0)');
        $b = Color::parse('rgb(0 0 255)');

        // Default mixing space should be oklab
        $m = Color::mix($a, $b, 0.5);
        $this->assertInstanceOf(OklabColor::class, $m);
    }

    public function testMixSrgb(): void
    {
        $a = Color::parse('rgb(255 0 0)');
        $b = Color::parse('rgb(0 0 255)');

        $m = Color::mix($a, $b, 0.5, 'srgb');
        $this->assertInstanceOf(SrgbColor::class, $m);

        // Roughly purple
        $hex = strtolower($m->toHex());
        $this->assertStringStartsWith('#', $hex);
    }

    public function testMixUnsupportedSpace(): void
    {
        $red = Color::parse('#ff0000');
        $green = Color::parse('#00ff00');

        $this->expectException(InvalidColorException::class);
        $this->expectExceptionMessage('Mixing space "unknown" is not supported yet.');
        Color::mix($red, $green, 0.5, 'unknown');
    }

    public function testOklab(): void
    {
        $c = Color::oklab(0.1, 0.2, 0.3, 0.4);

        $this->assertSame(0.1, $c->l);
        $this->assertSame(0.2, $c->a);
        $this->assertSame(0.3, $c->b);
        $this->assertSame(0.4, $c->getAlpha());
    }

    public function testOklch(): void
    {
        $c = Color::oklch(0.1, 0.2, 0.3, 0.4);

        $this->assertSame(0.1, $c->l);
        $this->assertSame(0.2, $c->c);
        $this->assertSame(0.3, $c->h);
        $this->assertSame(0.4, $c->getAlpha());
    }

    public function testParseColorFunctionA98Rgb(): void
    {
        $color = Color::parse('color(a98-rgb 0.2 0.7 0.4 / 0.8)');
        $this->assertInstanceOf(A98RgbColor::class, $color);

        $this->assertSame(0.8, $color->a);
    }

    public function testParseColorFunctionDisplayP3(): void
    {
        $color = Color::parse('color(display-p3 0.8 0.3 0.1)');
        $this->assertInstanceOf(DisplayP3Color::class, $color);

        $p3Color = $color;
        $this->assertSame(0.8, $p3Color->r);
        $this->assertSame(0.3, $p3Color->g);
        $this->assertSame(0.1, $p3Color->b);
        $this->assertSame(1.0, $p3Color->a);
    }

    public function testParseColorFunctionDisplayP3WithAlpha(): void
    {
        $color = Color::parse('color(display-p3 0.8 0.3 0.1 / 0.7)');
        $this->assertInstanceOf(DisplayP3Color::class, $color);

        $p3Color = $color;
        $this->assertSame(0.7, $p3Color->a);
    }

    public function testParseColorFunctionProPhoto(): void
    {
        $color = Color::parse('color(prophoto-rgb 0.4 0.5 0.6)');
        $this->assertInstanceOf(ProPhotoColor::class, $color);

        $pro = $color;
        $this->assertSame(0.4, $pro->r);
        $this->assertSame(0.5, $pro->g);
        $this->assertSame(0.6, $pro->b);
    }

    public function testParseColorFunctionRec2020(): void
    {
        $color = Color::parse('color(rec2020 0.6 0.2 0.8)');
        $this->assertInstanceOf(Rec2020Color::class, $color);

        $rec = $color;
        $this->assertSame(0.6, $rec->r);
        $this->assertSame(0.2, $rec->g);
        $this->assertSame(0.8, $rec->b);
        $this->assertSame(1.0, $rec->a);
    }

    public function testParseColorFunctionRec2020Alias(): void
    {
        $color = Color::parse('color(bt2020 0.1 0.9 0.3 / 0.25)');
        $this->assertInstanceOf(Rec2020Color::class, $color);

        $this->assertEqualsWithDelta(0.25, $color->a, 0.0001);
    }

    public function testParseColorFunctionSrgb(): void
    {
        $color = Color::parse('color(srgb 1 0 0 / 0.75)');
        $this->assertInstanceOf(SrgbColor::class, $color);
        $this->assertSame('#ff0000bf', strtolower($color->toHex(true)));
    }

    public function testParseColorFunctionXyzD65(): void
    {
        $color = Color::parse('color(xyz-d65 0.4 0.2 0.1)');
        $this->assertInstanceOf(XyzColor::class, $color);

        $xyzColor = $color;
        $this->assertSame(0.4, $xyzColor->x);
        $this->assertSame(0.2, $xyzColor->y);
        $this->assertSame(0.1, $xyzColor->z);
        $this->assertSame(1.0, $xyzColor->alpha);
    }

    public function testParseColorFunctionXyzD65WithAlpha(): void
    {
        $color = Color::parse('color(xyz-d65 0.4 0.2 0.1 / 0.7)');
        $this->assertInstanceOf(XyzColor::class, $color);

        $xyzColor = $color;
        $this->assertSame(0.7, $xyzColor->alpha);
    }

    public function testParseCssColorFunctionEdgeCases(): void
    {
        // Test with spaces and different formats
        $color1 = Color::parse('color(srgb 1 0 0)');
        $this->assertInstanceOf(SrgbColor::class, $color1);

        $color2 = Color::parse('color(srgb 0.5 0.5 0.5 / 0.5)');
        $this->assertInstanceOf(SrgbColor::class, $color2);
    }

    public function testParseDelegatesToCssParserForRelativeColor(): void
    {
        $c = Color::parse('oklch(from rgb(255 0 0) l c h / 1)');
        $this->assertInstanceOf(OklchColor::class, $c);
    }

    public function testParseEmptyString(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Empty color string.');
        Color::parse('');
    }

    public function testParseHexEdgeCases(): void
    {
        // Test various hex formats
        $color3 = Color::parse('#abc');
        $this->assertInstanceOf(ColorInterface::class, $color3);

        $color6 = Color::parse('#aabbcc');
        $this->assertInstanceOf(ColorInterface::class, $color6);

        $color8 = Color::parse('#aabbccdd');
        $this->assertInstanceOf(ColorInterface::class, $color8);
    }

    public function testParseHexShort(): void
    {
        $color = Color::parse('#09c');
        $this->assertInstanceOf(ColorInterface::class, $color);
        $this->assertSame('#0099cc', strtolower($color->toHex(false)));
    }

    public function testParseHexWithAlpha(): void
    {
        $color = Color::parse('#336699cc');
        $this->assertInstanceOf(ColorInterface::class, $color);
        $this->assertSame('#336699cc', strtolower($color->toHex(true)));
    }

    public function testParseHslFunctional(): void
    {
        $color = Color::parse('hsl(120 100% 25%)');
        $this->assertInstanceOf(SrgbColor::class, $color);
        $this->assertSame('#008000', strtolower($color->toHex()));
    }

    public function testParseHslVariations(): void
    {
        // Different HSL syntax variations
        $color1 = Color::parse('hsl(0, 100%, 50%)');
        $this->assertInstanceOf(SrgbColor::class, $color1);

        $color2 = Color::parse('hsl(0 100% 50%)');
        $this->assertInstanceOf(SrgbColor::class, $color2);

        $color3 = Color::parse('hsl(0deg 100% 50% / 0.8)');
        $this->assertInstanceOf(SrgbColor::class, $color3);
    }

    public function testParseHwbFunctional(): void
    {
        $color = Color::parse('hwb(120 50% 20%)');
        $this->assertInstanceOf(HwbColor::class, $color);
    }

    public function testParseInvalidColorFunction(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Cannot parse color "color(invalid)".');
        Color::parse('color(invalid)');
    }

    #[DataProvider('invalidFunctionParametersProvider')]
    public function testParseInvalidFunctionParameters(string $color, string $expectedMessage): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage($expectedMessage);
        Color::parse($color);
    }

    public static function invalidFunctionParametersProvider(): iterable
    {
        yield 'oklab too few params' => ['oklab(1 2)', 'Cannot parse color "oklab(1 2)".'];
        yield 'rgb too few params' => ['rgb(1 2)', 'Cannot parse color "rgb(1 2)".'];
        yield 'hsl too few params' => ['hsl(1 2)', 'Cannot parse color "hsl(1 2)".'];
        yield 'oklch too few params' => ['oklch(1 2)', 'Cannot parse color "oklch(1 2)".'];
        yield 'lab too few params' => ['lab(1 2)', 'Cannot parse color "lab(1 2)".'];
        yield 'lch too few params' => ['lch(1 2)', 'Cannot parse color "lch(1 2)".'];
        yield 'color too few params' => ['color(srgb 1 2)', 'Cannot parse color "color(srgb 1 2)".'];
    }

    public function testParseInvalidHex(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Cannot parse color "#invalid-hex".');
        Color::parse('#invalid-hex');
    }

    public function testParseInvalidHsl(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Cannot parse color "hsl(invalid)".');
        Color::parse('hsl(invalid)');
    }

    public function testParseInvalidRgb(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Cannot parse color "rgb(invalid)".');
        Color::parse('rgb(invalid)');
    }

    public function testParseLab(): void
    {
        $color = Color::parse('lab(50 20 -10)');
        $this->assertInstanceOf(LabColor::class, $color);

        $labColor = $color;
        $this->assertSame(50.0, $labColor->l);
        $this->assertSame(20.0, $labColor->a);
        $this->assertSame(-10.0, $labColor->b);
        $this->assertSame(1.0, $labColor->alpha);
    }

    public function testParseLabWithAlpha(): void
    {
        $color = Color::parse('lab(50 20 -10 / 0.8)');
        $this->assertInstanceOf(LabColor::class, $color);

        $labColor = $color;
        $this->assertSame(0.8, $labColor->alpha);
    }

    public function testParseLch(): void
    {
        $color = Color::parse('lch(50 30 120)');
        $this->assertInstanceOf(LchColor::class, $color);

        $lchColor = $color;
        $this->assertSame(50.0, $lchColor->l);
        $this->assertSame(30.0, $lchColor->c);
        $this->assertSame(120.0, $lchColor->h);
        $this->assertSame(1.0, $lchColor->alpha);
    }

    public function testParseLchWithAlpha(): void
    {
        $color = Color::parse('lch(50 30 120 / 0.9)');
        $this->assertInstanceOf(LchColor::class, $color);

        $lchColor = $color;
        $this->assertSame(0.9, $lchColor->alpha);
    }

    #[DataProvider('malformedFunctionSyntaxProvider')]
    public function testParseMalformedFunctionSyntax(string $color, string $expectedMessage): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage($expectedMessage);
        Color::parse($color);
    }

    public static function malformedFunctionSyntaxProvider(): iterable
    {
        yield 'rgb no closing paren' => ['rgb(1 2 3', 'Cannot parse color "rgb(1 2 3".'];
        yield 'hsl no closing paren' => ['hsl(1 2 3', 'Cannot parse color "hsl(1 2 3".'];
        yield 'color no closing paren' => ['color(srgb 1 2 3', 'Cannot parse color "color(srgb 1 2 3".'];
        yield 'oklab no closing paren' => ['oklab(1 2 3', 'Cannot parse color "oklab(1 2 3".'];
        yield 'oklch no closing paren' => ['oklch(1 2 3', 'Cannot parse color "oklch(1 2 3".'];
        yield 'lab no closing paren' => ['lab(1 2 3', 'Cannot parse color "lab(1 2 3".'];
        yield 'lch no closing paren' => ['lch(1 2 3', 'Cannot parse color "lch(1 2 3".'];
    }

    public function testParseNamedColor(): void
    {
        $color = Color::parse('rebeccapurple');
        $this->assertInstanceOf(ColorInterface::class, $color);
        $this->assertSame('#663399', strtolower($color->toHex(false)));
    }

    public function testParseOklab(): void
    {
        $color = Color::parse('oklab(0.7 0.1 0.05)');
        $this->assertInstanceOf(OklabColor::class, $color);

        $oklabColor = $color;
        $this->assertSame(0.7, $oklabColor->l);
        $this->assertSame(0.1, $oklabColor->a);
        $this->assertSame(0.05, $oklabColor->b);
        $this->assertSame(1.0, $oklabColor->alpha);
    }

    public function testParseOklabWithAlpha(): void
    {
        $color = Color::parse('oklab(0.7 0.1 0.05 / 0.8)');
        $this->assertInstanceOf(OklabColor::class, $color);

        $oklabColor = $color;
        $this->assertSame(0.8, $oklabColor->alpha);
    }

    public function testParseOklch(): void
    {
        $color = Color::parse('oklch(0.7 0.15 30)');
        $this->assertInstanceOf(OklchColor::class, $color);

        $oklchColor = $color;
        $this->assertSame(0.7, $oklchColor->l);
        $this->assertSame(0.15, $oklchColor->c);
        $this->assertSame(30.0, $oklchColor->h);
        $this->assertSame(1.0, $oklchColor->alpha);
    }

    public function testParseOklchWithAlpha(): void
    {
        $color = Color::parse('oklch(0.7 0.15 30 / 0.9)');
        $this->assertInstanceOf(OklchColor::class, $color);

        $oklchColor = $color;
        $this->assertSame(0.9, $oklchColor->alpha);
    }

    public function testParseRgbFunctional(): void
    {
        $color = Color::parse('rgb(255 0 0)');
        $this->assertInstanceOf(ColorInterface::class, $color);
        $this->assertSame('#ff0000', strtolower($color->toHex(false)));
    }

    public function testParseRgbVariations(): void
    {
        // Different RGB syntax variations (modern CSS syntax)
        $color1 = Color::parse('rgb(255, 0, 0)');
        $this->assertInstanceOf(ColorInterface::class, $color1);

        $color2 = Color::parse('rgb(255 0 0)');
        $this->assertInstanceOf(ColorInterface::class, $color2);

        $color3 = Color::parse('rgba(255 0 0 / 0.5)');
        $this->assertInstanceOf(ColorInterface::class, $color3);
    }

    public function testParseUnrecognizedColor(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unrecognized color "notacolor".');

        Color::parse('notacolor');
    }

    public function testParseUnsupportedColorSpace(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unsupported color() space "unsupported".');

        Color::parse('color(unsupported 1 1 1)');
    }

    public function testRed(): void
    {
        $c = Color::red();
        $this->assertInstanceOf(SrgbColor::class, $c);
        $this->assertSame('#ff0000', $c->toHex());
    }

    public function testRgb(): void
    {
        $c = Color::rgb(0.1, 0.2, 0.3, 0.4);

        $this->assertSame(0.1, $c->r);
        $this->assertSame(0.2, $c->g);
        $this->assertSame(0.3, $c->b);
        $this->assertSame(0.4, $c->getAlpha());
    }

    public function testTryFromParsesValidString(): void
    {
        $c = Color::tryFrom('#ff0000');
        $this->assertInstanceOf(ColorInterface::class, $c);
    }

    public function testTryFromPassThroughColor(): void
    {
        $color = new SrgbColor(1.0, 0.0, 0.0);
        $this->assertSame($color, Color::tryFrom($color));
    }

    public function testTryFromReturnsNullOnInvalid(): void
    {
        $this->assertNull(Color::tryFrom('not-a-color'));
    }

    public function testWhite(): void
    {
        $c = Color::white();
        $this->assertInstanceOf(SrgbColor::class, $c);
        $this->assertSame('#ffffff', $c->toHex());
    }

    public function testContrastWithBlackAndWhite(): void
    {
        $ratio = Color::contrast('#000000', '#ffffff');
        $this->assertGreaterThanOrEqual(21.0, $ratio);
    }

    public function testContrastWithColorObjects(): void
    {
        $black = Color::parse('#000000');
        $white = Color::parse('#ffffff');
        $ratio = Color::contrast($black, $white);

        $this->assertGreaterThanOrEqual(21.0, $ratio);
    }

    public function testContrastWithSameColor(): void
    {
        $ratio = Color::contrast('#ffffff', '#ffffff');
        $this->assertEqualsWithDelta(1.0, $ratio, 0.01);
    }

    public function testMixWithBothEndpointsTransparent(): void
    {
        $red = Color::parse('rgb(255 0 0 / 0)');
        $blue = Color::parse('rgb(0 0 255 / 0)');

        // A zero interpolated alpha leaves the channels undefined per CSS Color 4; this library
        // falls back to plain interpolation, the limit of the premultiplied formula.
        $oklab = Color::mix($red, $blue, 0.5, 'oklab');
        $this->assertInstanceOf(OklabColor::class, $oklab);
        $this->assertSame(0.0, $oklab->alpha);
        $this->assertEqualsWithDelta(0.539984539499947, $oklab->l, 1e-12);
        $this->assertEqualsWithDelta(0.096203038448605, $oklab->a, 1e-12);
        $this->assertEqualsWithDelta(-0.092840924573820, $oklab->b, 1e-12);

        $srgb = Color::mix($red, $blue, 0.5, 'srgb');
        $this->assertInstanceOf(SrgbColor::class, $srgb);
        $this->assertSame(0.0, $srgb->a);
        $this->assertSame('#bc00bc', $srgb->toHex());
    }

    public function testMixWithColorInterfaceSpace(): void
    {
        $red = Color::parse('#ff0000');
        $blue = Color::parse('#0000ff');
        $oklabSpace = Color::parse('oklab(0.5 0 0)');

        $result = Color::mix($red, $blue, 0.5, $oklabSpace);

        $this->assertInstanceOf(OklabColor::class, $result);
    }

    public function testMixWithNegativeT(): void
    {
        $red = SrgbColor::parseHex('#ff0000');
        $blue = SrgbColor::parseHex('#0000ff');

        $result = Color::mix($red, $blue, -0.5, 'srgb');

        // Negative t should clamp to 0
        $srgb = $result->toSrgb();
        $this->assertEqualsWithDelta(1.0, $srgb->r, 0.01);
    }

    public function testMixWithOneT(): void
    {
        $red = Color::parse('#ff0000');
        $blue = Color::parse('#0000ff');
        $result = Color::mix($red, $blue, 1.0, 'srgb');

        // t=1 should return blue
        $srgb = $result->toSrgb();
        $this->assertEqualsWithDelta(0.0, $srgb->r, 0.01);
        $this->assertEqualsWithDelta(1.0, $srgb->b, 0.01);
    }

    public function testMixWithOneTPreservesAlpha(): void
    {
        $red = Color::parse('rgb(255 0 0 / 0.8)');
        $blue = Color::parse('rgb(0 0 255 / 0.2)');

        // lerp() is not bit-exact at t=1, so alpha lands one ulp below 0.2 here.
        $oklab = Color::mix($red, $blue, 1.0, 'oklab');
        $this->assertInstanceOf(OklabColor::class, $oklab);
        $this->assertEqualsWithDelta(0.2, $oklab->alpha, 1e-12);
        $this->assertSame('#0000ff', $oklab->toSrgb()->toHex());

        $srgb = Color::mix($red, $blue, 1.0, 'srgb');
        $this->assertInstanceOf(SrgbColor::class, $srgb);
        $this->assertEqualsWithDelta(0.2, $srgb->a, 1e-12);
        $this->assertSame('#0000ff', $srgb->toHex());
    }

    public function testMixWithOpaqueEndpoints(): void
    {
        $red = Color::parse('rgb(255 0 0)');
        $blue = Color::parse('rgb(0 0 255)');

        $oklab = Color::mix($red, $blue, 0.5, 'oklab');
        $this->assertInstanceOf(OklabColor::class, $oklab);
        $this->assertSame(1.0, $oklab->alpha);
        $this->assertEqualsWithDelta(0.539984539499947, $oklab->l, 1e-12);
        $this->assertEqualsWithDelta(0.096203038448605, $oklab->a, 1e-12);
        $this->assertEqualsWithDelta(-0.092840924573820, $oklab->b, 1e-12);
        $this->assertSame('#8c53a2', $oklab->toSrgb()->toHex());

        $srgb = Color::mix($red, $blue, 0.5, 'srgb');
        $this->assertInstanceOf(SrgbColor::class, $srgb);
        $this->assertSame(1.0, $srgb->a);
        $this->assertEqualsWithDelta(0.735356983052449, $srgb->r, 1e-12);
        $this->assertEqualsWithDelta(0.0, $srgb->g, 1e-12);
        $this->assertEqualsWithDelta(0.735356983052449, $srgb->b, 1e-12);
        $this->assertSame('#bc00bc', $srgb->toHex());
    }

    public function testMixWithOverflowT(): void
    {
        $red = Color::parse('#ff0000');
        $blue = Color::parse('#0000ff');
        $result = Color::mix($red, $blue, 1.5, 'srgb');

        // t > 1 should clamp to 1
        $srgb = $result->toSrgb();
        $this->assertEqualsWithDelta(1.0, $srgb->b, 0.01);
    }

    public function testMixWithPartialAlphaOklab(): void
    {
        $red = Color::parse('rgb(255 0 0 / 0.8)');
        $blue = Color::parse('rgb(0 0 255 / 0.2)');

        $mixed = Color::mix($red, $blue, 0.5, 'oklab');

        $this->assertInstanceOf(OklabColor::class, $mixed);
        $this->assertSame(0.5, $mixed->alpha);
        // Equal weights premultiplied reduce to the alpha-weighted average 0.8 * red + 0.2 * blue.
        $this->assertEqualsWithDelta(0.592767032168710, $mixed->l, 1e-12);
        $this->assertEqualsWithDelta(0.173399052019026, $mixed->a, 1e-12);
        $this->assertEqualsWithDelta(0.038371409288913, $mixed->b, 1e-12);
    }

    public function testMixWithPartialAlphaSrgb(): void
    {
        $red = Color::parse('rgb(255 0 0 / 0.8)');
        $blue = Color::parse('rgb(0 0 255 / 0.2)');

        $mixed = Color::mix($red, $blue, 0.5, 'srgb');

        $this->assertInstanceOf(SrgbColor::class, $mixed);
        $this->assertSame(0.5, $mixed->a);
        $this->assertEqualsWithDelta(0.906331753344059, $mixed->r, 1e-12);
        $this->assertEqualsWithDelta(0.0, $mixed->g, 1e-12);
        $this->assertEqualsWithDelta(0.484529204481707, $mixed->b, 1e-12);
        $this->assertSame('#e7007c', $mixed->toHex());
    }

    public function testMixWithTransparentEndpointPreservesHue(): void
    {
        $red = Color::parse('rgb(255 0 0 / 1)');
        $blue = Color::parse('rgb(0 0 255 / 0)');

        $oklab = Color::mix($red, $blue, 0.5, 'oklab');
        $this->assertInstanceOf(OklabColor::class, $oklab);
        $this->assertSame(0.5, $oklab->alpha);
        $this->assertSame('#ff0000', $oklab->toSrgb()->toHex());

        $srgb = Color::mix($red, $blue, 0.5, 'srgb');
        $this->assertInstanceOf(SrgbColor::class, $srgb);
        $this->assertSame(0.5, $srgb->a);
        $this->assertSame('#ff0000', $srgb->toHex());
    }

    public function testMixWithZeroT(): void
    {
        $red = Color::parse('#ff0000');
        $blue = Color::parse('#0000ff');
        $result = Color::mix($red, $blue, 0.0, 'srgb');

        // t=0 should return red
        $srgb = $result->toSrgb();
        $this->assertEqualsWithDelta(1.0, $srgb->r, 0.01);
        $this->assertEqualsWithDelta(0.0, $srgb->b, 0.01);
    }

    public function testMixWithZeroTPreservesAlpha(): void
    {
        $red = Color::parse('rgb(255 0 0 / 0.8)');
        $blue = Color::parse('rgb(0 0 255 / 0.2)');

        $oklab = Color::mix($red, $blue, 0.0, 'oklab');
        $this->assertInstanceOf(OklabColor::class, $oklab);
        $this->assertSame(0.8, $oklab->alpha);
        $this->assertSame('#ff0000', $oklab->toSrgb()->toHex());

        $srgb = Color::mix($red, $blue, 0.0, 'srgb');
        $this->assertInstanceOf(SrgbColor::class, $srgb);
        $this->assertSame(0.8, $srgb->a);
        $this->assertSame('#ff0000', $srgb->toHex());
    }

    public function testParseColorFunctionEmptyParams(): void
    {
        $this->expectException(ParseException::class);
        Color::parse('color()');
    }

    public function testParseColorFunctionMissingSpace(): void
    {
        $this->expectException(ParseException::class);
        Color::parse('color(srgb1 0 0)');
    }

    public function testParseColorFunctionTooFewParams(): void
    {
        $this->expectException(ParseException::class);
        Color::parse('color(srgb 1 0)');
    }

    public function testParseHex3DigitExpands(): void
    {
        $color = Color::parse('#abc');
        $this->assertSame('#aabbcc', strtolower($color->toHex()));
    }

    public function testParseHex4Digit(): void
    {
        $color = Color::parse('#f0a8');
        $hex = strtolower($color->toHex(true));
        $this->assertSame('#ff00aa88', $hex);
    }

    public function testParseHslClampsPercentages(): void
    {
        $color = Color::parse('hsl(0 150% 150%)');
        $srgb = $color->toSrgb();
        $this->assertInstanceOf(SrgbColor::class, $srgb);
    }

    public function testParseHslEmptyParams(): void
    {
        $this->expectException(ParseException::class);
        Color::parse('hsl()');
    }

    public function testParseHslMissingPercentSign(): void
    {
        $this->expectException(ParseException::class);
        Color::parse('hsl(0 100 50)');
    }

    public function testParseHslWithNegativeSaturation(): void
    {
        $color = Color::parse('hsl(0 -50% 50%)');
        $srgb = $color->toSrgb();
        $this->assertInstanceOf(SrgbColor::class, $srgb);
    }

    public function testParseHueNegativeWraps(): void
    {
        $color = Color::parse('hsl(-60 100% 50%)');
        $this->assertInstanceOf(SrgbColor::class, $color);
    }

    public function testParseHueOverflow(): void
    {
        $color = Color::parse('hsl(480 100% 50%)');
        $this->assertSame('#00ff00', strtolower($color->toHex()));
    }

    public function testParseHueWithDegrees(): void
    {
        $color = Color::parse('hsl(120deg 100% 50%)');
        $this->assertSame('#00ff00', strtolower($color->toHex()));
    }

    public function testParseHueWithGradians(): void
    {
        $color = Color::parse('hsl(100grad 100% 50%)');
        $this->assertInstanceOf(SrgbColor::class, $color);
    }

    public function testParseHueWithRadians(): void
    {
        $color = Color::parse('hsl(3.14159rad 100% 50%)');
        $this->assertInstanceOf(SrgbColor::class, $color);
    }

    public function testParseHueWithTurns(): void
    {
        $color = Color::parse('hsl(0.5turn 100% 50%)');
        $this->assertInstanceOf(SrgbColor::class, $color);
    }

    public function testParseLabEmptyParams(): void
    {
        $this->expectException(ParseException::class);
        Color::parse('lab()');
    }

    public function testParseLchEmptyParams(): void
    {
        $this->expectException(ParseException::class);
        Color::parse('lch()');
    }

    public function testParseOklabEmptyParams(): void
    {
        $this->expectException(ParseException::class);
        Color::parse('oklab()');
    }

    public function testParseOklabTooFewParams(): void
    {
        $this->expectException(ParseException::class);
        Color::parse('oklab(0.5 0.1)');
    }

    public function testParseOklchEmptyParams(): void
    {
        $this->expectException(ParseException::class);
        Color::parse('oklch()');
    }

    public function testParseRgbaClampsValues(): void
    {
        $color = Color::parse('rgb(300 -50 128)');
        $srgb = $color->toSrgb();
        $this->assertEqualsWithDelta(1.0, $srgb->r, 0.01);
        $this->assertEqualsWithDelta(0.0, $srgb->g, 0.01);
    }

    public function testParseRgbEmptyParams(): void
    {
        $this->expectException(ParseException::class);
        Color::parse('rgb()');
    }

    public function testParseRgbInvalidParams(): void
    {
        $this->expectException(ParseException::class);
        Color::parse('rgb(255 0)');
    }

    public function testParseRgbMixedPercentagesAndNumbers(): void
    {
        $color = Color::parse('rgb(255 0% 0)');
        $this->assertSame('#ff0000', strtolower($color->toHex()));
    }

    public function testParseRgbWithAlphaNumber(): void
    {
        $color = Color::parse('rgb(255 0 0 / 0.5)');
        $srgb = $color->toSrgb();
        $this->assertEqualsWithDelta(0.5, $srgb->a, 0.01);
    }

    public function testParseRgbWithAlphaPercentage(): void
    {
        $color = Color::parse('rgb(255 0 0 / 50%)');
        $srgb = $color->toSrgb();
        $this->assertEqualsWithDelta(0.5, $srgb->a, 0.01);
    }

    public function testParseRgbWithPercentages(): void
    {
        $color = Color::parse('rgb(100% 0% 0%)');
        $this->assertSame('#ff0000', strtolower($color->toHex()));
    }
}
