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
use PhpColor\Color\Gradient\RadialGradient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RadialGradient::class)]
final class RadialGradientTest extends TestCase
{
    public function testAddStop(): void
    {
        $gradient = new RadialGradient();
        $color = Color::parse('#ff0000');

        $newGradient = $gradient->addStop($color, 0.5);

        $this->assertNotSame($gradient, $newGradient);
        $this->assertEmpty($gradient->getStops());
        $this->assertCount(1, $newGradient->getStops());
    }

    public function testCircle(): void
    {
        $gradient = RadialGradient::circle();

        $this->assertSame('circle', $gradient->getShape());
    }

    public function testConstructor(): void
    {
        $gradient = new RadialGradient();

        $this->assertSame('radial', $gradient->getType());
        $this->assertSame('ellipse', $gradient->getShape());
        $this->assertSame('farthest-corner', $gradient->getSize());
        $this->assertSame('center', $gradient->getPosition());
    }

    public function testEllipse(): void
    {
        $gradient = RadialGradient::ellipse();

        $this->assertSame('ellipse', $gradient->getShape());
    }

    public function testInterpolate(): void
    {
        $gradient = new RadialGradient();
        $gradient = $gradient
            ->addStop(Color::parse('#ff0000'), 0.0)
            ->addStop(Color::parse('#0000ff'), 1.0);

        $color = $gradient->interpolate(0.5);
        $this->assertInstanceOf(ColorInterface::class, $color);
    }

    public function testToCssDefault(): void
    {
        $gradient = new RadialGradient();
        $gradient = $gradient
            ->addStop(Color::parse('#ff0000'), 0.0)
            ->addStop(Color::parse('#0000ff'), 1.0);

        $css = $gradient->toCss();

        $this->assertStringStartsWith('radial-gradient(', $css);
        $this->assertStringContainsString('rgb(255 0 0)', $css);
        $this->assertStringContainsString('rgb(0 0 255)', $css);
    }

    public function testToCssWithCircle(): void
    {
        $gradient = RadialGradient::circle()
            ->addStop(Color::parse('#ff0000'), 0.0)
            ->addStop(Color::parse('#0000ff'), 1.0);

        $css = $gradient->toCss();

        $this->assertStringContainsString('circle', $css);
    }

    public function testToCssWithPosition(): void
    {
        $gradient = new RadialGradient();
        $gradient = $gradient->withPosition('top left')
            ->addStop(Color::parse('#ff0000'), 0.0);

        $css = $gradient->toCss();

        $this->assertStringContainsString('at top left', $css);
    }

    public function testToCssWithSize(): void
    {
        $gradient = new RadialGradient();
        $gradient = $gradient->withSize('closest-side')
            ->addStop(Color::parse('#ff0000'), 0.0);

        $css = $gradient->toCss();

        $this->assertStringContainsString('closest-side', $css);
    }

    public function testWithPosition(): void
    {
        $gradient = new RadialGradient();
        $newGradient = $gradient->withPosition('top left');

        $this->assertNotSame($gradient, $newGradient);
        $this->assertSame('center', $gradient->getPosition());
        $this->assertSame('top left', $newGradient->getPosition());
    }

    public function testWithShape(): void
    {
        $gradient = new RadialGradient();
        $newGradient = $gradient->withShape('circle');

        $this->assertNotSame($gradient, $newGradient);
        $this->assertSame('ellipse', $gradient->getShape());
        $this->assertSame('circle', $newGradient->getShape());
    }

    public function testWithSize(): void
    {
        $gradient = new RadialGradient();
        $newGradient = $gradient->withSize('closest-side');

        $this->assertNotSame($gradient, $newGradient);
        $this->assertSame('farthest-corner', $gradient->getSize());
        $this->assertSame('closest-side', $newGradient->getSize());
    }
}
