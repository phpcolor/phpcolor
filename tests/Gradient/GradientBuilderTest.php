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
use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\Gradient\ConicGradient;
use PhpColor\Color\Gradient\Gradient;
use PhpColor\Color\Gradient\GradientBuilder;
use PhpColor\Color\Gradient\GradientStop;
use PhpColor\Color\Gradient\InterpolationSpace;
use PhpColor\Color\Gradient\LinearGradient;
use PhpColor\Color\Gradient\RadialGradient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GradientBuilder::class)]
#[CoversClass(Gradient::class)]
final class GradientBuilderTest extends TestCase
{
    public function testBuiltGradientEmitsInterpolationSpace(): void
    {
        $css = GradientBuilder::linear(90.0)
            ->from('#ff0000')
            ->to('#0000ff')
            ->build()
            ->toCss();

        $this->assertSame('linear-gradient(90deg in oklab, rgb(255 0 0) 0%, rgb(0 0 255) 100%)', $css);
    }

    public function testBuiltGradientEmitsSrgbInterpolationSpace(): void
    {
        $css = GradientBuilder::linear(90.0)
            ->from('#ff0000')
            ->to('#0000ff')
            ->in('srgb')
            ->build()
            ->toCss();

        $this->assertSame('linear-gradient(90deg in srgb, rgb(255 0 0) 0%, rgb(0 0 255) 100%)', $css);
    }

    public function testBuiltGradientEmitsSrgbLinearInterpolationSpace(): void
    {
        $css = GradientBuilder::linear(90.0)
            ->from('#ff0000')
            ->to('#0000ff')
            ->in('srgb-linear')
            ->build()
            ->toCss();

        $this->assertSame('linear-gradient(90deg in srgb-linear, rgb(255 0 0) 0%, rgb(0 0 255) 100%)', $css);
    }

    public function testEmptyBuiltGradientBuildsButFailsToRender(): void
    {
        $gradient = GradientBuilder::linear()->build();

        $this->assertSame([], $gradient->getStops());

        $this->expectException(InvalidColorException::class);
        $this->expectExceptionMessage('A linear gradient requires at least 2 color stops, 0 given.');

        $gradient->toCss();
    }

    public function testSingleStopBuiltGradientFailsToRender(): void
    {
        $gradient = GradientBuilder::radial()->from('#ff0000')->build();

        $this->expectException(InvalidColorException::class);
        $this->expectExceptionMessage('A radial gradient requires at least 2 color stops, 1 given.');

        $gradient->toCss();
    }

    public function testBuilderInterpolationSpace(): void
    {
        $gradient = GradientBuilder::linear()
            ->from('#ff0000')
            ->to('#0000ff')
            ->in('srgb')
            ->build();

        $this->assertInstanceOf(LinearGradient::class, $gradient);
    }

    public function testBuilderInterpolationSpaceEnum(): void
    {
        $gradient = GradientBuilder::linear()
            ->from('#ff0000')
            ->to('#0000ff')
            ->in(InterpolationSpace::Srgb)
            ->build();

        $this->assertInstanceOf(LinearGradient::class, $gradient);
    }

    public function testBuilderPosition(): void
    {
        $gradient = GradientBuilder::radial()
            ->at('top left')
            ->from('#ff0000')
            ->to('#0000ff')
            ->build();

        $this->assertInstanceOf(RadialGradient::class, $gradient);
        $this->assertSame('top left', $gradient->getPosition());
    }

    public function testBuilderRadialShape(): void
    {
        $gradient = GradientBuilder::radial()
            ->shape('circle')
            ->from('#ff0000')
            ->to('#0000ff')
            ->build();

        $this->assertInstanceOf(RadialGradient::class, $gradient);
        $this->assertSame('circle', $gradient->getShape());
    }

    public function testBuilderRadialSize(): void
    {
        $gradient = GradientBuilder::radial()
            ->size('closest-side')
            ->from('#ff0000')
            ->to('#0000ff')
            ->build();

        $this->assertInstanceOf(RadialGradient::class, $gradient);
        $this->assertSame('closest-side', $gradient->getSize());
    }

    public function testBuilderStop(): void
    {
        $gradient = GradientBuilder::linear()
            ->stop('#ff0000', 0.0)
            ->stop('#00ff00', 0.3)
            ->stop('#0000ff', 1.0)
            ->build();

        $stops = $gradient->getStops();
        $this->assertCount(3, $stops);
        $this->assertSame(0.3, $stops[1]->position);
    }

    public function testBuilderStopsAcceptsGradientStop(): void
    {
        $stop = new GradientStop(Color::parse('#ff0000'), 0.25);
        $gradient = GradientBuilder::linear()->stops($stop, '#0000ff')->build();

        $stops = $gradient->getStops();
        $this->assertCount(2, $stops);
        $this->assertEqualsWithDelta(0.25, $stops[0]->position, 1e-12);
        $this->assertEqualsWithDelta(1.0, $stops[1]->position, 1e-12);
    }

    public function testBuilderStopsKeepsExplicitPositionMixedWithPlainColor(): void
    {
        $gradient = GradientBuilder::linear(180.0)
            ->stops('#000000', new GradientStop(Color::parse('#ff0000'), 0.9))
            ->build();

        $stops = $gradient->getStops();
        $this->assertCount(2, $stops);
        $this->assertEqualsWithDelta(0.0, $stops[0]->position, 1e-12);
        $this->assertEqualsWithDelta(0.9, $stops[1]->position, 1e-12);
        $this->assertStringContainsString('rgb(255 0 0) 90%', $gradient->toCss());
    }

    public function testBuilderStopsSpreadsPlainColorBetweenExplicitStops(): void
    {
        $gradient = GradientBuilder::linear()
            ->stops(
                new GradientStop(Color::parse('#ff0000'), 0.2),
                '#000000',
                new GradientStop(Color::parse('#00ff00'), 0.8),
            )
            ->build();

        $stops = $gradient->getStops();
        $this->assertCount(3, $stops);
        $this->assertEqualsWithDelta(0.2, $stops[0]->position, 1e-12);
        $this->assertEqualsWithDelta(0.5, $stops[1]->position, 1e-12);
        $this->assertEqualsWithDelta(0.8, $stops[2]->position, 1e-12);
    }

    public function testBuilderStopsWithOnlyExplicitStopsKeepsEveryPosition(): void
    {
        $gradient = GradientBuilder::linear()
            ->stops(
                new GradientStop(Color::parse('#ff0000'), 0.3),
                new GradientStop(Color::parse('#00ff00'), 0.7),
            )
            ->build();

        $stops = $gradient->getStops();
        $this->assertCount(2, $stops);
        $this->assertEqualsWithDelta(0.3, $stops[0]->position, 1e-12);
        $this->assertEqualsWithDelta(0.7, $stops[1]->position, 1e-12);
    }

    public function testBuilderStopsWithSingleColorStartsAtZero(): void
    {
        $gradient = GradientBuilder::linear()->stops('#ff0000')->build();

        $stops = $gradient->getStops();
        $this->assertCount(1, $stops);
        $this->assertEqualsWithDelta(0.0, $stops[0]->position, 1e-12);
    }

    public function testBuilderStopsSpreadsAfterAlreadyAddedStops(): void
    {
        $gradient = GradientBuilder::linear()->from('#000000')->stops('#888888', '#ffffff')->build();

        $stops = $gradient->getStops();
        $this->assertCount(3, $stops);
        $this->assertEqualsWithDelta(0.0, $stops[0]->position, 1e-12);
        $this->assertEqualsWithDelta(0.5, $stops[1]->position, 1e-12);
        $this->assertEqualsWithDelta(1.0, $stops[2]->position, 1e-12);
    }

    public function testBuilderStopsMatchesFacadeForMixedStops(): void
    {
        $arguments = [
            '#000000',
            new GradientStop(Color::parse('#ff0000'), 0.2),
            '#888888',
            new GradientStop(Color::parse('#00ff00'), 0.8),
            '#ffffff',
        ];

        $built = GradientBuilder::linear(180.0)->stops(...$arguments)->build();
        $direct = Gradient::linear(180.0, ...$arguments);

        $this->assertSame(
            array_column($direct->getStops(), 'position'),
            array_column($built->getStops(), 'position'),
        );
    }

    public function testBuilderStopsMethod(): void
    {
        // Test builder stops() method with auto-distribution
        $gradient = Gradient::linear(45.0)
            ->stops('#ff0000', '#00ff00', '#0000ff')
            ->build();

        $this->assertInstanceOf(LinearGradient::class, $gradient);
        $this->assertCount(3, $gradient->getStops());

        $stops = $gradient->getStops();
        $this->assertSame(0.0, $stops[0]->position);
        $this->assertSame(0.5, $stops[1]->position);
        $this->assertSame(1.0, $stops[2]->position);
    }

    public function testBuilderViaMethod(): void
    {
        $gradient = GradientBuilder::linear()
            ->from('#ff0000')
            ->via('#00ff00')
            ->to('#0000ff')
            ->build();

        $stops = $gradient->getStops();
        $this->assertCount(3, $stops);
        $this->assertSame(0.0, $stops[0]->position);
        $this->assertSame(0.5, $stops[1]->position);
        $this->assertSame(1.0, $stops[2]->position);
    }

    public function testBuilderWithColorObjects(): void
    {
        $red = Color::parse('#ff0000');
        $blue = Color::parse('#0000ff');

        $gradient = GradientBuilder::linear()
            ->from($red)
            ->to($blue)
            ->build();

        $this->assertCount(2, $gradient->getStops());
    }

    public function testCompleteExample(): void
    {
        // Test a complete real-world example
        $gradient = Gradient::linear(135.0)
            ->from('#667eea')
            ->via('#764ba2')
            ->to('#f093fb')
            ->in('oklab')
            ->build();

        $this->assertInstanceOf(LinearGradient::class, $gradient);
        $this->assertCount(3, $gradient->getStops());

        $css = $gradient->toCss();
        $this->assertStringStartsWith('linear-gradient(', $css);
        $this->assertStringContainsString('135deg', $css);
    }

    public function testConicBuilder(): void
    {
        $gradient = GradientBuilder::conic(45.0)
            ->from('#ff0000')
            ->to('#0000ff')
            ->build();

        $this->assertInstanceOf(ConicGradient::class, $gradient);
        $this->assertSame(45.0, $gradient->getAngle());
        $this->assertCount(2, $gradient->getStops());
    }

    public function testConicDirectConstruction(): void
    {
        $gradient = Gradient::conic(45.0, '#ff0000', '#0000ff');

        $this->assertInstanceOf(ConicGradient::class, $gradient);
        $this->assertCount(2, $gradient->getStops());
    }

    public function testDirectConstruction(): void
    {
        // Test direct construction with variadic stops
        $gradient = Gradient::linear(180.0, '#ff0000', '#0000ff');

        $this->assertInstanceOf(LinearGradient::class, $gradient);
        $this->assertCount(2, $gradient->getStops());

        $stops = $gradient->getStops();
        $this->assertSame(0.0, $stops[0]->position);
        $this->assertSame(1.0, $stops[1]->position);
    }

    public function testDirectConstructionMultipleColors(): void
    {
        // Test with 3 colors - should auto-distribute
        $gradient = Gradient::linear(90.0, '#ff0000', '#00ff00', '#0000ff');

        $this->assertInstanceOf(LinearGradient::class, $gradient);
        $this->assertCount(3, $gradient->getStops());

        $stops = $gradient->getStops();
        $this->assertSame(0.0, $stops[0]->position);
        $this->assertSame(0.5, $stops[1]->position);
        $this->assertSame(1.0, $stops[2]->position);
    }

    public function testGradientFacade(): void
    {
        $linear = Gradient::linear(45.0)->from('#f00')->to('#00f')->build();
        $this->assertInstanceOf(LinearGradient::class, $linear);

        $radial = Gradient::radial()->from('#f00')->to('#00f')->build();
        $this->assertInstanceOf(RadialGradient::class, $radial);

        $conic = Gradient::conic()->from('#f00')->to('#00f')->build();
        $this->assertInstanceOf(ConicGradient::class, $conic);
    }

    public function testLinearBuilder(): void
    {
        $gradient = GradientBuilder::linear(90.0)
            ->from('#ff0000')
            ->to('#0000ff')
            ->build();

        $this->assertInstanceOf(LinearGradient::class, $gradient);
        $this->assertSame(90.0, $gradient->getAngle());
        $this->assertCount(2, $gradient->getStops());
    }

    public function testRadialBuilder(): void
    {
        $gradient = GradientBuilder::radial()
            ->circle()
            ->from('#ff0000')
            ->to('#0000ff')
            ->build();

        $this->assertInstanceOf(RadialGradient::class, $gradient);
        $this->assertSame('circle', $gradient->getShape());
        $this->assertCount(2, $gradient->getStops());
    }

    public function testRadialDirectConstruction(): void
    {
        $gradient = Gradient::radial('#ff0000', '#0000ff');

        $this->assertInstanceOf(RadialGradient::class, $gradient);
        $this->assertCount(2, $gradient->getStops());
    }
}
