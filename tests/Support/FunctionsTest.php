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

namespace PhpColor\Color\Tests\Support;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function PhpColor\Color\black;
use function PhpColor\Color\blue;
use function PhpColor\Color\green;
use function PhpColor\Color\hex;
use function PhpColor\Color\mix;
use function PhpColor\Color\oklab;
use function PhpColor\Color\oklch;
use function PhpColor\Color\parse;
use function PhpColor\Color\red;
use function PhpColor\Color\rgb;
use function PhpColor\Color\white;

#[CoversFunction('PhpColor\Color\black')]
#[CoversFunction('PhpColor\Color\blue')]
#[CoversFunction('PhpColor\Color\green')]
#[CoversFunction('PhpColor\Color\hex')]
#[CoversFunction('PhpColor\Color\mix')]
#[CoversFunction('PhpColor\Color\oklab')]
#[CoversFunction('PhpColor\Color\oklch')]
#[CoversFunction('PhpColor\Color\parse')]
#[CoversFunction('PhpColor\Color\red')]
#[CoversFunction('PhpColor\Color\rgb')]
#[CoversFunction('PhpColor\Color\white')]
class FunctionsTest extends TestCase
{
    public function testParse(): void
    {
        $color = parse('red');
        self::assertSame('rgb(255 0 0)', (string) $color);
    }

    public function testRgb(): void
    {
        $color = rgb(1.0, 0, 0);
        self::assertSame('rgb(255 0 0)', (string) $color);
    }

    public function testHex(): void
    {
        $color = hex('#ff0000');
        self::assertSame('rgb(255 0 0)', (string) $color);
    }

    public function testOklab(): void
    {
        $color = oklab(0.628, 0.225, 0.126);
        self::assertSame('oklab(0.628 0.225 0.126)', (string) $color);
    }

    public function testOklch(): void
    {
        $color = oklch(0.628, 0.257, 29.23);
        self::assertSame('oklch(0.628 0.257 29.23)', (string) $color);
    }

    public function testBlack(): void
    {
        $color = black();
        self::assertSame('rgb(0 0 0)', (string) $color);
    }

    public function testWhite(): void
    {
        $color = white();
        self::assertSame('rgb(255 255 255)', (string) $color);
    }

    public function testRed(): void
    {
        $color = red();
        self::assertSame('rgb(255 0 0)', (string) $color);
    }

    public function testGreen(): void
    {
        $color = green();
        self::assertSame('rgb(0 255 0)', (string) $color);
    }

    public function testBlue(): void
    {
        $color = blue();
        self::assertSame('rgb(0 0 255)', (string) $color);
    }

    public function testMix(): void
    {
        $mixed = mix('red', 'blue', 0.5, 'srgb');
        self::assertSame('rgb(188 0 188)', (string) $mixed);
    }
}
