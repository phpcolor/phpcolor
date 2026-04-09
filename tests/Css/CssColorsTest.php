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

namespace PhpColor\Color\Tests\Css;

use PhpColor\Color\Css\CssColors;
use PhpColor\Color\Exception\ParseException;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CssColors::class)]
final class CssColorsTest extends TestCase
{
    public function testAllContainsKnownColors(): void
    {
        $colors = CssColors::all();

        $this->assertArrayHasKey('red', $colors);
        $this->assertArrayHasKey('blue', $colors);
        $this->assertArrayHasKey('green', $colors);
        $this->assertArrayHasKey('rebeccapurple', $colors);
    }

    public function testAllReturnsArrayOfColors(): void
    {
        $colors = CssColors::all();

        $this->assertIsArray($colors);
        $this->assertCount(148, $colors);
    }

    public function testAllValuesAreValidHex(): void
    {
        $colors = CssColors::all();

        foreach ($colors as $name => $hex) {
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $hex, "Color '{$name}' should be valid hex");
        }
    }

    public function testAllValuesHaveHashPrefix(): void
    {
        $colors = CssColors::all();

        foreach ($colors as $name => $hex) {
            $this->assertStringStartsWith('#', $hex, "Color '{$name}' should have # prefix");
            $this->assertSame(7, \strlen($hex), "Color '{$name}' should be #rrggbb format");
        }
    }

    public function testMapContainsExpectedNumberOfColors(): void
    {
        // According to actual count, there are 148 colors
        $this->assertCount(148, CssColors::COLORS);
    }

    public function testMapKeysAreLowercase(): void
    {
        foreach (array_keys(CssColors::COLORS) as $key) {
            $this->assertSame(strtolower($key), $key, "Color name '{$key}' should be lowercase");
        }
    }

    public function testMapValuesAre6CharHex(): void
    {
        foreach (CssColors::COLORS as $name => $hex) {
            $this->assertSame(6, \strlen($hex), "Color '{$name}' hex value should be 6 characters");
            $this->assertMatchesRegularExpression('/^[0-9a-f]{6}$/', $hex, "Color '{$name}' hex value should be valid hex");
        }
    }

    public function testSpecificColorValues(): void
    {
        // Test a few specific colors to ensure correctness
        $this->assertSame('00ffff', CssColors::tryHex('cyan'));
        $this->assertSame('ff00ff', CssColors::tryHex('magenta'));
        $this->assertSame('ffff00', CssColors::tryHex('yellow'));
        $this->assertSame('ffa500', CssColors::tryHex('orange'));
        $this->assertSame('800080', CssColors::tryHex('purple'));
    }

    public function testTryHexHandlesGrayAndGrey(): void
    {
        // Both spellings should work
        $this->assertSame('808080', CssColors::tryHex('gray'));
        $this->assertSame('808080', CssColors::tryHex('grey'));
        $this->assertSame('a9a9a9', CssColors::tryHex('darkgray'));
        $this->assertSame('a9a9a9', CssColors::tryHex('darkgrey'));
    }

    public function testTryHexIsCaseInsensitive(): void
    {
        $this->assertSame('ff0000', CssColors::tryHex('RED'));
        $this->assertSame('ff0000', CssColors::tryHex('Red'));
        $this->assertSame('ff0000', CssColors::tryHex('rEd'));
    }

    public function testTryHexReturnsKnownColors(): void
    {
        $this->assertSame('ff0000', CssColors::tryHex('red'));
        $this->assertSame('00ff00', CssColors::tryHex('lime'));
        $this->assertSame('0000ff', CssColors::tryHex('blue'));
        $this->assertSame('ffffff', CssColors::tryHex('white'));
        $this->assertSame('000000', CssColors::tryHex('black'));
        $this->assertSame('663399', CssColors::tryHex('rebeccapurple'));
    }

    public function testTryHexReturnsNullForUnknownColors(): void
    {
        $this->assertNull(CssColors::tryHex('unknowncolor'));
        $this->assertNull(CssColors::tryHex('notacolor'));
        $this->assertNull(CssColors::tryHex(''));
    }

    public function testTryHexTrimsWhitespace(): void
    {
        $this->assertSame('ff0000', CssColors::tryHex('  red  '));
        $this->assertSame('ff0000', CssColors::tryHex("\tred\n"));
    }

    public function testParseCaseInsensitive(): void
    {
        $color = CssColors::parse('BLUE');

        $this->assertInstanceOf(SrgbColor::class, $color);
        $this->assertSame('#0000ff', strtolower($color->toHex()));
    }

    public function testParseConvertsToCorrectRgbValues(): void
    {
        $white = CssColors::parse('white');
        $this->assertEqualsWithDelta(1.0, $white->r, 0.001);
        $this->assertEqualsWithDelta(1.0, $white->g, 0.001);
        $this->assertEqualsWithDelta(1.0, $white->b, 0.001);

        $black = CssColors::parse('black');
        $this->assertEqualsWithDelta(0.0, $black->r, 0.001);
        $this->assertEqualsWithDelta(0.0, $black->g, 0.001);
        $this->assertEqualsWithDelta(0.0, $black->b, 0.001);
    }

    public function testParseReturnsValidSrgbColor(): void
    {
        $color = CssColors::parse('red');

        $this->assertInstanceOf(SrgbColor::class, $color);
        $this->assertSame('#ff0000', strtolower($color->toHex()));
    }

    public function testParseThrowsOnEmptyString(): void
    {
        $this->expectException(ParseException::class);

        CssColors::parse('');
    }

    public function testParseThrowsOnUnknownColor(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unrecognized color');

        CssColors::parse('notacolor');
    }

    public function testParseTrimsWhitespace(): void
    {
        $color = CssColors::parse('  green  ');

        $this->assertInstanceOf(SrgbColor::class, $color);
        $this->assertSame('#008000', strtolower($color->toHex()));
    }

    public function testTryParseReturnsColorOnValidInput(): void
    {
        $color = CssColors::tryParse('red');

        $this->assertInstanceOf(SrgbColor::class, $color);
        $this->assertSame('#ff0000', strtolower($color->toHex()));
    }

    public function testTryParseReturnsNullOnEmptyString(): void
    {
        $this->assertNull(CssColors::tryParse(''));
    }

    public function testTryParseReturnsNullOnUnknownColor(): void
    {
        $this->assertNull(CssColors::tryParse('notacolor'));
    }
}
