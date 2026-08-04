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
use PhpColor\Color\Contrast\ContrastSolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContrastSolver::class)]
final class ContrastSolverTest extends TestCase
{
    public function testRequiredAlphaSolvesAA(): void
    {
        $fg = Color::parse('#3b82f6')->withAlpha(1.0); // blue
        $bg = Color::white();

        $alpha = ContrastSolver::requiredAlpha($fg, $bg, 4.5);
        $this->assertGreaterThanOrEqual(0.0, $alpha);
        $this->assertLessThanOrEqual(1.0, $alpha);

        $ratio = ContrastSolver::compositedRatio($fg->withAlpha($alpha), $bg);
        if ($alpha < 1.0) {
            $this->assertGreaterThanOrEqual(4.5, $ratio);
        } else {
            // If not achievable, alpha will be 1.0 and ratio is best effort
            $maxRatio = ContrastSolver::compositedRatio($fg->withAlpha(1.0), $bg);
            $this->assertEquals($maxRatio, $ratio);
        }
    }

    public function testCompositeHandlesZeroAlpha(): void
    {
        $fg = Color::parse('rgba(0 0 0 / 0)');
        $bg = Color::parse('rgba(255 255 255 / 0)');
        $comp = ContrastSolver::composite($fg, $bg);
        $this->assertSame(0.0, $comp->a);
        $this->assertSame(0.0, $comp->r);
        $this->assertSame(0.0, $comp->g);
        $this->assertSame(0.0, $comp->b);
    }

    public function testRequiredAlphaQuickChecksAndBinarySearch(): void
    {
        $bg = Color::white();
        // target 1.0 should return 0.0 immediately (alpha=0 uses bg over bg => ratio 1)
        $ret = ContrastSolver::requiredAlpha(Color::black(), $bg, 1.0);
        $this->assertSame(0.0, $ret);

        // For black over white with AA=4.5, loop executes and returns 0<alpha<1
        $alpha = ContrastSolver::requiredAlpha(Color::black(), $bg, 4.5);
        $this->assertGreaterThan(0.0, $alpha);
        $this->assertLessThan(1.0, $alpha);
        $ratio = ContrastSolver::compositedRatio(Color::black()->withAlpha($alpha), $bg);
        $this->assertGreaterThanOrEqual(4.5, $ratio);
    }

    public function testBestOn(): void
    {
        $bg = Color::parse('#111111');
        $best = ContrastSolver::bestOn($bg, [Color::white(), Color::black()]);
        $this->assertTrue(ColorContrast::calculate($best, $bg) >= 4.5);

        $bg2 = Color::parse('#eeeeee');
        $best2 = ContrastSolver::bestOn($bg2, [Color::white(), Color::black()]);
        $this->assertTrue(ColorContrast::calculate($best2, $bg2) >= 4.5);
    }

    public function testAdjustLightnessToContrast(): void
    {
        $fg = Color::parse('oklch(0.6 0.1 40)');
        $bg = Color::parse('#fafafa');

        $adj = ContrastSolver::adjustLightnessToContrast($fg, $bg, 4.5);
        $this->assertGreaterThanOrEqual(4.5, ColorContrast::calculate($adj, $bg));
        // Should preserve hue approximately
        $this->assertEqualsWithDelta($fg->getHue(), $adj->getHue(), 1.0);
    }

    public function testRequiredAlphaNotAchievableQuickCheck(): void
    {
        // Very low contrast pair with unrealistic target ensures the quick-check 1.0 path is taken
        $fg = Color::parse('#aaaaaa');
        $bg = Color::parse('#bbbbbb');

        $alpha = ContrastSolver::requiredAlpha($fg, $bg, 21.0); // beyond WCAG scale
        $this->assertSame(1.0, $alpha);

        $ratioMax = ContrastSolver::compositedRatio($fg->withAlpha(1.0), $bg);
        $this->assertEquals($ratioMax, ContrastSolver::compositedRatio($fg->withAlpha($alpha), $bg));
    }

    public function testBestOnWithNoCandidatesAndMinRatioReturnsBlack(): void
    {
        $bg = Color::white();
        $best = ContrastSolver::bestOn($bg, []);
        $this->assertEquals(Color::black(), $best);
    }

    public function testRequiredAlphaBinarySearchIsUsed(): void
    {
        // A color that has enough contrast at full alpha, but not at zero alpha,
        // forcing the use of the binary search to find the correct alpha.
        $fg = Color::parse('#767676'); // Gray
        $bg = Color::white();
        $targetRatio = 4.5;

        // Contrast at alpha=1.0 is > 4.5, so it is achievable.
        $this->assertGreaterThan($targetRatio, ColorContrast::calculate($fg, $bg));

        // Contrast at alpha=0.0 is 1.0, so it is not achievable.
        $this->assertLessThan($targetRatio, ColorContrast::calculate($bg, $bg));

        // Solve for the required alpha
        $alpha = ContrastSolver::requiredAlpha($fg, $bg, $targetRatio);

        // The binary search should find an alpha between 0 and 1.
        $this->assertGreaterThan(0.0, $alpha);
        $this->assertLessThan(1.0, $alpha);

        // The resulting color should meet the target contrast ratio.
        $ratio = ContrastSolver::compositedRatio($fg->withAlpha($alpha), $bg);
        $this->assertGreaterThanOrEqual($targetRatio, $ratio);

        // And it should be very close to the target ratio
        $this->assertEqualsWithDelta($targetRatio, $ratio, 0.01);
    }

    public function testBestOnDefaults(): void
    {
        $bg = Color::white();
        // Default minRatio is null, so it should return the best candidate even if contrast is low
        $badCandidate = Color::parse('#f0f0f0'); // Very low contrast with white
        $best = ContrastSolver::bestOn($bg, [$badCandidate]);

        $this->assertSame($badCandidate, $best);
    }

    public function testBestOnReturnsBlackIfCandidatesEmptyAndDefaultRatio(): void
    {
        $bg = Color::white();
        $best = ContrastSolver::bestOn($bg, []);
        $this->assertEquals(Color::black(), $best);
    }

    public function testAdjustLightnessToContrastUncoveredPath(): void
    {
        $fg = Color::parse('oklch(0.5 0.05 10)'); // Dark, slightly chromatic
        $bg = Color::parse('oklch(0.55 0.05 10)'); // Slightly lighter, low contrast with fg

        $adj = ContrastSolver::adjustLightnessToContrast($fg, $bg, 10.0); // High target ratio
        // When a high target ratio is unreachable, the method should still return the best possible color,
        // and its hue should be approximately preserved.
        $this->assertLessThan(10.0, ColorContrast::calculate($adj, $bg)); // Assert that target was NOT met
        $this->assertEqualsWithDelta($fg->getHue(), $adj->getHue(), 1.0); // Hue should be preserved
    }

    public function testAdjustLightnessToContrastSurvivesHexRoundTrip(): void
    {
        // Regression: the full-precision object used to reach
        // 4.50001473216499637 while its serialized #458cff reparsed to
        // 4.49190475486645280, below target.
        $fg = Color::parse('#3b82f6');
        $bg = Color::parse('#1e293b');

        $adjusted = ContrastSolver::adjustLightnessToContrast($fg, $bg, 4.5);
        $reparsed = Color::parse($adjusted->toHex());

        $this->assertGreaterThanOrEqual(4.5, ColorContrast::calculate($reparsed, $bg));
    }

    public function testRequiredAlphaSurvivesHexRoundTrip(): void
    {
        // Regression vector found while auditing this bug: alpha 0.7584023476
        // reaches 4.50000038 at full precision but rounds to 0.756863
        // (4.4873307076) at the nearest 8-bit alpha byte.
        $fg = Color::parse('#f59e0b');
        $bg = Color::parse('#1e293b');

        $alpha = ContrastSolver::requiredAlpha($fg, $bg, 4.5);
        $quantizedAlpha = round($alpha * 255.0) / 255.0;

        $ratio = ContrastSolver::compositedRatio($fg->withAlpha($quantizedAlpha), $bg);
        $this->assertGreaterThanOrEqual(4.5, $ratio);
    }

    public function testAdjustLightnessToContrastCanTargetFullPrecision(): void
    {
        // Precision 0 disables quantization, preserving the previous behavior.
        $fg = Color::parse('#3b82f6');
        $bg = Color::parse('#1e293b');

        $adjusted = ContrastSolver::adjustLightnessToContrast($fg, $bg, 4.5, 0);

        $this->assertGreaterThanOrEqual(4.5, ColorContrast::calculate($adjusted, $bg));
    }

    public function testRequiredAlphaCanTargetFullPrecision(): void
    {
        $fg = Color::parse('#f59e0b');
        $bg = Color::parse('#1e293b');

        $alpha = ContrastSolver::requiredAlpha($fg, $bg, 4.5, 0);

        $this->assertGreaterThanOrEqual(4.5, ContrastSolver::compositedRatio($fg->withAlpha($alpha), $bg));
    }
}
