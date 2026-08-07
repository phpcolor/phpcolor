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
use PhpColor\Color\Gradient\GradientStop;
use PhpColor\Color\Gradient\InterpolationSpace;
use PhpColor\Color\Gradient\LinearGradient;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(LinearGradient::class)]
final class LinearGradientTest extends TestCase
{
    public function testAddStop(): void
    {
        $gradient = new LinearGradient();
        $color = new SrgbColor(1.0, 0.0, 0.0);

        $newGradient = $gradient->addStop($color, 0.5);

        $this->assertNotSame($gradient, $newGradient);
        $this->assertEmpty($gradient->getStops());
        $this->assertCount(1, $newGradient->getStops());
    }

    public function testConstructor(): void
    {
        $gradient = new LinearGradient(90.0);

        $this->assertSame('linear', $gradient->getType());
        $this->assertSame(90.0, $gradient->getAngle());
        $this->assertEmpty($gradient->getStops());
    }

    #[DataProvider('provideDirections')]
    public function testFromDirection(string $dir, float $expectedAngle): void
    {
        $g = LinearGradient::fromDirection($dir);
        $this->assertSame($expectedAngle, $g->getAngle());
    }

    public static function provideDirections(): iterable
    {
        yield 'to top' => ['to top', 0.0];
        yield 'to right' => ['to right', 90.0];
        yield 'to bottom' => ['to bottom', 180.0];
        yield 'to left' => ['to left', 270.0];
    }

    #[DataProvider('provideCornerDirections')]
    public function testFromDirectionCorners(string $dir, float $expectedAngle): void
    {
        $g = LinearGradient::fromDirection($dir);
        $this->assertSame($expectedAngle, $g->getAngle());
    }

    public static function provideCornerDirections(): iterable
    {
        yield 'to top right' => ['to top right', 45.0];
        yield 'to bottom right' => ['to bottom right', 135.0];
        yield 'to bottom left' => ['to bottom left', 225.0];
        yield 'to top left' => ['to top left', 315.0];
    }

    #[DataProvider('provideDefaultDirectionInputs')]
    public function testFromDirectionDefaultsToBottom(string $dir): void
    {
        $g = LinearGradient::fromDirection($dir);
        $this->assertSame(180.0, $g->getAngle());
    }

    public static function provideDefaultDirectionInputs(): iterable
    {
        yield 'unknown word' => ['diagonal'];
        yield 'empty string' => [''];
        yield 'whitespace only' => ['   '];
        yield 'random phrase' => ['towards nowhere'];
    }

    public function testInterpolate(): void
    {
        $gradient = new LinearGradient();
        $gradient = $gradient
            ->addStop(Color::parse('#ff0000'), 0.0)
            ->addStop(Color::parse('#0000ff'), 1.0);

        // Interpolate at 0% (should be red)
        $color0 = $gradient->interpolate(0.0);
        $rgb0 = $color0->toSrgb();
        $this->assertEqualsWithDelta(1.0, $rgb0->r, 0.1);
        $this->assertEqualsWithDelta(0.0, $rgb0->g, 0.1);
        $this->assertEqualsWithDelta(0.0, $rgb0->b, 0.1);

        // Interpolate at 100% (should be blue)
        $color1 = $gradient->interpolate(1.0);
        $rgb1 = $color1->toSrgb();
        $this->assertEqualsWithDelta(0.0, $rgb1->r, 0.1);
        $this->assertEqualsWithDelta(0.0, $rgb1->g, 0.1);
        $this->assertEqualsWithDelta(1.0, $rgb1->b, 0.1);

        // Interpolate at 50% (should be a mix)
        $color05 = $gradient->interpolate(0.5);
        $this->assertInstanceOf(ColorInterface::class, $color05);
    }

    #[DataProvider('interpolationSpaceProvider')]
    public function testInterpolateAgreesWithMix(InterpolationSpace $space): void
    {
        $red = Color::parse('#ff0000');
        $blue = Color::parse('#0000ff');

        $gradient = new LinearGradient(90.0, [
            new GradientStop($red, 0.0),
            new GradientStop($blue, 1.0),
        ], $space);

        $this->assertSame(
            Color::mix($red, $blue, 0.5, $space->value)->toSrgb()->toHex(),
            $gradient->interpolate(0.5)->toSrgb()->toHex(),
        );
    }

    /**
     * @return iterable<string, array{InterpolationSpace}>
     */
    public static function interpolationSpaceProvider(): iterable
    {
        yield 'oklab' => [InterpolationSpace::Oklab];
        yield 'srgb' => [InterpolationSpace::Srgb];
        yield 'srgb-linear' => [InterpolationSpace::SrgbLinear];
    }

    public function testInterpolateEmptyGradient(): void
    {
        $gradient = new LinearGradient();
        $color = $gradient->interpolate(0.5);

        // Should return black as fallback
        $rgb = $color->toSrgb();
        $this->assertSame(0.0, $rgb->r);
        $this->assertSame(0.0, $rgb->g);
        $this->assertSame(0.0, $rgb->b);
    }

    public function testInterpolateSingleStop(): void
    {
        $gradient = new LinearGradient();
        $gradient = $gradient->addStop(Color::parse('#ff0000'), 0.5);

        $color = $gradient->interpolate(0.8);
        $rgb = $color->toSrgb();

        // Should return the single color
        $this->assertEqualsWithDelta(1.0, $rgb->r, 0.01);
    }

    public function testToCssWithSrgbInterpolationSpace(): void
    {
        $gradient = new LinearGradient(90.0, [
            new GradientStop(Color::parse('#ff0000'), 0.0),
            new GradientStop(Color::parse('#0000ff'), 1.0),
        ], InterpolationSpace::Srgb);

        $this->assertSame('linear-gradient(90deg in srgb, rgb(255 0 0) 0%, rgb(0 0 255) 100%)', $gradient->toCss());
    }

    public function testToCssWithSrgbLinearInterpolationSpace(): void
    {
        $gradient = new LinearGradient(90.0, [
            new GradientStop(Color::parse('#ff0000'), 0.0),
            new GradientStop(Color::parse('#0000ff'), 1.0),
        ], InterpolationSpace::SrgbLinear);

        $this->assertSame('linear-gradient(90deg in srgb-linear, rgb(255 0 0) 0%, rgb(0 0 255) 100%)', $gradient->toCss());
    }

    public function testToCssThrowsWithoutStops(): void
    {
        $gradient = new LinearGradient(90.0);

        $this->expectException(InvalidColorException::class);
        $this->expectExceptionMessage('A linear gradient requires at least 2 color stops, 0 given.');

        $gradient->toCss();
    }

    public function testToCssThrowsWithSingleStop(): void
    {
        $gradient = (new LinearGradient())->addStop(Color::parse('#ff0000'), 0.0);

        $this->expectException(InvalidColorException::class);
        $this->expectExceptionMessage('A linear gradient requires at least 2 color stops, 1 given.');

        $gradient->toCss();
    }

    public function testToCssWithOklabInterpolationSpace(): void
    {
        $gradient = (new LinearGradient())
            ->addStop(Color::parse('#ff0000'), 0.0)
            ->addStop(Color::parse('#0000ff'), 1.0);

        $this->assertSame('linear-gradient(180deg in oklab, rgb(255 0 0) 0%, rgb(0 0 255) 100%)', $gradient->toCss());
    }

    public function testToCssWithStops(): void
    {
        $gradient = new LinearGradient(180.0);
        $gradient = $gradient
            ->addStop(Color::parse('#ff0000'), 0.0)
            ->addStop(Color::parse('#0000ff'), 1.0);

        $css = $gradient->toCss();

        $this->assertStringStartsWith('linear-gradient(', $css);
        $this->assertStringContainsString('180deg', $css);
        $this->assertStringContainsString('rgb(255 0 0)', $css);
        $this->assertStringContainsString('rgb(0 0 255)', $css);
    }

    public function testWithAngle(): void
    {
        $gradient = new LinearGradient(0.0);
        $newGradient = $gradient->withAngle(180.0);

        $this->assertNotSame($gradient, $newGradient);
        $this->assertSame(0.0, $gradient->getAngle());
        $this->assertSame(180.0, $newGradient->getAngle());
    }
}
