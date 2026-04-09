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
use PhpColor\Color\Rec2020Color;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Rec2020Color::class)]
final class Rec2020ColorTest extends ColorTestCase
{
    protected function createColor(): ColorInterface
    {
        return new Rec2020Color(0.5, 0.3, 0.1, 0.6);
    }

    protected function getExpectedColorClass(): string
    {
        return Rec2020Color::class;
    }

    public static function provideColorSamples(): iterable
    {
        yield 'vivid purple' => [
            new Rec2020Color(0.7, 0.2, 0.85),
            '#de00e6',
            '#de00e6ff',
            'color(rec2020 0.7 0.2 0.85)',
        ];
        yield 'translucent green' => [
            new Rec2020Color(0.2, 0.8, 0.2, 0.4),
            '#00dc00',
            '#00dc0066',
            'color(rec2020 0.2 0.8 0.2 / 0.4)',
        ];
    }

    public static function provideFromInputs(): iterable
    {
        yield [new SrgbColor(1.0, 0.0, 0.0)];
        yield ['#ff0000'];
        yield ['color(rec2020 0.6 0.2 0.8)'];
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
            ['prophoto-rgb'],
            ['hsl'],
            ['color-srgb'],
        ];
    }

    public function testAlphaPreservationThroughGammaTransfer(): void
    {
        $alphas = [0.0, 0.25, 0.5, 0.75, 1.0];

        foreach ($alphas as $alpha) {
            $rec = new Rec2020Color(0.5, 0.5, 0.5, $alpha);
            $srgb = $rec->toSrgb();
            $roundTrip = Rec2020Color::fromSrgb($srgb);

            $this->assertEqualsWithDelta($alpha, $srgb->a, 0.0001);
            $this->assertEqualsWithDelta($alpha, $roundTrip->a, 0.0001);
        }
    }

    public function testBetaThresholdBoundary(): void
    {
        // Test a value slightly below and above the GAMMA_THRESHOLD (0.08145)
        // which corresponds to approximately 4.5 * BETA in the encoded space
        $rec1 = new Rec2020Color(0.08, 0.08, 0.08);
        $srgb = $rec1->toSrgb();
        $rec2 = Rec2020Color::fromSrgb($srgb);

        // Use higher tolerance for round-trip through multiple color spaces
        $tolerance = 0.02;
        $this->assertEqualsWithDelta($rec1->r, $rec2->r, $tolerance);
        $this->assertEqualsWithDelta($rec1->g, $rec2->g, $tolerance);
        $this->assertEqualsWithDelta($rec1->b, $rec2->b, $tolerance);
    }

    public function testChannelGetters(): void
    {
        $c = new Rec2020Color(0.11, 0.22, 0.33, 0.44);
        $this->assertSame(0.11, $c->getRed());
        $this->assertSame(0.22, $c->getGreen());
        $this->assertSame(0.33, $c->getBlue());
    }

    public function testCssOutputInRec2020Space(): void
    {
        $color = $this->createColor();
        $css = $color->toCss('rec2020');
        $this->assertStringStartsWith('color(rec2020', $css);
    }

    public function testFromSrgbConversion(): void
    {
        $srgb = new SrgbColor(0.25, 0.5, 0.75);
        $rec = Rec2020Color::fromSrgb($srgb);

        $this->assertInstanceOf(Rec2020Color::class, $rec);
        $this->assertGreaterThan(0.0, $rec->r);
        $this->assertGreaterThan(0.0, $rec->g);
        $this->assertGreaterThan(0.0, $rec->b);
    }

    public function testGammaTransferFunctionAboveThreshold(): void
    {
        // Test values above GAMMA_THRESHOLD (0.08145)
        $rec = new Rec2020Color(0.5, 0.5, 0.5);
        $srgb = $rec->toSrgb();

        // Should use power function
        $this->assertGreaterThan(0.0, $srgb->r);
        $this->assertLessThan(1.0, $srgb->r);
    }

    public function testGammaTransferFunctionAtOne(): void
    {
        $rec = new Rec2020Color(1.0, 1.0, 1.0);
        $srgb = $rec->toSrgb();

        $this->assertEqualsWithDelta(1.0, $srgb->r, 0.001);
        $this->assertEqualsWithDelta(1.0, $srgb->g, 0.001);
        $this->assertEqualsWithDelta(1.0, $srgb->b, 0.001);
    }

    public function testGammaTransferFunctionAtZero(): void
    {
        $rec = new Rec2020Color(0.0, 0.0, 0.0);
        $srgb = $rec->toSrgb();

        $this->assertEqualsWithDelta(0.0, $srgb->r, 0.001);
        $this->assertEqualsWithDelta(0.0, $srgb->g, 0.001);
        $this->assertEqualsWithDelta(0.0, $srgb->b, 0.001);
    }

    public function testGammaTransferFunctionBelowThreshold(): void
    {
        // Test values below GAMMA_THRESHOLD (0.08145)
        $rec = new Rec2020Color(0.05, 0.05, 0.05);
        $srgb = $rec->toSrgb();

        // Should use linear section: value / 4.5
        $this->assertGreaterThanOrEqual(0.0, $srgb->r);
        $this->assertLessThan(1.0, $srgb->r);
    }

    public function testGammaTransferFunctionRoundTripAtThreshold(): void
    {
        // Test around GAMMA_THRESHOLD boundary
        $threshold = 0.08145;
        $rec = new Rec2020Color($threshold, $threshold, $threshold);
        $srgb = $rec->toSrgb();
        $roundTrip = Rec2020Color::fromSrgb($srgb);

        $tolerance = 0.001;
        $this->assertEqualsWithDelta($rec->r, $roundTrip->r, $tolerance);
        $this->assertEqualsWithDelta($rec->g, $roundTrip->g, $tolerance);
        $this->assertEqualsWithDelta($rec->b, $roundTrip->b, $tolerance);
    }

    public function testGammaTransferFunctionWithVariousValues(): void
    {
        $testValues = [0.0, 0.01, 0.05, 0.08, 0.1, 0.25, 0.5, 0.75, 0.9, 1.0];

        foreach ($testValues as $value) {
            $rec = new Rec2020Color($value, $value, $value);
            $srgb = $rec->toSrgb();
            $roundTrip = Rec2020Color::fromSrgb($srgb);

            $tolerance = 0.001;
            $this->assertEqualsWithDelta($value, $roundTrip->r, $tolerance, "Failed for value {$value}");
            $this->assertEqualsWithDelta($value, $roundTrip->g, $tolerance, "Failed for value {$value}");
            $this->assertEqualsWithDelta($value, $roundTrip->b, $tolerance, "Failed for value {$value}");
        }
    }

    public function testGammaTransferWithNegativeValuesHandling(): void
    {
        // Rec2020Color constructor clamps values, but test conversion behavior
        $rec = new Rec2020Color(0.0, 0.0, 0.0);
        $srgb = $rec->toSrgb();

        // All channels should be non-negative after conversion
        $this->assertGreaterThanOrEqual(0.0, $srgb->r);
        $this->assertGreaterThanOrEqual(0.0, $srgb->g);
        $this->assertGreaterThanOrEqual(0.0, $srgb->b);
    }

    public function testInverseGammaTransferFunctions(): void
    {
        // Test that Rec2020 gamma encoding is properly inverted through round-trip
        // Note: This goes through color space conversions, so tolerance is higher
        $testValues = [0.0, 0.1, 0.3, 0.5, 0.7, 0.9, 1.0];

        foreach ($testValues as $value) {
            $rec1 = new Rec2020Color($value, $value, $value);
            $srgb = $rec1->toSrgb();
            $rec2 = Rec2020Color::fromSrgb($srgb);

            // Higher tolerance due to double conversion through XYZ and sRGB gamma
            $tolerance = 0.02;
            $this->assertEqualsWithDelta($value, $rec2->r, $tolerance, "Round-trip failed for value {$value}");
            $this->assertEqualsWithDelta($value, $rec2->g, $tolerance, "Round-trip failed for value {$value}");
            $this->assertEqualsWithDelta($value, $rec2->b, $tolerance, "Round-trip failed for value {$value}");
        }
    }

    public function testLinearSectionConsistency(): void
    {
        // Values below GAMMA_THRESHOLD should use linear transfer
        // Linear section: encoded = 4.5 * linear
        $lowValue = 0.01; // Well below threshold

        $rec = new Rec2020Color($lowValue, $lowValue, $lowValue);
        $srgb = $rec->toSrgb();
        $roundTrip = Rec2020Color::fromSrgb($srgb);

        $tolerance = 0.0001;
        $this->assertEqualsWithDelta($lowValue, $roundTrip->r, $tolerance);
        $this->assertEqualsWithDelta($lowValue, $roundTrip->g, $tolerance);
        $this->assertEqualsWithDelta($lowValue, $roundTrip->b, $tolerance);
    }

    public function testLinearToRec2020NegativeBranch(): void
    {
        // Use reflection to cover the negative-value recursion branch
        $ref = new \ReflectionClass(Rec2020Color::class);
        $method = $ref->getMethod('linearToRec2020');

        $value = -0.01; // |value| < BETA -> linear section expected
        $result = $method->invoke(null, $value);

        // For |v| < BETA, encoded = 4.5 * v; negative v should reflect the sign
        $this->assertEqualsWithDelta(4.5 * $value, $result, 1e-9);
    }

    public function testPowerSectionConsistency(): void
    {
        // Values above GAMMA_THRESHOLD should use power transfer
        $highValue = 0.5; // Well above threshold

        $rec = new Rec2020Color($highValue, $highValue, $highValue);
        $srgb = $rec->toSrgb();
        $roundTrip = Rec2020Color::fromSrgb($srgb);

        $tolerance = 0.001;
        $this->assertEqualsWithDelta($highValue, $roundTrip->r, $tolerance);
        $this->assertEqualsWithDelta($highValue, $roundTrip->g, $tolerance);
        $this->assertEqualsWithDelta($highValue, $roundTrip->b, $tolerance);
    }

    #[DataProvider('provideRec2020ToLinearValues')]
    public function testRec2020ToLinear(float $value, float $expected): void
    {
        $ref = new \ReflectionClass(Rec2020Color::class);
        $method = $ref->getMethod('rec2020ToLinear');

        $result = $method->invoke(null, $value);

        $this->assertEqualsWithDelta($expected, $result, 0.0001);
    }

    public static function provideRec2020ToLinearValues(): array
    {
        // GAMMA_THRESHOLD = 0.08145
        // ALPHA = 1.09929682680944
        // BETA = 0.018053968510807
        // GAMMA = 0.45

        return [
            'below threshold (positive)' => [0.04, 0.04 / 4.5],
            'at threshold (positive)' => [0.08145, 0.08145 / 4.5], // Should still be linear
            'above threshold (positive)' => [0.5, ((0.5 + (1.09929682680944 - 1.0)) / 1.09929682680944) ** (1.0 / 0.45)],
            'zero' => [0.0, 0.0],
            'one' => [1.0, ((1.0 + (1.09929682680944 - 1.0)) / 1.09929682680944) ** (1.0 / 0.45)],
            'below threshold (negative)' => [-0.04, -0.04 / 4.5],
            'at threshold (negative)' => [-0.08145, -0.08145 / 4.5],
            'above threshold (negative)' => [-0.5, -(((0.5 + (1.09929682680944 - 1.0)) / 1.09929682680944) ** (1.0 / 0.45))],
        ];
    }

    public function testRoundTripConversion(): void
    {
        $original = new SrgbColor(0.3, 0.6, 0.9, 0.4);
        $rec = Rec2020Color::fromSrgb($original);
        $roundTrip = $rec->toSrgb();

        $tolerance = 0.01;
        $this->assertEqualsWithDelta($original->r, $roundTrip->r, $tolerance);
        $this->assertEqualsWithDelta($original->g, $roundTrip->g, $tolerance);
        $this->assertEqualsWithDelta($original->b, $roundTrip->b, $tolerance);
        $this->assertEqualsWithDelta($original->a, $roundTrip->a, $tolerance);
    }
}
