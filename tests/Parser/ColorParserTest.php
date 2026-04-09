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

namespace PhpColor\Color\Tests\Parser;

use PhpColor\Color\Exception\ParseException;
use PhpColor\Color\HwbColor;
use PhpColor\Color\LabColor;
use PhpColor\Color\LchColor;
use PhpColor\Color\OklabColor;
use PhpColor\Color\OklchColor;
use PhpColor\Color\Parser\ColorParser;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ColorParser::class)]
final class ColorParserTest extends TestCase
{
    public function testDelegatesToCssParserWhenRelativeFromFound(): void
    {
        $c = ColorParser::parse('rgb(from red r g b / 50%)');
        $this->assertInstanceOf(SrgbColor::class, $c);
    }

    public function testEmptyStringThrows(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Empty color string.');
        ColorParser::parse('');
    }

    public function testHexDispatch(): void
    {
        $c = ColorParser::parse('#f00');
        $this->assertInstanceOf(SrgbColor::class, $c);
        $this->assertSame('#ff0000', $c->toHex());
    }

    public function testNamedColorNoParenDispatch(): void
    {
        $c = ColorParser::parse('red');
        $this->assertInstanceOf(SrgbColor::class, $c);
    }

    public function testParsesColorFunction(): void
    {
        $this->assertInstanceOf(SrgbColor::class, ColorParser::parse('color(srgb 1 0 0)'));
    }

    public function testParsesHsl(): void
    {
        $this->assertInstanceOf(SrgbColor::class, ColorParser::parse('hsl(0 100% 50%)'));
    }

    public function testParsesHwb(): void
    {
        $this->assertInstanceOf(HwbColor::class, ColorParser::parse('hwb(0 0% 0%)'));
    }

    public function testParsesLab(): void
    {
        $this->assertInstanceOf(LabColor::class, ColorParser::parse('lab(50 20 -20)'));
    }

    public function testParsesLch(): void
    {
        $this->assertInstanceOf(LchColor::class, ColorParser::parse('lch(50 30 180)'));
    }

    public function testParsesOklab(): void
    {
        $this->assertInstanceOf(OklabColor::class, ColorParser::parse('oklab(0.5 0.1 0.2)'));
    }

    public function testParsesOklch(): void
    {
        $this->assertInstanceOf(OklchColor::class, ColorParser::parse('oklch(0.5 0.2 180)'));
    }

    public function testParsesRgb(): void
    {
        $this->assertInstanceOf(SrgbColor::class, ColorParser::parse('rgb(255 0 0)'));
    }

    public function testUnknownFunctionThrows(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Cannot parse color "funky(1 2 3)".');
        ColorParser::parse('funky(1 2 3)');
    }
}
