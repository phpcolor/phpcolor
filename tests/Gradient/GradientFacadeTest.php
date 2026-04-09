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
        // Use an explicit position that is NOT where it would be auto-distributed
        // to properly test that it gets overridden.
        $explicitStop = new GradientStop(new SrgbColor(0.0, 1.0, 0.0), 0.4);

        $g = Gradient::linear(90.0, $red, $explicitStop, '#0000ff');
        $stops = $g->getStops();

        // When mixing implicit and explicit stops, all stops are evenly re-distributed,
        // and pre-existing explicit positions are discarded.
        $this->assertCount(3, $stops);
        $this->assertEqualsWithDelta(0.0, $stops[0]->position, 1e-12); // Was implicit, becomes 0.0
        $this->assertEqualsWithDelta(0.5, $stops[1]->position, 1e-12); // Was explicit 0.4, becomes 0.5
        $this->assertEqualsWithDelta(1.0, $stops[2]->position, 1e-12); // Was implicit, becomes 1.0
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
