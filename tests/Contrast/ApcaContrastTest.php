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
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Reference vectors are independently derived from the canonical APCA
 * 0.0.98G "G-4g" algorithm (Myndex apca-w3, Beta 0.1.9, 2022-07-03), not
 * from this codebase, so a match proves conformance rather than
 * self-consistency.
 */
#[CoversClass(ApcaContrast::class)]
final class ApcaContrastTest extends TestCase
{
    private const float TOLERANCE = 0.01;

    public function testRegressionVectorGray777OnBlack(): void
    {
        // Canonical APCA 0.0.98G returns approximately -30.56 for this pair;
        // background Y is 0, so this also exercises the black soft clamp.
        $lc = ApcaContrast::lc(Color::parse('#777777'), Color::black());

        $this->assertEqualsWithDelta(-30.561149, $lc, self::TOLERANCE);
    }

    public function testCanonicalDarkTextOnLightBackground(): void
    {
        $lc = ApcaContrast::lc(Color::black(), Color::white());

        $this->assertEqualsWithDelta(106.040673, $lc, self::TOLERANCE);
    }

    public function testCanonicalLightTextOnDarkBackground(): void
    {
        $lc = ApcaContrast::lc(Color::white(), Color::black());

        $this->assertEqualsWithDelta(-107.884733, $lc, self::TOLERANCE);
    }

    public function testReferenceMatrixVector(): void
    {
        $lc = ApcaContrast::lc(Color::parse('#3b82f6'), Color::parse('#1e293b'));

        $this->assertEqualsWithDelta(-34.174740, $lc, self::TOLERANCE);
    }

    public function testPolarityMagnitudeIsNotSymmetric(): void
    {
        // normBG/normTXT (0.56/0.57) differ from revBG/revTXT (0.65/0.62), so
        // reverse polarity (light on dark) is slightly stronger in magnitude
        // than normal polarity for the same extreme pair. A symmetric result
        // would indicate the polarity-specific exponents are missing.
        $normal = ApcaContrast::lc(Color::black(), Color::white());
        $reverse = ApcaContrast::lc(Color::white(), Color::black());

        $this->assertGreaterThan(0.0, $normal);
        $this->assertLessThan(0.0, $reverse);
        $this->assertGreaterThan($normal, abs($reverse));
    }

    public function testZeroForIdenticalColors(): void
    {
        $red = Color::red();

        $this->assertSame(0.0, ApcaContrast::lc($red, $red));
    }

    public function testLowContrastIsClippedToZero(): void
    {
        $lc = ApcaContrast::lc(Color::parse('#808080'), Color::parse('#858585'));

        $this->assertSame(0.0, $lc);
    }

    public function testModerateContrastIsNotClipped(): void
    {
        $lc = ApcaContrast::lc(Color::parse('#707070'), Color::parse('#909090'));

        $this->assertEqualsWithDelta(13.197298, $lc, self::TOLERANCE);
    }

    public function testRelLumiChannelWeightsAffectContrastOrdering(): void
    {
        // A single-channel swing must be full (0 to 1) to clear the
        // low-contrast clip; a subtle per-channel delta is clipped to zero
        // by design and would not exercise the weighting at all.
        $bg = Color::black();

        $lcR = ApcaContrast::lc(Color::rgb(1.0, 0.0, 0.0), $bg);
        $lcG = ApcaContrast::lc(Color::rgb(0.0, 1.0, 0.0), $bg);
        $lcB = ApcaContrast::lc(Color::rgb(0.0, 0.0, 1.0), $bg);

        $this->assertLessThan(0.0, $lcR);
        $this->assertLessThan(0.0, $lcG);
        $this->assertLessThan(0.0, $lcB);

        // Green contributes most (0.7151522), then red (0.2126729), then blue (0.0721750).
        $this->assertGreaterThan(abs($lcR), abs($lcG));
        $this->assertGreaterThan(abs($lcB), abs($lcR));
    }

    public function testOutOfGamutComponentReturnsZero(): void
    {
        $fg = new SrgbColor(1.5, 1.5, 1.5);

        $this->assertSame(0.0, ApcaContrast::lc($fg, Color::black()));
    }

    public function testNegativeComponentReturnsZero(): void
    {
        $fg = new SrgbColor(-0.5, -0.5, -0.5);

        $this->assertSame(0.0, ApcaContrast::lc($fg, Color::white()));
    }

    public function testAlphaIsIgnoredLikeWcagContrast(): void
    {
        $opaque = ApcaContrast::lc(Color::black(), Color::white());
        $transparent = ApcaContrast::lc(Color::black()->withAlpha(0.2), Color::white());

        $this->assertSame($opaque, $transparent);
    }
}
