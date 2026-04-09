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

use PhpColor\Color\Colorimetry\Illuminant;
use PhpColor\Color\Colorimetry\Observer;
use PhpColor\Color\Colorimetry\WhitePoint;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Illuminant::class)]
#[CoversClass(Observer::class)]
#[CoversClass(WhitePoint::class)]
final class IlluminantTest extends TestCase
{
    public function testAllCasesCount(): void
    {
        // Verify we have exactly 11 illuminants
        $cases = Illuminant::cases();
        $this->assertCount(11, $cases);
    }

    public function testAllIlluminantsHaveNormalizedY(): void
    {
        // All illuminants should have Y normalized to 1.0
        foreach (Illuminant::cases() as $ill) {
            $wp = $ill->whitePoint();
            $this->assertSame(1.0, $wp->Y, "Illuminant {$ill->value} should have Y = 1.0");
        }
    }

    public function testD50PrintingStandard(): void
    {
        // D50 is the standard for printing (used by ProPhoto RGB)
        $wp = Illuminant::D50->whitePoint();

        $this->assertEqualsWithDelta(0.96422, $wp->X, 1e-5);
        $this->assertSame(1.0, $wp->Y);
        $this->assertEqualsWithDelta(0.82521, $wp->Z, 1e-5);
    }

    public function testD65StandardIlluminant(): void
    {
        // D65 is the standard illuminant for sRGB and most computer graphics
        $wp = Illuminant::D65->whitePoint();

        $this->assertEqualsWithDelta(0.95047, $wp->X, 1e-5);
        $this->assertSame(1.0, $wp->Y);
        $this->assertEqualsWithDelta(1.08883, $wp->Z, 1e-5);
    }

    public function testDaylightSeriesOrdering(): void
    {
        // Verify daylight series increases in Z component (bluer) as temp increases
        $d50 = Illuminant::D50->whitePoint();
        $d55 = Illuminant::D55->whitePoint();
        $d60 = Illuminant::D60->whitePoint();
        $d65 = Illuminant::D65->whitePoint();
        $d75 = Illuminant::D75->whitePoint();

        $this->assertLessThan($d55->Z, $d50->Z, 'D50 should have less Z than D55');
        $this->assertLessThan($d60->Z, $d55->Z, 'D55 should have less Z than D60');
        $this->assertLessThan($d65->Z, $d60->Z, 'D60 should have less Z than D65');
        $this->assertLessThan($d75->Z, $d65->Z, 'D65 should have less Z than D75');
    }

    public function testEnumValues(): void
    {
        // Test that enum values match their string representation
        $this->assertSame('A', Illuminant::A->value);
        $this->assertSame('C', Illuminant::C->value);
        $this->assertSame('D50', Illuminant::D50->value);
        $this->assertSame('D55', Illuminant::D55->value);
        $this->assertSame('D60', Illuminant::D60->value);
        $this->assertSame('D65', Illuminant::D65->value);
        $this->assertSame('D75', Illuminant::D75->value);
        $this->assertSame('E', Illuminant::E->value);
        $this->assertSame('F2', Illuminant::F2->value);
        $this->assertSame('F7', Illuminant::F7->value);
        $this->assertSame('F11', Illuminant::F11->value);
    }

    public function testEqualEnergyIlluminant(): void
    {
        // Illuminant E should have equal energy (X=Y=Z=1.0)
        $wp = Illuminant::E->whitePoint();

        $this->assertSame(1.0, $wp->X);
        $this->assertSame(1.0, $wp->Y);
        $this->assertSame(1.0, $wp->Z);
    }

    public function testIncandescentVsFluorescent(): void
    {
        // A (incandescent) should be warm (high X, low Z)
        $wpA = Illuminant::A->whitePoint();
        $this->assertGreaterThan(1.0, $wpA->X, 'A should have X > 1 (warm)');
        $this->assertLessThan(0.5, $wpA->Z, 'A should have low Z (warm)');

        // F7 (fluorescent) should be closer to daylight
        $wpF7 = Illuminant::F7->whitePoint();
        $this->assertLessThan($wpA->X, $wpF7->X, 'F7 should be less warm than A');
        $this->assertGreaterThan($wpA->Z, $wpF7->Z, 'F7 should have more Z than A');
    }

    public function testObserverParameterBackwardCompatibility(): void
    {
        // Existing calls without observer parameter should continue to work
        foreach (Illuminant::cases() as $ill) {
            $wp = $ill->whitePoint();
            $this->assertInstanceOf(WhitePoint::class, $wp);
            $this->assertSame(1.0, $wp->Y, "Illuminant {$ill->value} should have Y = 1.0");
        }
    }

    public function testWhitePointConsistency(): void
    {
        // Calling whitePoint() multiple times should return equivalent values
        $wp1 = Illuminant::D65->whitePoint();
        $wp2 = Illuminant::D65->whitePoint();

        $this->assertEqualsWithDelta($wp1->X, $wp2->X, 0.0);
        $this->assertEqualsWithDelta($wp1->Y, $wp2->Y, 0.0);
        $this->assertEqualsWithDelta($wp1->Z, $wp2->Z, 0.0);
    }

    public function testWhitePointDefaultObserver(): void
    {
        // Without observer parameter, should default to 2°
        $wpDefault = Illuminant::D65->whitePoint();
        $wpExplicit = Illuminant::D65->whitePoint(Observer::TwoDegree);

        $this->assertEquals($wpDefault->X, $wpExplicit->X);
        $this->assertEquals($wpDefault->Y, $wpExplicit->Y);
        $this->assertEquals($wpDefault->Z, $wpExplicit->Z);
    }

    /**
     * @param array{float,float,float} $expected
     */
    #[DataProvider('illuminantProvider')]
    public function testWhitePoints(Illuminant $ill, array $expected): void
    {
        $wp = $ill->whitePoint();

        $this->assertInstanceOf(WhitePoint::class, $wp);
        $this->assertEqualsWithDelta($expected[0], $wp->X, 1e-5);
        $this->assertEqualsWithDelta($expected[1], $wp->Y, 0.0);
        $this->assertEqualsWithDelta($expected[2], $wp->Z, 1e-5);

        $arr = $wp->toArray();
        $this->assertEqualsWithDelta($expected[0], $arr[0], 1e-5);
        $this->assertEqualsWithDelta($expected[1], $arr[1], 0.0);
        $this->assertEqualsWithDelta($expected[2], $arr[2], 1e-5);
    }

    /**
     * @return array<string,array{Illuminant,array{float,float,float}}>
     */
    public static function illuminantProvider(): array
    {
        // Expected XYZ for CIE 1931 2° observer (Y = 1.0).
        return [
            'A' => [Illuminant::A,   [1.09850, 1.00000, 0.35585]],
            'C' => [Illuminant::C,   [0.98074, 1.00000, 1.18232]],
            'D50' => [Illuminant::D50, [0.96422, 1.00000, 0.82521]],
            'D55' => [Illuminant::D55, [0.95682, 1.00000, 0.92149]],
            'D60' => [Illuminant::D60, [0.95231, 1.00000, 1.00883]],
            'D65' => [Illuminant::D65, [0.95047, 1.00000, 1.08883]],
            'D75' => [Illuminant::D75, [0.94972, 1.00000, 1.22638]],
            'E' => [Illuminant::E,   [1.00000, 1.00000, 1.00000]],
            'F2' => [Illuminant::F2,  [0.99186, 1.00000, 0.67393]],
            'F7' => [Illuminant::F7,  [0.95041, 1.00000, 1.08747]],
            'F11' => [Illuminant::F11, [1.00962, 1.00000, 0.64350]],
        ];
    }

    public function testWhitePointWithTenDegreeObserver(): void
    {
        // Request 10° observer
        $wp = Illuminant::D65->whitePoint(Observer::TenDegree);

        $this->assertInstanceOf(WhitePoint::class, $wp);
        // CIE 1964 10° white for D65
        $this->assertEqualsWithDelta(0.94811, $wp->X, 1e-5);
        $this->assertSame(1.0, $wp->Y);
        $this->assertEqualsWithDelta(1.07304, $wp->Z, 1e-5);
    }

    public function testWhitePointWithTwoDegreeObserver(): void
    {
        // Explicitly request 2° observer
        $wp = Illuminant::D65->whitePoint(Observer::TwoDegree);

        $this->assertInstanceOf(WhitePoint::class, $wp);
        $this->assertEqualsWithDelta(0.95047, $wp->X, 1e-5);
        $this->assertSame(1.0, $wp->Y);
        $this->assertEqualsWithDelta(1.08883, $wp->Z, 1e-5);
    }
}
