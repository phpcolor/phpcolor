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
use PhpColor\Color\ColorInterface;
use PhpColor\Color\Gradient\ConicGradient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConicGradient::class)]
final class ConicGradientTest extends TestCase
{
    public function testAddStop(): void
    {
        $gradient = new ConicGradient();
        $color = Color::parse('#ff0000');

        $newGradient = $gradient->addStop($color, 0.5);

        $this->assertNotSame($gradient, $newGradient);
        $this->assertEmpty($gradient->getStops());
        $this->assertCount(1, $newGradient->getStops());
    }

    public function testColorWheel(): void
    {
        $gradient = ConicGradient::colorWheel();

        $this->assertSame(0.0, $gradient->getAngle());
    }

    public function testConstructor(): void
    {
        $gradient = new ConicGradient();

        $this->assertSame('conic', $gradient->getType());
        $this->assertSame(0.0, $gradient->getAngle());
        $this->assertSame('center', $gradient->getPosition());
    }

    public function testInterpolate(): void
    {
        $gradient = new ConicGradient();
        $gradient = $gradient
            ->addStop(Color::parse('#ff0000'), 0.0)
            ->addStop(Color::parse('#0000ff'), 1.0);

        $color = $gradient->interpolate(0.5);
        $this->assertInstanceOf(ColorInterface::class, $color);
    }

    public function testToCssDefault(): void
    {
        $gradient = new ConicGradient();
        $gradient = $gradient
            ->addStop(Color::parse('#ff0000'), 0.0)
            ->addStop(Color::parse('#0000ff'), 1.0);

        $css = $gradient->toCss();

        $this->assertStringStartsWith('conic-gradient(', $css);
        $this->assertStringContainsString('rgb(255 0 0)', $css);
        $this->assertStringContainsString('rgb(0 0 255)', $css);
    }

    public function testToCssEmptyReturnsBareFunction(): void
    {
        $gradient = new ConicGradient();
        $css = $gradient->toCss();
        $this->assertSame('conic-gradient()', $css);
    }

    public function testToCssWithAngle(): void
    {
        $gradient = new ConicGradient(90.0);
        $gradient = $gradient->addStop(Color::parse('#ff0000'), 0.0);

        $css = $gradient->toCss();

        $this->assertStringContainsString('from 90deg', $css);
    }

    public function testToCssWithAngleAndPosition(): void
    {
        $gradient = new ConicGradient(45.0);
        $gradient = $gradient->withPosition('top')
            ->addStop(Color::parse('#ff0000'), 0.0);

        $css = $gradient->toCss();

        $this->assertStringContainsString('from 45deg', $css);
        $this->assertStringContainsString('at top', $css);
    }

    public function testToCssWithPosition(): void
    {
        $gradient = new ConicGradient();
        $gradient = $gradient->withPosition('bottom left')
            ->addStop(Color::parse('#ff0000'), 0.0);

        $css = $gradient->toCss();

        $this->assertStringContainsString('at bottom left', $css);
    }

    public function testWithAngle(): void
    {
        $gradient = new ConicGradient();
        $newGradient = $gradient->withAngle(45.0);

        $this->assertNotSame($gradient, $newGradient);
        $this->assertSame(0.0, $gradient->getAngle());
        $this->assertSame(45.0, $newGradient->getAngle());
    }

    public function testWithPosition(): void
    {
        $gradient = new ConicGradient();
        $newGradient = $gradient->withPosition('top right');

        $this->assertNotSame($gradient, $newGradient);
        $this->assertSame('center', $gradient->getPosition());
        $this->assertSame('top right', $newGradient->getPosition());
    }
}
