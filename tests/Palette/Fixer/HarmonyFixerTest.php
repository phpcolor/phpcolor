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

namespace PhpColor\Color\Tests\Palette\Fixer;

use PhpColor\Color\Color;
use PhpColor\Color\ColorInterface;
use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\Palette\ColorPalette;
use PhpColor\Color\Palette\Fixer\HarmonyFixer;
use PhpColor\Color\Palette\Harmony\HarmonyPattern;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HarmonyFixer::class)]
final class HarmonyFixerTest extends TestCase
{
    private HarmonyFixer $fixer;

    protected function setUp(): void
    {
        $this->fixer = new HarmonyFixer();
    }

    public function testAnalyzePerfectTriadic(): void
    {
        $palette = ColorPalette::scale(Color::red()->triadic());
        $analysis = $this->fixer->analyze($palette);
        $this->assertSame(HarmonyPattern::Triadic, $analysis['detected_harmony']);
    }

    public function testAnalyzeEmptyPalette(): void
    {
        $palette = ColorPalette::scale([Color::red()]);
        $analysis = $this->fixer->analyze($palette);
        $this->assertNull($analysis['detected_harmony']);
        $this->assertCount(1, $analysis['issues']);
    }

    public function testFixWithEnum(): void
    {
        $palette = ColorPalette::scale([Color::red(), Color::blue()]);
        $fixed = $this->fixer->fix($palette, ['target_harmony' => HarmonyPattern::Complementary]);
        $this->assertCount(2, $fixed);
    }

    public function testFixWithString(): void
    {
        $palette = ColorPalette::scale([Color::red(), Color::blue()]);
        $fixed = $this->fixer->fix($palette, ['target_harmony' => 'complementary']);
        $this->assertCount(2, $fixed);
    }

    public function testFixWithInvalidStringThrows(): void
    {
        $this->expectException(InvalidColorException::class);
        $this->fixer->fix(ColorPalette::scale([Color::red(), Color::blue()]), ['target_harmony' => 'invalid']);
    }

    public function testFixWithInvalidOptionTypeThrows(): void
    {
        $this->expectException(InvalidColorException::class);
        $this->fixer->fix(ColorPalette::scale([Color::red(), Color::blue()]), ['target_harmony' => 123]);
    }

    public function testFixWithInvalidBaseIndexThrows(): void
    {
        $this->expectException(InvalidColorException::class);
        $this->fixer->fix(ColorPalette::scale([Color::red(), Color::blue()]), ['base_color_index' => '0']);
    }

    public function testApplyHarmonyWithNamedPalette(): void
    {
        $palette = ColorPalette::named(['r' => Color::red(), 'g' => Color::green()]);
        $fixed = $this->fixer->fix($palette, ['target_harmony' => HarmonyPattern::Complementary]);
        $this->assertTrue($fixed->isNamed());
    }

    public function testFixHandlesAchromaticColors(): void
    {
        $palette = ColorPalette::scale([Color::red(), Color::parse('gray')]);
        $fixed = $this->fixer->fix($palette, ['target_harmony' => HarmonyPattern::Complementary]);
        $this->assertCount(2, $fixed);
    }

    public function testFixWithBaseColorIndexOutOfBounds(): void
    {
        $palette = ColorPalette::scale([Color::red(), Color::blue()]);
        $fixed = $this->fixer->fix($palette, ['base_color_index' => 10]);
        $this->assertCount(2, $fixed);
    }

    public function testFixDefaultsToAnalogousWhenNoHarmonyDetected(): void
    {
        // Single-color palette: analyze() returns null detected_harmony.
        // No target_harmony option either => falls through to the null branch (L40) and picks Analogous.
        $palette = ColorPalette::scale([Color::red()]);
        $fixed = $this->fixer->fix($palette);
        $this->assertCount(1, $fixed);
    }

    public function testApplyHarmonyCastsNonOklchColors(): void
    {
        // Exercises L111 (base color cast) and L121 (loop color cast):
        // when to('oklch') returns a non-OklchColor, the code must fall back to OklchColor::fromSrgb().
        $srgb = new SrgbColor(0.8, 0.2, 0.1);
        $fake = $this->createStub(ColorInterface::class);
        $fake->method('to')->willReturn($srgb);
        $fake->method('toSrgb')->willReturn($srgb);

        $palette = ColorPalette::scale([$fake, $fake]);
        $fixed = $this->fixer->fix($palette, ['target_harmony' => HarmonyPattern::Complementary]);
        $this->assertCount(2, $fixed);
    }
}
