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

namespace PhpColor\Color\Tests\Gradient;

use PhpColor\Color\Color;
use PhpColor\Color\Gradient\GradientStop;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GradientStop::class)]
final class GradientStopTest extends TestCase
{
    public function testConstructor(): void
    {
        $color = new SrgbColor(1.0, 0.0, 0.0);
        $stop = new GradientStop($color, 0.5);

        $this->assertSame($color, $stop->color);
        $this->assertSame(0.5, $stop->position);
    }

    public function testNormalized(): void
    {
        $color = new SrgbColor(0.0, 1.0, 0.0);

        // Test clamping upper bound
        $stop1 = GradientStop::normalized($color, 1.5);
        $this->assertSame(1.0, $stop1->position);

        // Test clamping lower bound
        $stop2 = GradientStop::normalized($color, -0.5);
        $this->assertSame(0.0, $stop2->position);

        // Test normal value
        $stop3 = GradientStop::normalized($color, 0.3);
        $this->assertSame(0.3, $stop3->position);
    }

    public function testToCss(): void
    {
        $color = Color::parse('#ff0000');
        $stop = new GradientStop($color, 0.25);

        $css = $stop->toCss();

        $this->assertStringContainsString('rgb(255 0 0)', $css);
        $this->assertStringContainsString('25%', $css);
    }

    public function testToCssFormatsPosition(): void
    {
        $color = Color::parse('#000000');

        // Test 0%
        $stop1 = new GradientStop($color, 0.0);
        $this->assertStringContainsString('0%', $stop1->toCss());

        // Test 100%
        $stop2 = new GradientStop($color, 1.0);
        $this->assertStringContainsString('100%', $stop2->toCss());

        // Test decimal
        $stop3 = new GradientStop($color, 0.333);
        $css = $stop3->toCss();
        $this->assertStringContainsString('%', $css);
    }

    public function testToCssWithColorSpace(): void
    {
        $color = Color::parse('#ff0000');
        $stop = new GradientStop($color, 0.5);

        $css = $stop->toCss('hsl');

        $this->assertStringStartsWith('hsl(', $css);
        $this->assertStringContainsString('50%', $css);
    }
}
