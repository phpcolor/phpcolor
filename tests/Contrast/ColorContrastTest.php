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

namespace PhpColor\Color\Tests\Contrast;

use PhpColor\Color\Color;
use PhpColor\Color\Contrast\ColorContrast;
use PhpColor\Color\Contrast\WcagLevel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ColorContrast::class)]
final class ColorContrastTest extends TestCase
{
    // --- calculate() ---

    public function testCalculateContrastWithSameColors(): void
    {
        $diff = ColorContrast::calculate(Color::red(), Color::red());

        $this->assertIsFloat($diff);
        $this->assertEquals(1, $diff);
    }

    public function testCalculateBlackOnWhiteIsMaximum(): void
    {
        $ratio = ColorContrast::calculate(Color::black(), Color::white());

        $this->assertEqualsWithDelta(21.0, $ratio, 0.01);
    }

    public function testCalculateIsSymmetric(): void
    {
        $a = Color::parse('#ff0000');
        $b = Color::parse('#0000ff');

        $this->assertSame(
            ColorContrast::calculate($a, $b),
            ColorContrast::calculate($b, $a),
        );
    }

    public function testContrastAcceptsStringInputs(): void
    {
        $c = Color::contrast('#ff0000', '#0000ff');
        $this->assertIsFloat($c);
        $this->assertGreaterThan(1.0, $c);
    }

    public function testSrgbToLinearLowBranch(): void
    {
        // Very dark component triggers the (<= 0.04045) branch
        $nearBlack = Color::rgb(0.02, 0.0, 0.0);
        $black = Color::black();

        $contrast = ColorContrast::calculate($nearBlack, $black);

        $this->assertIsFloat($contrast);
        $this->assertGreaterThan(1.0, $contrast);
    }

    public function testSrgbToLinearHighBranch(): void
    {
        // Full white triggers the (> 0.04045) branch
        $ratio = ColorContrast::calculate(Color::white(), Color::black());

        $this->assertEqualsWithDelta(21.0, $ratio, 0.01);
    }

    // --- requiredRatio() ---

    public function testRequiredRatioAANormal(): void
    {
        $this->assertSame(4.5, ColorContrast::requiredRatio(WcagLevel::AA, false));
    }

    public function testRequiredRatioAALarge(): void
    {
        $this->assertSame(3.0, ColorContrast::requiredRatio(WcagLevel::AA, true));
    }

    public function testRequiredRatioAAANormal(): void
    {
        $this->assertSame(7.0, ColorContrast::requiredRatio(WcagLevel::AAA, false));
    }

    public function testRequiredRatioAAALarge(): void
    {
        $this->assertSame(4.5, ColorContrast::requiredRatio(WcagLevel::AAA, true));
    }

    public function testRequiredRatioAcceptsStringAA(): void
    {
        $this->assertSame(4.5, ColorContrast::requiredRatio(WcagLevel::AA, false));
        $this->assertSame(3.0, ColorContrast::requiredRatio(WcagLevel::AA, true));
    }

    public function testRequiredRatioAcceptsStringAAA(): void
    {
        $this->assertSame(7.0, ColorContrast::requiredRatio(WcagLevel::AAA, false));
        $this->assertSame(4.5, ColorContrast::requiredRatio(WcagLevel::AAA, true));
    }

    public function testRequiredRatioDefaultIsAA(): void
    {
        $this->assertSame(4.5, ColorContrast::requiredRatio());
    }

    // --- meets() ---

    public function testMeetsReturnsTrueWhenRatioSufficient(): void
    {
        $this->assertTrue(ColorContrast::meets(21.0, WcagLevel::AA));
        $this->assertTrue(ColorContrast::meets(21.0, WcagLevel::AAA));
        $this->assertTrue(ColorContrast::meets(4.5, WcagLevel::AA, false));
        $this->assertTrue(ColorContrast::meets(3.0, WcagLevel::AA, true));
        $this->assertTrue(ColorContrast::meets(7.0, WcagLevel::AAA, false));
        $this->assertTrue(ColorContrast::meets(4.5, WcagLevel::AAA, true));
    }

    public function testMeetsReturnsFalseWhenRatioInsufficient(): void
    {
        $this->assertFalse(ColorContrast::meets(4.4, WcagLevel::AA, false));
        $this->assertFalse(ColorContrast::meets(2.9, WcagLevel::AA, true));
        $this->assertFalse(ColorContrast::meets(6.9, WcagLevel::AAA, false));
        $this->assertFalse(ColorContrast::meets(4.4, WcagLevel::AAA, true));
    }

    public function testMeetsAcceptsStringLevel(): void
    {
        $this->assertTrue(ColorContrast::meets(21.0, WcagLevel::AA));
        $this->assertTrue(ColorContrast::meets(21.0, WcagLevel::AAA));
        $this->assertFalse(ColorContrast::meets(4.4, WcagLevel::AA));
    }

    // --- meetsFor() ---

    public function testMeetsForBlackOnWhitePassesAll(): void
    {
        $black = Color::black();
        $white = Color::white();

        $this->assertTrue(ColorContrast::meetsFor($black, $white, WcagLevel::AA, false));
        $this->assertTrue(ColorContrast::meetsFor($black, $white, WcagLevel::AA, true));
        $this->assertTrue(ColorContrast::meetsFor($black, $white, WcagLevel::AAA, false));
        $this->assertTrue(ColorContrast::meetsFor($black, $white, WcagLevel::AAA, true));
    }

    public function testMeetsForMidGrayFailsAAANormal(): void
    {
        $mid = Color::parse('#777');
        $white = Color::white();

        $this->assertFalse(ColorContrast::meetsFor($mid, $white, WcagLevel::AAA, false));
    }

    public function testMeetsForAcceptsStringLevel(): void
    {
        $black = Color::black();
        $white = Color::white();

        $this->assertTrue(ColorContrast::meetsFor($black, $white, WcagLevel::AA));
        $this->assertTrue(ColorContrast::meetsFor($black, $white, WcagLevel::AAA));
    }
}
