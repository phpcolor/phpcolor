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
use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\Gradient\ConicGradient;
use PhpColor\Color\Gradient\GradientStop;
use PhpColor\Color\Gradient\InterpolationSpace;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $this->assertCount(13, $gradient->getStops());
    }

    public function testColorWheelClosesOnTheStartingHue(): void
    {
        $stops = ConicGradient::colorWheel(6)->getStops();
        $last = $stops[\count($stops) - 1];

        $this->assertSame(0.0, $stops[0]->position);
        $this->assertSame(1.0, $last->position);
        $this->assertEqualsWithDelta($stops[0]->color->to('oklch')->h, $last->color->to('oklch')->h, 1e-9);
    }

    #[DataProvider('provideColorWheelSteps')]
    public function testColorWheelStopCount(int $steps): void
    {
        $this->assertCount($steps + 1, ConicGradient::colorWheel($steps)->getStops());
    }

    public static function provideColorWheelSteps(): iterable
    {
        yield 'minimum' => [2];
        yield 'quarters' => [4];
        yield 'default' => [12];
        yield 'fine grained' => [36];
    }

    public function testColorWheelToCss(): void
    {
        $this->assertSame(
            'conic-gradient(in oklab, oklch(0.7 0.15 0) 0%, oklch(0.7 0.15 120) 33.33%, oklch(0.7 0.15 240) 66.67%, oklch(0.7 0.15 0) 100%)',
            ConicGradient::colorWheel(3)->toCss()
        );
    }

    #[DataProvider('provideInvalidColorWheelSteps')]
    public function testColorWheelRejectsFewerThanTwoSteps(int $steps): void
    {
        $this->expectException(InvalidColorException::class);
        $this->expectExceptionMessage(\sprintf('A color wheel requires at least 2 steps, %d given.', $steps));

        ConicGradient::colorWheel($steps);
    }

    public static function provideInvalidColorWheelSteps(): iterable
    {
        yield 'single step' => [1];
        yield 'zero steps' => [0];
        yield 'negative steps' => [-3];
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

    public function testToCssWithSrgbInterpolationSpace(): void
    {
        $gradient = new ConicGradient(0.0, 'center', [
            new GradientStop(Color::parse('#ff0000'), 0.0),
            new GradientStop(Color::parse('#0000ff'), 1.0),
        ], InterpolationSpace::Srgb);

        $this->assertSame('conic-gradient(in srgb, rgb(255 0 0) 0%, rgb(0 0 255) 100%)', $gradient->toCss());
    }

    public function testToCssWithSrgbLinearInterpolationSpace(): void
    {
        $gradient = new ConicGradient(0.0, 'center', [
            new GradientStop(Color::parse('#ff0000'), 0.0),
            new GradientStop(Color::parse('#0000ff'), 1.0),
        ], InterpolationSpace::SrgbLinear);

        $this->assertSame('conic-gradient(in srgb-linear, rgb(255 0 0) 0%, rgb(0 0 255) 100%)', $gradient->toCss());
    }

    public function testToCssThrowsWithoutStops(): void
    {
        $gradient = new ConicGradient();

        $this->expectException(InvalidColorException::class);
        $this->expectExceptionMessage('A conic gradient requires at least 2 color stops, 0 given.');

        $gradient->toCss();
    }

    public function testToCssThrowsWithSingleStop(): void
    {
        $gradient = (new ConicGradient())->addStop(Color::parse('#ff0000'), 0.0);

        $this->expectException(InvalidColorException::class);
        $this->expectExceptionMessage('A conic gradient requires at least 2 color stops, 1 given.');

        $gradient->toCss();
    }

    public function testToCssWithAngle(): void
    {
        $gradient = new ConicGradient(90.0);
        $gradient = $gradient
            ->addStop(Color::parse('#ff0000'), 0.0)
            ->addStop(Color::parse('#0000ff'), 1.0);

        $css = $gradient->toCss();

        $this->assertStringContainsString('from 90deg', $css);
    }

    public function testToCssWithAngleAndPosition(): void
    {
        $gradient = new ConicGradient(45.0);
        $gradient = $gradient->withPosition('top')
            ->addStop(Color::parse('#ff0000'), 0.0)
            ->addStop(Color::parse('#0000ff'), 1.0);

        $css = $gradient->toCss();

        $this->assertStringContainsString('from 45deg', $css);
        $this->assertStringContainsString('at top', $css);
    }

    public function testToCssWithOklabInterpolationSpace(): void
    {
        $gradient = (new ConicGradient(45.0))
            ->withPosition('top')
            ->addStop(Color::parse('#ff0000'), 0.0)
            ->addStop(Color::parse('#0000ff'), 1.0);

        $this->assertSame('conic-gradient(from 45deg at top in oklab, rgb(255 0 0) 0%, rgb(0 0 255) 100%)', $gradient->toCss());
    }

    public function testToCssWithPosition(): void
    {
        $gradient = new ConicGradient();
        $gradient = $gradient->withPosition('bottom left')
            ->addStop(Color::parse('#ff0000'), 0.0)
            ->addStop(Color::parse('#0000ff'), 1.0);

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
