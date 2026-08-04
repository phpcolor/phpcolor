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

namespace PhpColor\Color\Tests\Distance;

use PhpColor\Color\Color;
use PhpColor\Color\Distance\ColorDistanceInterface;
use PhpColor\Color\Distance\DeltaE94;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DeltaE94::class)]
final class DeltaE94Test extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame('DeltaE94', (new DeltaE94())->getName());
    }

    public function testHueTermShrinksWhenChromaIncreases(): void
    {
        // (a, 24) vs (a, -24): deltaL = deltaC = 0 and deltaH = 48, so the result is 48 / SH.
        $calc = new DeltaE94();

        $pairs = [
            // [a, C1 = hypot(a, 24), 48 / (1 + 0.015 * C1)]
            [7.0, 25.0, 34.90909090909091],
            [18.0, 30.0, 33.10344827586207],
            [32.0, 40.0, 30.0],
            [45.0, 51.0, 27.195467422096314],
        ];

        $previous = null;

        foreach ($pairs as [$a, $C1, $expected]) {
            $result = $calc->calculateFromLab(50.0, $a, 24.0, 50.0, $a, -24.0);

            $this->assertEqualsWithDelta($C1, sqrt($a * $a + 24.0 * 24.0), 1e-12);
            $this->assertEqualsWithDelta($expected, $result, 1e-9);

            if (null !== $previous) {
                $this->assertLessThan($previous, $result);
            }

            $previous = $result;
        }
    }

    public function testImplementsInterface(): void
    {
        $calc = new DeltaE94();
        $this->assertInstanceOf(ColorDistanceInterface::class, $calc);
    }

    public function testProducesPositiveDifference(): void
    {
        $calc = new DeltaE94();
        $d = $calc->calculate(Color::parse('red'), Color::parse('blue'));
        $this->assertGreaterThan(0.0, $d);
    }

    #[DataProvider('provideReferenceValues')]
    public function testReferenceValues(float $L1, float $a1, float $b1, float $L2, float $a2, float $b2, float $expected): void
    {
        $calc = new DeltaE94();
        $res = $calc->calculateFromLab($L1, $a1, $b1, $L2, $a2, $b2);
        $this->assertEqualsWithDelta($expected, $res, 0.0001);
    }

    public static function provideReferenceValues(): iterable
    {
        yield 'low chroma, C1 = 5' => [60.0, 3.0, 4.0, 62.0, -4.0, 3.0, 6.8750731350];
        yield 'medium chroma, C1 = 25' => [60.0, 20.0, 15.0, 55.0, 9.0, 12.0, 7.9380789436];
        yield 'chroma shift only, C1 = 50' => [70.0, 40.0, 30.0, 70.0, 20.0, 15.0, 7.6923076923];
        yield 'hue rotation, C1 = 50' => [50.0, 30.0, 40.0, 50.0, 40.0, 30.0, 8.0812203564];
        yield 'dark quadrant, C1 = 65' => [30.0, -25.0, -60.0, 35.0, -60.0, -25.0, 25.5559087187];
        yield 'high chroma, C1 = 100' => [50.0, 60.0, 80.0, 50.0, 80.0, 60.0, 11.3137084990];
        yield 'red vs blue, C1 = 104.576' => [53.23, 80.11, 67.22, 32.30, 79.19, -107.86, 70.5746793223];
    }

    public function testZeroChromaLeavesWeightingFactorsNeutral(): void
    {
        // C1 = 0 sets SC = SH = 1 and forces deltaH = 0, so both parenthesizings agree.
        $calc = new DeltaE94();

        $this->assertEqualsWithDelta(5.0, $calc->calculateFromLab(50.0, 0.0, 0.0, 53.0, 0.0, 4.0), 1e-12);
        $this->assertEqualsWithDelta(5.0, $calc->calculateFromLab(95.0, 0.0, 0.0, 90.0, 0.0, 0.0), 1e-12);
    }
}
