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

namespace PhpColor\Color\Tests;

use PhpColor\Color\ColorInterface;
use PhpColor\Color\ProPhotoColor;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProPhotoColor::class)]
final class ProPhotoColorTest extends ColorTestCase
{
    protected function createColor(): ColorInterface
    {
        return new ProPhotoColor(0.4, 0.5, 0.6, 0.7);
    }

    protected function getExpectedColorClass(): string
    {
        return ProPhotoColor::class;
    }

    public static function provideColorSamples(): iterable
    {
        yield 'warm orange' => [
            new ProPhotoColor(0.65, 0.4, 0.2),
            '#e56531',
            '#e56531ff',
            'color(prophoto-rgb 0.65 0.4 0.2)',
        ];
        yield 'soft blue with alpha' => [
            new ProPhotoColor(0.3, 0.45, 0.8, 0.5),
            '#008ddf',
            '#008ddf80',
            'color(prophoto-rgb 0.3 0.45 0.8 / 0.5)',
        ];
    }

    public static function provideFromInputs(): iterable
    {
        yield [new SrgbColor(1.0, 0.0, 0.0)];
        yield ['#ff0000'];
        yield ['color(prophoto-rgb 0.4 0.5 0.6)'];
    }

    public static function provideInvalidCssOutputSpaces(): array
    {
        return [
            ['lab'],
            ['lch'],
            ['oklab'],
            ['oklch'],
            ['display-p3'],
            ['xyz'],
            ['a98-rgb'],
            ['rec2020'],
            ['hsl'],
            ['color-srgb'],
        ];
    }

    public function testAlphaPreservationThroughGammaTransfer(): void
    {
        $alphas = [0.0, 0.25, 0.5, 0.75, 1.0];

        foreach ($alphas as $alpha) {
            $pro = new ProPhotoColor(0.5, 0.5, 0.5, $alpha);
            $srgb = $pro->toSrgb();
            $roundTrip = ProPhotoColor::fromSrgb($srgb);

            $this->assertEqualsWithDelta($alpha, $srgb->a, 0.0001);
            $this->assertEqualsWithDelta($alpha, $roundTrip->a, 0.0001);
        }
    }

    public function testChannelGetters(): void
    {
        $c = new ProPhotoColor(0.11, 0.22, 0.33, 0.44);
        $this->assertSame(0.11, $c->getRed());
        $this->assertSame(0.22, $c->getGreen());
        $this->assertSame(0.33, $c->getBlue());
    }

    public function testCssOutputInProPhotoSpace(): void
    {
        $color = $this->createColor();
        $css = $color->toCss('prophoto-rgb');
        $this->assertStringStartsWith('color(prophoto-rgb', $css);
    }

    public function testD50ToD65ChromaticAdaptation(): void
    {
        // ProPhoto uses D50 white point, sRGB uses D65
        // Test that pure white converts correctly
        $proWhite = new ProPhotoColor(1.0, 1.0, 1.0);
        $srgbWhite = $proWhite->toSrgb();

        // White should remain white after chromatic adaptation
        $tolerance = 0.01;
        $this->assertEqualsWithDelta(1.0, $srgbWhite->r, $tolerance);
        $this->assertEqualsWithDelta(1.0, $srgbWhite->g, $tolerance);
        $this->assertEqualsWithDelta(1.0, $srgbWhite->b, $tolerance);
    }

    public function testFromSrgbConversion(): void
    {
        $srgb = new SrgbColor(0.1, 0.3, 0.6);
        $pro = ProPhotoColor::fromSrgb($srgb);

        $this->assertInstanceOf(ProPhotoColor::class, $pro);
        $this->assertGreaterThan(0.0, $pro->r);
        $this->assertGreaterThan(0.0, $pro->g);
        $this->assertGreaterThan(0.0, $pro->b);
    }

    public function testGammaExponent(): void
    {
        // ProPhoto uses gamma 1.8 (exponent in power section)
        // Test that a known value follows the expected power relationship
        $encoded = 0.5; // ≈ 0.2176

        $pro = new ProPhotoColor($encoded, $encoded, $encoded);

        // The conversion goes through XYZ and sRGB, so we can't directly verify
        // but we can verify round-trip consistency
        $srgb = $pro->toSrgb();
        $roundTrip = ProPhotoColor::fromSrgb($srgb);

        $tolerance = 0.02;
        $this->assertEqualsWithDelta($encoded, $roundTrip->r, $tolerance);
    }

    public function testGammaTransferFunctionAboveThreshold(): void
    {
        // Test values above LINEAR_THRESHOLD * 16
        $pro = new ProPhotoColor(0.5, 0.5, 0.5);
        $srgb = $pro->toSrgb();

        // Should use power function: value ** 1.8
        $this->assertGreaterThan(0.0, $srgb->r);
        $this->assertLessThan(1.0, $srgb->r);
    }

    public function testGammaTransferFunctionAtOne(): void
    {
        $pro = new ProPhotoColor(1.0, 1.0, 1.0);
        $srgb = $pro->toSrgb();

        $this->assertEqualsWithDelta(1.0, $srgb->r, 0.001);
        $this->assertEqualsWithDelta(1.0, $srgb->g, 0.001);
        $this->assertEqualsWithDelta(1.0, $srgb->b, 0.001);
    }

    public function testGammaTransferFunctionAtZero(): void
    {
        $pro = new ProPhotoColor(0.0, 0.0, 0.0);
        $srgb = $pro->toSrgb();

        $this->assertEqualsWithDelta(0.0, $srgb->r, 0.001);
        $this->assertEqualsWithDelta(0.0, $srgb->g, 0.001);
        $this->assertEqualsWithDelta(0.0, $srgb->b, 0.001);
    }

    public function testGammaTransferFunctionBelowThreshold(): void
    {
        // Test values below LINEAR_THRESHOLD * 16 (16/512 = 0.03125)
        $pro = new ProPhotoColor(0.02, 0.02, 0.02);
        $srgb = $pro->toSrgb();

        // Should use linear section: value / 16.0
        $this->assertGreaterThanOrEqual(0.0, $srgb->r);
        $this->assertLessThan(1.0, $srgb->r);
    }

    public function testGammaTransferFunctionRoundTripAtThreshold(): void
    {
        // Test around LINEAR_THRESHOLD * 16 boundary (0.03125)
        $threshold = 16.0 / 512.0;
        $pro = new ProPhotoColor($threshold, $threshold, $threshold);
        $srgb = $pro->toSrgb();
        $roundTrip = ProPhotoColor::fromSrgb($srgb);

        $tolerance = 0.01;
        $this->assertEqualsWithDelta($pro->r, $roundTrip->r, $tolerance);
        $this->assertEqualsWithDelta($pro->g, $roundTrip->g, $tolerance);
        $this->assertEqualsWithDelta($pro->b, $roundTrip->b, $tolerance);
    }

    public function testGammaTransferFunctionWithVariousValues(): void
    {
        $testValues = [0.0, 0.01, 0.03, 0.05, 0.1, 0.25, 0.5, 0.75, 0.9, 1.0];

        foreach ($testValues as $value) {
            $pro = new ProPhotoColor($value, $value, $value);
            $srgb = $pro->toSrgb();
            $roundTrip = ProPhotoColor::fromSrgb($srgb);

            // Higher tolerance due to color space conversions and D50/D65 adaptation
            $tolerance = 0.02;
            $this->assertEqualsWithDelta($value, $roundTrip->r, $tolerance, "Failed for value {$value}");
            $this->assertEqualsWithDelta($value, $roundTrip->g, $tolerance, "Failed for value {$value}");
            $this->assertEqualsWithDelta($value, $roundTrip->b, $tolerance, "Failed for value {$value}");
        }
    }

    public function testInverseGammaTransferFunctions(): void
    {
        // Test that ProPhoto gamma encoding is properly inverted through round-trip
        $testValues = [0.0, 0.1, 0.3, 0.5, 0.7, 0.9, 1.0];

        foreach ($testValues as $value) {
            $pro1 = new ProPhotoColor($value, $value, $value);
            $srgb = $pro1->toSrgb();
            $pro2 = ProPhotoColor::fromSrgb($srgb);

            // Higher tolerance due to conversions through XYZ, D50/D65 adaptation, and sRGB gamma
            $tolerance = 0.02;
            $this->assertEqualsWithDelta($value, $pro2->r, $tolerance, "Round-trip failed for value {$value}");
            $this->assertEqualsWithDelta($value, $pro2->g, $tolerance, "Round-trip failed for value {$value}");
            $this->assertEqualsWithDelta($value, $pro2->b, $tolerance, "Round-trip failed for value {$value}");
        }
    }

    public function testLinearSectionConsistency(): void
    {
        // Values below 16 * LINEAR_THRESHOLD should use linear transfer
        // Linear section: encoded = 16.0 * linear, decoded = encoded / 16.0
        $lowValue = 0.01; // Well below threshold (0.03125)

        $pro = new ProPhotoColor($lowValue, $lowValue, $lowValue);
        $srgb = $pro->toSrgb();
        $roundTrip = ProPhotoColor::fromSrgb($srgb);

        $tolerance = 0.01;
        $this->assertEqualsWithDelta($lowValue, $roundTrip->r, $tolerance);
        $this->assertEqualsWithDelta($lowValue, $roundTrip->g, $tolerance);
        $this->assertEqualsWithDelta($lowValue, $roundTrip->b, $tolerance);
    }

    public function testLinearThresholdBoundary(): void
    {
        // LINEAR_THRESHOLD = 1/512
        // Test conversion around this threshold in the linear section
        $linearThreshold = 1.0 / 512.0;

        // Test a value at the boundary (16 * LINEAR_THRESHOLD = 0.03125)
        $boundaryValue = $linearThreshold * 16.0;
        $pro1 = new ProPhotoColor($boundaryValue, $boundaryValue, $boundaryValue);
        $srgb = $pro1->toSrgb();
        $pro2 = ProPhotoColor::fromSrgb($srgb);

        // Use higher tolerance for round-trip through multiple color spaces
        $tolerance = 0.02;
        $this->assertEqualsWithDelta($boundaryValue, $pro2->r, $tolerance);
        $this->assertEqualsWithDelta($boundaryValue, $pro2->g, $tolerance);
        $this->assertEqualsWithDelta($boundaryValue, $pro2->b, $tolerance);
    }

    public function testPowerSectionConsistency(): void
    {
        // Values above 16 * LINEAR_THRESHOLD should use power transfer
        // Power: encoded = linear ** (1/1.8), decoded = encoded ** 1.8
        $highValue = 0.5; // Well above threshold

        $pro = new ProPhotoColor($highValue, $highValue, $highValue);
        $srgb = $pro->toSrgb();
        $roundTrip = ProPhotoColor::fromSrgb($srgb);

        $tolerance = 0.02;
        $this->assertEqualsWithDelta($highValue, $roundTrip->r, $tolerance);
        $this->assertEqualsWithDelta($highValue, $roundTrip->g, $tolerance);
        $this->assertEqualsWithDelta($highValue, $roundTrip->b, $tolerance);
    }

    public function testRoundTripConversion(): void
    {
        $original = new SrgbColor(0.7, 0.4, 0.2, 0.25);
        $pro = ProPhotoColor::fromSrgb($original);
        $roundTrip = $pro->toSrgb();

        $tolerance = 0.01;
        $this->assertEqualsWithDelta($original->r, $roundTrip->r, $tolerance);
        $this->assertEqualsWithDelta($original->g, $roundTrip->g, $tolerance);
        $this->assertEqualsWithDelta($original->b, $roundTrip->b, $tolerance);
        $this->assertEqualsWithDelta($original->a, $roundTrip->a, $tolerance);
    }

    public function testRoundTripMaintainsInGamutSrgb(): void
    {
        $original = new SrgbColor(0.84, 0.46, 0.99);
        $pro = ProPhotoColor::fromSrgb($original);
        $roundTrip = $pro->toSrgb();

        $tolerance = 0.001;
        $this->assertEqualsWithDelta($original->r, $roundTrip->r, $tolerance);
        $this->assertEqualsWithDelta($original->g, $roundTrip->g, $tolerance);
        $this->assertEqualsWithDelta($original->b, $roundTrip->b, $tolerance);
    }
}
