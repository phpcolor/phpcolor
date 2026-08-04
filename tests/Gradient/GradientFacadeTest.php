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
use PhpColor\Color\Gradient\ConicGradient;
use PhpColor\Color\Gradient\Gradient;
use PhpColor\Color\Gradient\GradientBuilder;
use PhpColor\Color\Gradient\GradientStop;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Gradient::class)]
final class GradientFacadeTest extends TestCase
{
    public function testConicBuilderWhenNoStopsProvided(): void
    {
        $builder = Gradient::conic(45.0);
        $this->assertInstanceOf(GradientBuilder::class, $builder);
    }

    public function testLinearWithExplicitStopsPreservesPositions(): void
    {
        $red = new SrgbColor(1.0, 0.0, 0.0);
        $blue = new SrgbColor(0.0, 0.0, 1.0);

        $g = Gradient::linear(90.0, new GradientStop($red, 0.2), new GradientStop($blue, 0.8));

        $stops = $g->getStops();
        $this->assertCount(2, $stops);
        $this->assertEqualsWithDelta(0.2, $stops[0]->position, 1e-12);
        $this->assertEqualsWithDelta(0.8, $stops[1]->position, 1e-12);
    }

    public function testRadialSingleStringStopGetsDefaultPosition(): void
    {
        $g = Gradient::radial('#ff0000');
        $stops = $g->getStops();

        $this->assertCount(1, $stops);
        $this->assertEqualsWithDelta(0.0, $stops[0]->position, 1e-12);
        $this->assertSame('#ff0000', strtolower($stops[0]->color->toHex()));
    }

    public function testLinearWithMixedStopTypes(): void
    {
        $red = new SrgbColor(1.0, 0.0, 0.0);
        // Explicit position that differs from where an even distribution would put it.
        $explicitStop = new GradientStop(new SrgbColor(0.0, 1.0, 0.0), 0.4);

        $g = Gradient::linear(90.0, $red, $explicitStop, '#0000ff');
        $stops = $g->getStops();

        $this->assertCount(3, $stops);
        $this->assertEqualsWithDelta(0.0, $stops[0]->position, 1e-12);
        $this->assertEqualsWithDelta(0.4, $stops[1]->position, 1e-12);
        $this->assertEqualsWithDelta(1.0, $stops[2]->position, 1e-12);
    }

    public function testLinearKeepsExplicitPositionMixedWithPlainColor(): void
    {
        $g = Gradient::linear(180.0, '#000000', new GradientStop(Color::parse('#ff0000'), 0.9));
        $stops = $g->getStops();

        $this->assertCount(2, $stops);
        $this->assertEqualsWithDelta(0.0, $stops[0]->position, 1e-12);
        $this->assertEqualsWithDelta(0.9, $stops[1]->position, 1e-12);
        $this->assertStringContainsString('rgb(255 0 0) 90%', $g->toCss());
    }

    public function testLinearSpreadsPlainColorBetweenExplicitStops(): void
    {
        $g = Gradient::linear(
            180.0,
            new GradientStop(Color::parse('#ff0000'), 0.2),
            '#000000',
            new GradientStop(Color::parse('#00ff00'), 0.8),
        );
        $stops = $g->getStops();

        $this->assertCount(3, $stops);
        $this->assertEqualsWithDelta(0.2, $stops[0]->position, 1e-12);
        $this->assertEqualsWithDelta(0.5, $stops[1]->position, 1e-12);
        $this->assertEqualsWithDelta(0.8, $stops[2]->position, 1e-12);
    }

    public function testLinearSpreadsConsecutivePlainColorsBetweenExplicitStops(): void
    {
        $g = Gradient::linear(
            180.0,
            new GradientStop(Color::parse('#ff0000'), 0.2),
            '#000000',
            '#888888',
            new GradientStop(Color::parse('#00ff00'), 0.8),
        );
        $stops = $g->getStops();

        $this->assertCount(4, $stops);
        $this->assertEqualsWithDelta(0.2, $stops[0]->position, 1e-12);
        $this->assertEqualsWithDelta(0.4, $stops[1]->position, 1e-12);
        $this->assertEqualsWithDelta(0.6, $stops[2]->position, 1e-12);
        $this->assertEqualsWithDelta(0.8, $stops[3]->position, 1e-12);
    }

    public function testLinearSpreadsLeadingPlainColorsFromZero(): void
    {
        $g = Gradient::linear(180.0, '#000000', '#888888', new GradientStop(Color::parse('#00ff00'), 0.9));
        $stops = $g->getStops();

        $this->assertCount(3, $stops);
        $this->assertEqualsWithDelta(0.0, $stops[0]->position, 1e-12);
        $this->assertEqualsWithDelta(0.45, $stops[1]->position, 1e-12);
        $this->assertEqualsWithDelta(0.9, $stops[2]->position, 1e-12);
    }

    public function testLinearSpreadsTrailingPlainColorsUpToOne(): void
    {
        $g = Gradient::linear(180.0, new GradientStop(Color::parse('#ff0000'), 0.1), '#000000', '#888888');
        $stops = $g->getStops();

        $this->assertCount(3, $stops);
        $this->assertEqualsWithDelta(0.1, $stops[0]->position, 1e-12);
        $this->assertEqualsWithDelta(0.55, $stops[1]->position, 1e-12);
        $this->assertEqualsWithDelta(1.0, $stops[2]->position, 1e-12);
    }

    public function testLinearWithoutExplicitPositionDistributesEvenly(): void
    {
        $g = Gradient::linear(180.0, '#000000', '#888888', '#ffffff');
        $stops = $g->getStops();

        $this->assertCount(3, $stops);
        $this->assertEqualsWithDelta(0.0, $stops[0]->position, 1e-12);
        $this->assertEqualsWithDelta(0.5, $stops[1]->position, 1e-12);
        $this->assertEqualsWithDelta(1.0, $stops[2]->position, 1e-12);
    }

    public function testLinearWithOnlyExplicitStopsKeepsEveryPosition(): void
    {
        $g = Gradient::linear(
            180.0,
            new GradientStop(Color::parse('#ff0000'), 0.3),
            new GradientStop(Color::parse('#00ff00'), 0.7),
        );
        $stops = $g->getStops();

        $this->assertCount(2, $stops);
        $this->assertEqualsWithDelta(0.3, $stops[0]->position, 1e-12);
        $this->assertEqualsWithDelta(0.7, $stops[1]->position, 1e-12);
    }

    public function testConicWithNullValuesFilteredOut(): void
    {
        $g = Gradient::conic(45.0, '#ff0000', null, '#0000ff', null);
        $stops = $g->getStops();

        // Nulls should be filtered out, leaving only 2 stops
        $this->assertCount(2, $stops);
        $this->assertEqualsWithDelta(0.0, $stops[0]->position, 1e-12);
        $this->assertEqualsWithDelta(1.0, $stops[1]->position, 1e-12);
    }

    public function testRadialWithOnlyNullsReturnsBuilder(): void
    {
        $builder = Gradient::radial(null, null);
        $this->assertInstanceOf(GradientBuilder::class, $builder);
    }

    public function testLinearWithColorInterfaceStops(): void
    {
        $red = new SrgbColor(1.0, 0.0, 0.0);
        $blue = new SrgbColor(0.0, 0.0, 1.0);

        $g = Gradient::linear(180.0, $red, $blue);
        $stops = $g->getStops();

        $this->assertCount(2, $stops);
        $this->assertEqualsWithDelta(0.0, $stops[0]->position, 1e-12);
        $this->assertEqualsWithDelta(1.0, $stops[1]->position, 1e-12);
    }

    public function testConicWithThreeStopsDistributesEvenly(): void
    {
        $g = Gradient::conic(0.0, '#ff0000', '#00ff00', '#0000ff');
        $stops = $g->getStops();

        $this->assertCount(3, $stops);
        $this->assertEqualsWithDelta(0.0, $stops[0]->position, 1e-12);
        $this->assertEqualsWithDelta(0.5, $stops[1]->position, 1e-12);
        $this->assertEqualsWithDelta(1.0, $stops[2]->position, 1e-12);
    }

    public function testRadialWithFourStopsDistributesEvenly(): void
    {
        $g = Gradient::radial('#ff0000', '#00ff00', '#0000ff', '#ffff00');
        $stops = $g->getStops();

        $this->assertCount(4, $stops);
        $this->assertEqualsWithDelta(0.0, $stops[0]->position, 1e-12);
        $this->assertEqualsWithDelta(1.0 / 3.0, $stops[1]->position, 1e-12);
        $this->assertEqualsWithDelta(2.0 / 3.0, $stops[2]->position, 1e-12);
        $this->assertEqualsWithDelta(1.0, $stops[3]->position, 1e-12);
    }

    public function testLinearWithAllExplicitStopsPreservesPositions(): void
    {
        $stop1 = new GradientStop(new SrgbColor(1.0, 0.0, 0.0), 0.1);
        $stop2 = new GradientStop(new SrgbColor(0.0, 1.0, 0.0), 0.3);
        $stop3 = new GradientStop(new SrgbColor(0.0, 0.0, 1.0), 0.9);

        $g = Gradient::linear(90.0, $stop1, $stop2, $stop3);
        $stops = $g->getStops();

        $this->assertCount(3, $stops);
        $this->assertEqualsWithDelta(0.1, $stops[0]->position, 1e-12);
        $this->assertEqualsWithDelta(0.3, $stops[1]->position, 1e-12);
        $this->assertEqualsWithDelta(0.9, $stops[2]->position, 1e-12);
    }

    public function testConicWithSingleStopCreatesGradient(): void
    {
        $g = Gradient::conic(120.0, '#ff0000');
        $this->assertInstanceOf(ConicGradient::class, $g);
        $stops = $g->getStops();
        $this->assertCount(1, $stops);
    }

    public function testLinearNoStopsReturnsBuilder(): void
    {
        $builder = Gradient::linear(90.0);
        $this->assertInstanceOf(GradientBuilder::class, $builder);
    }

    public function testRadialNoStopsReturnsBuilder(): void
    {
        $builder = Gradient::radial();
        $this->assertInstanceOf(GradientBuilder::class, $builder);
    }
}
