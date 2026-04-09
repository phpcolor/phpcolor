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
use PhpColor\Color\Contrast\ApcaContrast;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApcaContrast::class)]
final class ApcaContrastTest extends TestCase
{
    public function testPolaritySigns(): void
    {
        $white = Color::white();
        $black = Color::black();

        $normal = ApcaContrast::lc($black, $white);   // dark on light
        $reverse = ApcaContrast::lc($white, $black);  // light on dark

        $this->assertGreaterThan(0.0, $normal);
        $this->assertLessThan(0.0, $reverse);
        $this->assertGreaterThan(abs($reverse), $normal); // preferred polarity usually higher magnitude
    }

    public function testZeroForIdenticalColors(): void
    {
        $red = Color::red();
        $this->assertSame(0.0, ApcaContrast::lc($red, $red));
    }

    public function testNormalPolarityReturnsPositiveNonZero(): void
    {
        $fg = Color::black();
        $bg = Color::white();
        $lc = ApcaContrast::lc($fg, $bg);
        $this->assertGreaterThan(0.0, $lc); // exercises return max(0.0, $Lc)
    }

    public function testReversePolarityReturnsNegativeNonZero(): void
    {
        $fg = Color::white();
        $bg = Color::black();
        $lc = ApcaContrast::lc($fg, $bg);
        $this->assertLessThan(0.0, $lc); // exercises return min(0.0, $Lc)
    }

    public function testNearZeroClampNormalPolarity(): void
    {
        // Find a delta where lc > 0, then shrink until clamp returns 0.0
        $fg = Color::rgb(0.50, 0.50, 0.50);
        $delta = 1e-3;
        $bg = Color::rgb(0.50 + $delta, 0.50 + $delta, 0.50 + $delta);
        $lcPrev = ApcaContrast::lc($fg, $bg);
        // Ensure starting point is non-zero and positive (normal polarity)
        if (0.0 === $lcPrev) {
            $delta = 1e-2; // back off if needed
            $bg = Color::rgb(0.50 + $delta, 0.50 + $delta, 0.50 + $delta);
            $lcPrev = ApcaContrast::lc($fg, $bg);
        }
        $this->assertGreaterThan(0.0, $lcPrev);
        // Shrink delta until we hit clamp
        $lc = $lcPrev;
        for ($i = 0; $i < 24 && 0.0 !== $lc; ++$i) {
            $delta *= 0.1;
            $bg = Color::rgb(0.50 + $delta, 0.50 + $delta, 0.50 + $delta);
            $lc = ApcaContrast::lc($fg, $bg);
        }
        $this->assertSame(0.0, $lc);
    }

    public function testNearZeroClampReversePolarity(): void
    {
        // Start with non-zero reverse polarity then shrink to clamp 0.0
        $bg = Color::rgb(0.50, 0.50, 0.50);
        $delta = 1e-3;
        $fg = Color::rgb(0.50 + $delta, 0.50 + $delta, 0.50 + $delta);
        $lcPrev = ApcaContrast::lc($fg, $bg);
        if (0.0 === $lcPrev) {
            $delta = 1e-2;
            $fg = Color::rgb(0.50 + $delta, 0.50 + $delta, 0.50 + $delta);
            $lcPrev = ApcaContrast::lc($fg, $bg);
        }
        $this->assertLessThan(0.0, $lcPrev); // reverse polarity should be negative initially
        $lc = $lcPrev;
        for ($i = 0; $i < 24 && 0.0 !== $lc; ++$i) {
            $delta *= 0.1;
            $fg = Color::rgb(0.50 + $delta, 0.50 + $delta, 0.50 + $delta);
            $lc = ApcaContrast::lc($fg, $bg);
        }
        $this->assertSame(0.0, $lc);
    }

    public function testSrgbToLinearThresholdBranch(): void
    {
        // Exercise srgbToLinear low-branch exactly at the 0.04045 threshold
        $v = 0.04045; // boundary where branch changes
        $fg = Color::rgb($v, $v, $v);
        $bg = Color::rgb($v, $v, $v);

        // Identical colors: lc should early-return 0.0; importantly it passes through relLumi and srgbToLinear
        $this->assertSame(0.0, ApcaContrast::lc($fg, $bg));

        // Nudge just above threshold to exercise the other srgbToLinear branch within same test run
        $fg2 = Color::rgb($v + 1e-4, $v + 1e-4, $v + 1e-4);
        $lc = ApcaContrast::lc($fg2, $bg);
        $this->assertIsFloat($lc);
    }

    public function testRelLumiChannelWeightsAffectContrastOrdering(): void
    {
        // Use values below 0.04045 to stay in the linear branch: linear = v/12.92
        // Base gray background
        $bg = Color::rgb(0.10, 0.10, 0.10);

        // Foregrounds differ by +0.01 in a single channel
        $fgR = Color::rgb(0.11, 0.10, 0.10);
        $fgG = Color::rgb(0.10, 0.11, 0.10);
        $fgB = Color::rgb(0.10, 0.10, 0.11);

        $lcR = ApcaContrast::lc($fgR, $bg);
        $lcG = ApcaContrast::lc($fgG, $bg);
        $lcB = ApcaContrast::lc($fgB, $bg);

        // Since only foreground is brighter (reverse polarity), results are <= 0; compare magnitudes
        $this->assertLessThan(0.0, $lcR);
        $this->assertLessThan(0.0, $lcG);
        $this->assertLessThan(0.0, $lcB);

        // Green should contribute the most (0.7152), then Red (0.2126), then Blue (0.0722)
        $this->assertGreaterThan(abs($lcR), abs($lcG));
        $this->assertGreaterThan(abs($lcB), abs($lcR));
    }
}
