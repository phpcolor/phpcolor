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

namespace PhpColor\Color\Tests\Colorimetry;

use PhpColor\Color\Color;
use PhpColor\Color\Colorimetry\Gamut;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Gamut::class)]
final class GamutTest extends TestCase
{
    public function testIsInGamutSrgbReturnsTrueForBasicColors(): void
    {
        $red = Color::red();
        $this->assertTrue(Gamut::isInGamut($red, 'srgb'));

        $white = Color::white();
        $this->assertTrue(Gamut::isInGamut($white, 'srgb'));
    }

    public function testIsInGamutFallsBackFromNonRgbSpace(): void
    {
        // Stub returns no r/g/b for 'oklch', triggering fallback to sRGB
        $stub = new StubColor(['r' => 0.2, 'g' => 0.3, 'b' => 0.4]);
        $this->assertTrue(Gamut::isInGamut($stub, 'oklch'));
    }

    public function testIsInGamutCanReturnFalseWithStrictEpsilon(): void
    {
        // Using a negative epsilon should force the boundary check to fail
        $stub = new StubColor(['r' => 0.0, 'g' => 0.0, 'b' => 0.0]);
        $this->assertFalse(Gamut::isInGamut($stub, 'srgb', -1e-3));
    }

    public function testGamutDeltaDetectsNegativeAndOverflowChannels(): void
    {
        // Create a stub with out-of-range channels to exercise delta branches
        $stub = new StubColor(['r' => -0.1, 'g' => 1.2, 'b' => 0.5]);
        $delta = Gamut::gamutDelta($stub, 'stub');
        // Max(|-0.1|, |1.2-1|, |0|) = 0.2
        $this->assertEqualsWithDelta(0.2, $delta, 1e-9);
    }

    // applyPolicy removed; diagnostics-only (isInGamut, gamutDelta)

    public function testGamutDeltaZeroForSrgbColors(): void
    {
        $c = Color::parse('#336699');
        $this->assertSame(0.0, Gamut::gamutDelta($c, 'srgb'));
    }

    // no more applyPolicy tests
}
