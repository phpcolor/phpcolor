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
use PhpColor\Color\DisplayP3Color;
use PhpColor\Color\Exception\InvalidArgumentException;
use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\OklchColor;
use PhpColor\Color\SrgbColor;
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

    public function testGamutDeltaZeroForSrgbColors(): void
    {
        $c = Color::parse('#336699');
        $this->assertSame(0.0, Gamut::gamutDelta($c, 'srgb'));
    }

    public function testClipReturnsSrgbAndPreservesAlpha(): void
    {
        $clipped = Gamut::clip(new SrgbColor(1.2, -0.1, 0.5, 0.4), 'srgb');

        $this->assertSame(['r' => 1.0, 'g' => 0.0, 'b' => 0.5], $clipped->getChannels());
        $this->assertSame(0.4, $clipped->getAlpha());
    }

    public function testClipAcceptsSrgbAlias(): void
    {
        $clipped = Gamut::clip(new SrgbColor(1.2, 0.5, -0.1), 'rgb');

        $this->assertSame(['r' => 1.0, 'g' => 0.5, 'b' => 0.0], $clipped->getChannels());
    }

    public function testMapReturnsAnInGamutSrgbColorAndPreservesAlpha(): void
    {
        $mapped = Gamut::map(new OklchColor(0.7, 0.4, 30.0, 0.42), 'srgb');

        $this->assertInstanceOf(SrgbColor::class, $mapped);
        $this->assertTrue(Gamut::isInGamut($mapped, 'srgb', 0.0));
        $this->assertSame(0.42, $mapped->getAlpha());
    }

    public function testMapPreservesLightnessAndHueBetterThanClipping(): void
    {
        $origin = new OklchColor(0.7, 0.4, 30.0);
        $clipped = OklchColor::from(Gamut::clip($origin, 'srgb'));
        $mapped = OklchColor::from(Gamut::map($origin, 'srgb'));

        $this->assertLessThan(abs($clipped->l - $origin->l), abs($mapped->l - $origin->l));
        $this->assertLessThan(abs($clipped->h - $origin->h), abs($mapped->h - $origin->h));
    }

    public function testMapLeavesInGamutSrgbColorUnchanged(): void
    {
        $color = new SrgbColor(0.2, 0.4, 0.6, 0.8);
        $mapped = Gamut::map($color, 'srgb');

        $this->assertEqualsWithDelta($color->r, $mapped->r, 1e-12);
        $this->assertEqualsWithDelta($color->g, $mapped->g, 1e-12);
        $this->assertEqualsWithDelta($color->b, $mapped->b, 1e-12);
        $this->assertSame($color->a, $mapped->a);
    }

    public function testMapHandlesExtremeLightness(): void
    {
        $black = Gamut::map(new OklchColor(0.0, 0.4, 30.0, 0.3), 'srgb');
        $white = Gamut::map(new OklchColor(1.0, 0.4, 30.0, 0.7), 'srgb');

        $this->assertSame(['r' => 0.0, 'g' => 0.0, 'b' => 0.0], $black->getChannels());
        $this->assertSame(0.3, $black->getAlpha());
        $this->assertSame(['r' => 1.0, 'g' => 1.0, 'b' => 1.0], $white->getChannels());
        $this->assertSame(0.7, $white->getAlpha());
    }

    public function testMapAndClipProduceDifferentSrgbResultsForDisplayP3(): void
    {
        $color = new DisplayP3Color(0.0, 1.0, 0.0, 0.4);

        $clipped = Gamut::clip($color, 'srgb');
        $mapped = Gamut::map($color, 'srgb');

        $this->assertInstanceOf(SrgbColor::class, $clipped);
        $this->assertInstanceOf(SrgbColor::class, $mapped);
        $this->assertNotSame($clipped->getChannels(), $mapped->getChannels());
        $this->assertTrue(Gamut::isInGamut($mapped, 'srgb', 0.0));
        $this->assertSame(0.4, $mapped->getAlpha());
    }

    public function testOperationsRejectUnsupportedTargetGamut(): void
    {
        $this->expectException(InvalidColorException::class);
        $this->expectExceptionMessage('only sRGB is currently supported');

        Gamut::map(Color::red(), 'display-p3');
    }

    public function testOperationsRejectNonFiniteChannels(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Gamut operations require finite color channels and alpha.');

        Gamut::map(new SrgbColor(\NAN, 0.0, 0.0), 'srgb');
    }
}
