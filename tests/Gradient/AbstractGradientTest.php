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

use PhpColor\Color\ColorInterface;
use PhpColor\Color\Gradient\AbstractGradient;
use PhpColor\Color\Gradient\GradientStop;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractGradient::class)]
final class AbstractGradientTest extends TestCase
{
    public function testAddStopReturnsNewInstanceAndKeepsExistingStops(): void
    {
        $red = new SrgbColor(255, 0, 0);
        $blue = new SrgbColor(0, 0, 255);

        $gradient = new readonly class([new GradientStop($red, 0.0)]) extends AbstractGradient {
            public function addStop(ColorInterface $color, float $position): static
            {
                $stops = $this->stops;
                $stops[] = new GradientStop($color, $position);

                return new self($stops, $this->interpolationSpace);
            }

            public function getType(): string
            {
                return 'test';
            }

            public function toCss(?string $colorSpace = null): string
            {
                return 'test-gradient('.$this->formatStops($colorSpace).')';
            }
        };

        $updated = $gradient->addStop($blue, 1.0);

        $this->assertNotSame($gradient, $updated);
        $this->assertCount(1, $gradient->getStops());
        $this->assertCount(2, $updated->getStops());
        $this->assertTrue($updated->getStops()[1]->color->equals($blue));
    }

    public function testGetTypeAndToCssShape(): void
    {
        $red = new SrgbColor(255, 0, 0);
        $blue = new SrgbColor(0, 0, 255);

        $gradient = new readonly class([new GradientStop($red, 0.0), new GradientStop($blue, 1.0)]) extends AbstractGradient {
            public function addStop(ColorInterface $color, float $position): static
            {
                $stops = $this->stops;
                $stops[] = new GradientStop($color, $position);

                return new self($stops, $this->interpolationSpace);
            }

            public function getType(): string
            {
                return 'test';
            }

            public function toCss(?string $colorSpace = null): string
            {
                return 'test-gradient('.$this->formatStops($colorSpace).')';
            }
        };

        $this->assertSame('test', $gradient->getType());
        $css = $gradient->toCss('srgb');
        $this->assertStringStartsWith('test-gradient(', $css);
        $this->assertStringContainsString('rgb(', $css);
        $this->assertStringContainsString(',', $css, 'Should contain comma-separated stops');
    }

    public function testInterpolateAtHardStopReturnsLastColor(): void
    {
        // A hard stop is created by defining two colors at the same position.
        $red = new SrgbColor(255, 0, 0);
        $blue = new SrgbColor(0, 0, 255);

        $gradient = new readonly class([new GradientStop($red, 0.5), new GradientStop($blue, 0.5)]) extends AbstractGradient {
            public function addStop(ColorInterface $color, float $position): static
            {
                $stops = $this->stops;
                $stops[] = new GradientStop($color, $position);

                return new self($stops, $this->interpolationSpace);
            }

            public function getType(): string
            {
                return 'test';
            }

            public function toCss(?string $colorSpace = null): string
            {
                return 'test-gradient('.$this->formatStops($colorSpace).')';
            }
        };

        // When interpolating at the exact position of the hard stop (0.5),
        // the code should return the color of the *last* stop defined at that position.
        // This is because the loop to find the 'before' stop will overwrite it
        // with the last matching stop it finds.
        $interpolatedColor = $gradient->interpolate(0.5);

        $this->assertTrue(
            $blue->equals($interpolatedColor),
            'The interpolated color at a hard stop should be the last color defined at that position.'
        );
    }

    public function testInterpolateBeforeFirstStopReturnsFirstColor(): void
    {
        $red = new SrgbColor(255, 0, 0);
        $blue = new SrgbColor(0, 0, 255);

        $gradient = new readonly class([new GradientStop($red, 0.2), new GradientStop($blue, 0.8)]) extends AbstractGradient {
            public function addStop(ColorInterface $color, float $position): static
            {
                $stops = $this->stops;
                $stops[] = new GradientStop($color, $position);

                return new self($stops, $this->interpolationSpace);
            }

            public function getType(): string
            {
                return 'test';
            }

            public function toCss(?string $colorSpace = null): string
            {
                return 'test-gradient('.$this->formatStops($colorSpace).')';
            }
        };

        // Position before the first stop should return the first stop's color
        $color = $gradient->interpolate(0.0);
        $this->assertTrue($color->equals($red));
    }

    public function testInterpolateMidpointInOklabProducesMidOklab(): void
    {
        $red = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $blue = new SrgbColor(0.0, 0.0, 1.0, 1.0);

        $gradient = new readonly class([new GradientStop($red, 0.0), new GradientStop($blue, 1.0)]) extends AbstractGradient {
            public function addStop(ColorInterface $color, float $position): static
            {
                $stops = $this->stops;
                $stops[] = new GradientStop($color, $position);

                return new self($stops, $this->interpolationSpace);
            }

            public function getType(): string
            {
                return 'test';
            }

            public function toCss(?string $colorSpace = null): string
            {
                return 'test-gradient('.$this->formatStops($colorSpace).')';
            }
        };

        $mid = $gradient->interpolate(0.5);

        $labR = $red->to('oklab');
        $labB = $blue->to('oklab');
        $labM = $mid->to('oklab');

        $this->assertEqualsWithDelta(($labR->l + $labB->l) / 2.0, $labM->l, 0.02);
        $this->assertEqualsWithDelta(($labR->a + $labB->a) / 2.0, $labM->a, 0.02);
        $this->assertEqualsWithDelta(($labR->b + $labB->b) / 2.0, $labM->b, 0.02);
    }

    public function testInterpolateWithNearlyIdenticalStopPositionsReturnsBeforeColor(): void
    {
        $red = new SrgbColor(255, 0, 0);
        $blue = new SrgbColor(0, 0, 255);

        // Two positions that are extremely close so that their difference
        // is below the 1e-10 threshold inside AbstractGradient::interpolate().
        $p1 = 0.3;
        $p2 = $p1 + 5e-11; // 5e-11 apart (< 1e-10)
        $between = $p1 + 2.5e-11; // strictly between $p1 and $p2

        $gradient = new readonly class([new GradientStop($red, $p1), new GradientStop($blue, $p2)]) extends AbstractGradient {
            public function addStop(ColorInterface $color, float $position): static
            {
                $stops = $this->stops;
                $stops[] = new GradientStop($color, $position);

                return new self($stops, $this->interpolationSpace);
            }

            public function getType(): string
            {
                return 'test';
            }

            public function toCss(?string $colorSpace = null): string
            {
                return 'test-gradient('.$this->formatStops($colorSpace).')';
            }
        };

        // Because the two bracketing stops have nearly identical positions,
        // interpolate() should return the 'before' color.
        $color = $gradient->interpolate($between);
        $this->assertTrue($color->equals($red));
    }
}
