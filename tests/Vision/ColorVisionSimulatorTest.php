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

namespace PhpColor\Color\Tests\Vision;

use PhpColor\Color\Color;
use PhpColor\Color\ColorInterface;
use PhpColor\Color\Vision\ColorVisionSimulator;
use PhpColor\Color\Vision\VisionProfile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ColorVisionSimulator::class)]
final class ColorVisionSimulatorTest extends TestCase
{
    private ColorVisionSimulator $simulator;

    protected function setUp(): void
    {
        $this->simulator = new ColorVisionSimulator();
    }

    public function testSimulateRedWithProtanProfileReducesRedChannel(): void
    {
        $red = Color::hex('#ff0000');
        $simulated = $this->simulator->simulate($red, VisionProfile::Protanopia);

        $this->assertLessThan($red->toSrgb()->r, $simulated->toSrgb()->r);
    }

    public function testMonochromaticSimulationProducesEqualChannels(): void
    {
        $colorful = Color::hex('#ff6b35')->withAlpha(0.5);
        $simulated = $this->simulator->simulate($colorful, VisionProfile::Monochromacy);
        $srgb = $simulated->toSrgb();

        $this->assertEqualsWithDelta($srgb->r, $srgb->g, 1e-12);
        $this->assertEqualsWithDelta($srgb->g, $srgb->b, 1e-12);
        $this->assertSame($colorful->getAlpha(), $simulated->getAlpha());
    }

    public function testSimulateAllPreservesCountAndTypes(): void
    {
        $colors = [
            Color::hex('#ff0000'),
            Color::hex('#00ff00'),
            Color::hex('#0000ff'),
        ];

        $simulated = $this->simulator->simulateAll($colors, VisionProfile::Deuteranopia);

        $this->assertCount(3, $simulated);
        array_walk($simulated, fn (ColorInterface $color) => $this->assertInstanceOf(ColorInterface::class, $color));
    }

    public function testAreDistinguishableHonorsThreshold(): void
    {
        $red = Color::hex('#ff0000');
        $green = Color::hex('#00ff00');
        $similarRed = Color::hex('#f60000');

        $this->assertTrue($this->simulator->areDistinguishable($red, $green, VisionProfile::Deuteranopia));
        $this->assertFalse($this->simulator->areDistinguishable($red, $similarRed, VisionProfile::Protanopia, 0.5));
    }

    public function testAnomalousProfilesStayCloserToOriginal(): void
    {
        $color = Color::hex('#ff0000');

        $severe = $this->simulator->simulate($color, VisionProfile::Protanopia)->toSrgb();
        $partial = $this->simulator->simulate($color, VisionProfile::Protanomaly)->toSrgb();
        $original = $color->toSrgb();

        $severeDelta = abs($original->r - $severe->r);
        $partialDelta = abs($original->r - $partial->r);

        $this->assertLessThan($severeDelta, $partialDelta);
    }

    public function testProfileOverrideDoesNotChangeDefault(): void
    {
        $this->simulator->simulate(Color::hex('#ff0000'), VisionProfile::Tritanopia);

        $this->assertSame(VisionProfile::Deuteranomaly, $this->simulator->getProfile());
    }

    public function testProfilesHelperMatchesEnumCases(): void
    {
        $this->assertSame(VisionProfile::cases(), ColorVisionSimulator::profiles());
    }

    public function testCreate(): void
    {
        $simulator = ColorVisionSimulator::create(VisionProfile::Deuteranopia);
        $this->assertInstanceOf(ColorVisionSimulator::class, $simulator);
        $this->assertSame(VisionProfile::Deuteranopia, $simulator->getProfile());
    }

    public function testClamp01(): void
    {
        $reflection = new \ReflectionClass(ColorVisionSimulator::class);
        $method = $reflection->getMethod('clamp01');
        $simulator = new ColorVisionSimulator();

        $this->assertSame(0.0, $method->invoke($simulator, -0.1));
        $this->assertSame(1.0, $method->invoke($simulator, 1.1));
        $this->assertSame(0.5, $method->invoke($simulator, 0.5));
    }

    public function testAdjustMatrixSeverityBoundaries(): void
    {
        $reflection = new \ReflectionClass(ColorVisionSimulator::class);
        $method = $reflection->getMethod('adjustMatrixSeverity');
        $simulator = new ColorVisionSimulator();

        $matrix = [[0.0, 0.0, 0.0], [0.0, 0.0, 0.0], [0.0, 0.0, 0.0]];

        // Test severity < 0
        $resultLow = $method->invoke($simulator, $matrix, -0.1);
        // Severity clamped to 0.0 -> Identity matrix
        $expectedIdentity = [
            [1.0, 0.0, 0.0],
            [0.0, 1.0, 0.0],
            [0.0, 0.0, 1.0],
        ];
        $this->assertEquals($expectedIdentity, $resultLow);

        // Test severity > 1
        $resultHigh = $method->invoke($simulator, $matrix, 1.1);
        // Severity clamped to 1.0 -> Original matrix
        $this->assertEquals($matrix, $resultHigh);
    }
}
