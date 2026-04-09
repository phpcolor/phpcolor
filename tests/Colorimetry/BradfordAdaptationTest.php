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

namespace PhpColor\Color\Tests\Colorimetry;

use PhpColor\Color\Colorimetry\Adaptation\Bradford;
use PhpColor\Color\Colorimetry\Illuminant;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Bradford::class)]
final class BradfordAdaptationTest extends TestCase
{
    private const float TOLERANCE = 1e-6;

    /**
     * Test round-trip adaptation D65→D50→D65 maintains XYZ values.
     */
    public function testRoundTripD65ToD50AndBack(): void
    {
        $src = Illuminant::D65->whitePoint();
        $dst = Illuminant::D50->whitePoint();

        // Pick an arbitrary XYZ color (Y in [0,1])
        $xyz = [0.25, 0.40, 0.10];

        $toD50 = Bradford::adaptXYZ($xyz, $src, $dst);
        $back = Bradford::adaptXYZ($toD50, $dst, $src);

        // Expect near-identity after round-trip (ΔE < 1e-6)
        $this->assertEqualsWithDelta($xyz[0], $back[0], self::TOLERANCE);
        $this->assertEqualsWithDelta($xyz[1], $back[1], self::TOLERANCE);
        $this->assertEqualsWithDelta($xyz[2], $back[2], self::TOLERANCE);
    }

    /**
     * Test identity transformation when source and dest white points are equal.
     */
    public function testIdentityWhenWhitePointsEqual(): void
    {
        $wp = Illuminant::D65->whitePoint();
        $xyz = [0.3, 0.5, 0.2];
        $out = Bradford::adaptXYZ($xyz, $wp, $wp);
        $this->assertSame($xyz[0], $out[0]);
        $this->assertSame($xyz[1], $out[1]);
        $this->assertSame($xyz[2], $out[2]);
    }

    /**
     * Test round-trip adaptation with multiple sample XYZ colors to ensure no drift.
     *
     * Tests a range of colors from dark to bright, neutral to saturated.
     */
    #[DataProvider('sampleXyzColorsProvider')]
    public function testRoundTripWithSampleColors(array $xyz, string $description): void
    {
        $d65 = Illuminant::D65->whitePoint();
        $d50 = Illuminant::D50->whitePoint();

        $adapted = Bradford::adaptXYZ($xyz, $d65, $d50);
        $roundTrip = Bradford::adaptXYZ($adapted, $d50, $d65);

        $this->assertEqualsWithDelta($xyz[0], $roundTrip[0], self::TOLERANCE, "X mismatch for $description");
        $this->assertEqualsWithDelta($xyz[1], $roundTrip[1], self::TOLERANCE, "Y mismatch for $description");
        $this->assertEqualsWithDelta($xyz[2], $roundTrip[2], self::TOLERANCE, "Z mismatch for $description");
    }

    /**
     * Provide sample XYZ colors spanning the color space.
     *
     * @return iterable<string, array{array, string}>
     */
    public static function sampleXyzColorsProvider(): iterable
    {
        yield 'Pure black' => [[0.0, 0.0, 0.0], 'black'];
        yield 'Very dark gray' => [[0.01, 0.01, 0.01], 'very dark gray'];
        yield 'Dark gray' => [[0.1, 0.1, 0.1], 'dark gray'];
        yield 'Mid gray' => [[0.5, 0.5, 0.5], 'mid gray'];
        yield 'Light gray' => [[0.8, 0.8, 0.8], 'light gray'];
        yield 'Near white' => [[0.95, 0.95, 0.95], 'near white'];
        yield 'Red-ish' => [[0.4, 0.2, 0.1], 'red-ish'];
        yield 'Green-ish' => [[0.2, 0.4, 0.1], 'green-ish'];
        yield 'Blue-ish' => [[0.1, 0.2, 0.4], 'blue-ish'];
        yield 'Cyan-ish' => [[0.2, 0.4, 0.5], 'cyan-ish'];
        yield 'Magenta-ish' => [[0.4, 0.2, 0.5], 'magenta-ish'];
        yield 'Yellow-ish' => [[0.5, 0.5, 0.1], 'yellow-ish'];
    }

    /**
     * Test round-trip between various illuminant pairs.
     */
    #[DataProvider('illuminantPairsProvider')]
    public function testRoundTripBetweenIlluminants(Illuminant $src, Illuminant $dst): void
    {
        $srcWp = $src->whitePoint();
        $dstWp = $dst->whitePoint();
        $xyz = [0.4, 0.3, 0.2]; // Mid-tone gray-ish color

        $adapted = Bradford::adaptXYZ($xyz, $srcWp, $dstWp);
        $roundTrip = Bradford::adaptXYZ($adapted, $dstWp, $srcWp);

        $this->assertEqualsWithDelta($xyz[0], $roundTrip[0], self::TOLERANCE);
        $this->assertEqualsWithDelta($xyz[1], $roundTrip[1], self::TOLERANCE);
        $this->assertEqualsWithDelta($xyz[2], $roundTrip[2], self::TOLERANCE);
    }

    /**
     * Provide pairs of illuminants for round-trip testing.
     *
     * @return iterable<string, array{Illuminant, Illuminant}>
     */
    public static function illuminantPairsProvider(): iterable
    {
        yield 'D65 ↔ D50' => [Illuminant::D65, Illuminant::D50];
        yield 'D65 ↔ D55' => [Illuminant::D65, Illuminant::D55];
        yield 'D65 ↔ D75' => [Illuminant::D65, Illuminant::D75];
        yield 'D50 ↔ D55' => [Illuminant::D50, Illuminant::D55];
        yield 'D55 ↔ D75' => [Illuminant::D55, Illuminant::D75];
        yield 'A ↔ D65' => [Illuminant::A, Illuminant::D65];
        yield 'E ↔ D65' => [Illuminant::E, Illuminant::D65];
    }

    /**
     * Test that white point itself adapts to target white point.
     */
    public function testWhitePointAdaptation(): void
    {
        $d65 = Illuminant::D65->whitePoint();
        $d50 = Illuminant::D50->whitePoint();

        // D65 white point XYZ (normalized to Y=1)
        $d65Xyz = $d65->toArray();

        // Adapt D65 white to D50
        $adapted = Bradford::adaptXYZ($d65Xyz, $d65, $d50);

        // Should approximately equal D50 white point
        $this->assertEqualsWithDelta($d50->X, $adapted[0], 1e-5, 'Adapted X should match D50');
        $this->assertEqualsWithDelta($d50->Y, $adapted[1], 1e-5, 'Adapted Y should match D50');
        $this->assertEqualsWithDelta($d50->Z, $adapted[2], 1e-5, 'Adapted Z should match D50');
    }

    /**
     * Test adaptation of edge case: pure black (0, 0, 0).
     */
    public function testBlackAdaptation(): void
    {
        $d65 = Illuminant::D65->whitePoint();
        $d50 = Illuminant::D50->whitePoint();
        $black = [0.0, 0.0, 0.0];

        $adapted = Bradford::adaptXYZ($black, $d65, $d50);

        $this->assertEquals(0.0, $adapted[0], 'Black X should remain 0');
        $this->assertEquals(0.0, $adapted[1], 'Black Y should remain 0');
        $this->assertEquals(0.0, $adapted[2], 'Black Z should remain 0');
    }

    /**
     * Test adaptation preserves relative luminance relationships.
     *
     * If color A is brighter than color B before adaptation, it should remain
     * brighter after adaptation (luminance order preservation).
     */
    public function testLuminanceOrderPreservation(): void
    {
        $d65 = Illuminant::D65->whitePoint();
        $d50 = Illuminant::D50->whitePoint();

        $dark = [0.1, 0.1, 0.1];   // Y = 0.1
        $bright = [0.5, 0.5, 0.5]; // Y = 0.5

        $darkAdapted = Bradford::adaptXYZ($dark, $d65, $d50);
        $brightAdapted = Bradford::adaptXYZ($bright, $d65, $d50);

        $this->assertLessThan($brightAdapted[1], $darkAdapted[1], 'Luminance order should be preserved');
    }

    /**
     * Test that consecutive adaptations compose correctly.
     *
     * D65→D50→D55 should equal direct D65→D55 (within tolerance).
     */
    public function testConsecutiveAdaptations(): void
    {
        $d65 = Illuminant::D65->whitePoint();
        $d50 = Illuminant::D50->whitePoint();
        $d55 = Illuminant::D55->whitePoint();

        $xyz = [0.3, 0.4, 0.2];

        // Two-step: D65→D50→D55
        $step1 = Bradford::adaptXYZ($xyz, $d65, $d50);
        $twoStep = Bradford::adaptXYZ($step1, $d50, $d55);

        // Direct: D65→D55
        $direct = Bradford::adaptXYZ($xyz, $d65, $d55);

        // Should be equivalent within numerical precision
        $this->assertEqualsWithDelta($direct[0], $twoStep[0], 1e-5, 'Consecutive X matches direct');
        $this->assertEqualsWithDelta($direct[1], $twoStep[1], 1e-5, 'Consecutive Y matches direct');
        $this->assertEqualsWithDelta($direct[2], $twoStep[2], 1e-5, 'Consecutive Z matches direct');
    }
}
