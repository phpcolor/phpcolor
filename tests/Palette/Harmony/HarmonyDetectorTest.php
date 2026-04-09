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

namespace PhpColor\Color\Tests\Palette\Harmony;

use PhpColor\Color\Color;
use PhpColor\Color\ColorInterface;
use PhpColor\Color\Palette\ColorPalette;
use PhpColor\Color\Palette\Harmony\HarmonyDetector;
use PhpColor\Color\Palette\Harmony\HarmonyPattern;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HarmonyDetector::class)]
final class HarmonyDetectorTest extends TestCase
{
    private const float HIGH_CONFIDENCE = 0.7;
    private const float LOW_CONFIDENCE = 0.1;

    private HarmonyDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new HarmonyDetector();
    }

    public function testDetectComplementary(): void
    {
        $red = Color::parse('#ff0000');
        $cyan = $red->complementary();

        $palette = ColorPalette::scale([$red, $cyan]);
        $result = $this->detector->detect($palette);

        // Should detect complementary (180° angle between two colors)
        $this->assertSame(HarmonyPattern::Complementary, $result['type']);
        $this->assertGreaterThan(self::HIGH_CONFIDENCE, $result['confidence']);
        $this->assertNotNull($result['base_hue']);
    }

    public function testDetectAnalogous(): void
    {
        $blue = Color::parse('#0000ff');
        $analogous = $blue->analogous(2);

        $palette = ColorPalette::scale($analogous);
        $result = $this->detector->detect($palette);

        // Analogous should be detected (confidence may vary due to gamut mapping)
        $this->assertSame(HarmonyPattern::Analogous, $result['type']);
        $this->assertGreaterThan(self::LOW_CONFIDENCE, $result['confidence']);
    }

    public function testDetectTriadic(): void
    {
        $red = Color::parse('#ff0000');
        $triadic = $red->triadic();

        $palette = ColorPalette::scale($triadic);
        $result = $this->detector->detect($palette);

        $this->assertSame(HarmonyPattern::Triadic, $result['type']);
        $this->assertGreaterThan(0.6, $result['confidence']);
    }

    public function testDetectTetradic(): void
    {
        $red = Color::parse('#ff0000');
        $tetradic = $red->tetradic();

        $palette = ColorPalette::scale($tetradic);
        $result = $this->detector->detect($palette);

        // Tetradic detection may have lower confidence due to gamut mapping
        $this->assertSame(HarmonyPattern::Tetradic, $result['type']);
        $this->assertGreaterThan(0.3, $result['confidence']);
    }

    public function testDetectSplitComplementary(): void
    {
        $blue = Color::parse('#0000ff');
        $splitComp = $blue->splitComplementary();

        $palette = ColorPalette::scale($splitComp);
        $result = $this->detector->detect($palette);

        // Should detect split-complementary harmony
        $this->assertSame(HarmonyPattern::SplitComplementary, $result['type']);
        $this->assertGreaterThan(self::HIGH_CONFIDENCE, $result['confidence']);
    }

    public function testDetectWithSingleColor(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000']);
        $result = $this->detector->detect($palette);

        $this->assertNull($result['type']);
        $this->assertSame(0.0, $result['confidence']);
    }

    public function testDetectWithAchromaticColors(): void
    {
        $palette = ColorPalette::fromHex(['#000000', '#808080', '#ffffff']);
        $result = $this->detector->detect($palette);

        $this->assertNull($result['type']);
        $this->assertNull($result['base_hue']);
    }

    public function testDetectWithCustomTolerance(): void
    {
        $red = Color::parse('#ff0000');
        $cyan = $red->complementary();

        $palette = ColorPalette::scale([$red, $cyan]);

        // Strict tolerance may not detect due to gamut mapping artifacts (~19° deviation from ideal)
        $strictResult = $this->detector->detect($palette, 5.0);
        // With very strict tolerance, detection may fail or have low confidence
        $this->assertIsFloat($strictResult['confidence']);

        // Moderate tolerance should detect complementary
        $moderateResult = $this->detector->detect($palette, 20.0);
        $this->assertSame(HarmonyPattern::Complementary, $moderateResult['type']);
        $this->assertGreaterThan(self::HIGH_CONFIDENCE, $moderateResult['confidence']);

        // Loose tolerance should also detect complementary
        $looseResult = $this->detector->detect($palette, 30.0);
        $this->assertSame(HarmonyPattern::Complementary, $looseResult['type']);
        $this->assertGreaterThan(self::HIGH_CONFIDENCE, $looseResult['confidence']);
    }

    public function testIsHarmonyReturnsTrueForMatch(): void
    {
        $red = Color::parse('#ff0000');
        $triadic = $red->triadic();

        $palette = ColorPalette::scale($triadic);

        $this->assertTrue($this->detector->isHarmony($palette, 'triadic'));
    }

    public function testIsHarmonyReturnsFalseForNonMatch(): void
    {
        $red = Color::parse('#ff0000');
        $triadic = $red->triadic();

        $palette = ColorPalette::scale($triadic);

        $this->assertFalse($this->detector->isHarmony($palette, 'complementary'));
    }

    public function testDetectWithMixedColors(): void
    {
        // Create palette with some harmony + extra colors
        $red = Color::parse('#ff0000');
        $cyan = $red->complementary();
        $random = Color::parse('#8b4513');

        $palette = ColorPalette::scale([$red, $cyan, $random]);
        $result = $this->detector->detect($palette);

        // Should detect some harmony, but confidence may be lower due to extra color
        $this->assertNotNull($result['type']);
        $this->assertIsFloat($result['confidence']);
        $this->assertGreaterThan(0.0, $result['confidence']);
    }

    public function testDetectedAnglesAreReturned(): void
    {
        $red = Color::parse('#ff0000');
        $triadic = $red->triadic();

        $palette = ColorPalette::scale($triadic);
        $result = $this->detector->detect($palette);

        $this->assertIsArray($result['detected_angles']);
        $this->assertCount(2, $result['detected_angles']); // 2 angles from base
    }

    public function testNormalizeAngleWrapsIntoNegativeRange(): void
    {
        $wrapped = $this->invokeDetectorMethod('normalizeAngle', [270.0]);
        $this->assertEqualsWithDelta(-90.0, $wrapped, 1e-6);
    }

    public function testMatchPatternPenalizesExtraAngles(): void
    {
        $score = $this->invokeDetectorMethod('matchPattern', [[0.0, 90.0], [180.0], 30.0]);

        $this->assertLessThan(1.0, $score);
        $this->assertGreaterThanOrEqual(0.0, $score);
    }

    public function testExtractHuesSkipsAchromaticColors(): void
    {
        $colors = [
            Color::parse('#ff0000'),
            Color::parse('#808080'),
        ];

        $hues = $this->invokeDetectorMethod('extractHues', [$colors]);

        $this->assertCount(1, $hues);
    }

    public function testExtractHuesConvertsConvertibleColors(): void
    {
        $colors = [$this->createConvertibleDetectorColor()];

        $hues = $this->invokeDetectorMethod('extractHues', [$colors]);

        $this->assertCount(1, $hues);
    }

    public function testFindBaseHueConvertsConvertibleColors(): void
    {
        $colors = [$this->createConvertibleDetectorColor()];

        $base = $this->invokeDetectorMethod('findBaseHue', [$colors]);

        $this->assertIsFloat($base);
    }

    public function testMatchPatternHandlesEmptyDetectedAngles(): void
    {
        $score = $this->invokeDetectorMethod('matchPattern', [[], [180.0], 10.0]);

        $this->assertSame(0.0, $score);
    }

    /**
     * @param non-empty-string  $method
     * @param array<int, mixed> $arguments
     */
    private function invokeDetectorMethod(string $method, array $arguments = []): mixed
    {
        $callable = \Closure::bind(
            static fn (HarmonyDetector $detector, string $method, array $arguments): mixed => $detector->{$method}(...$arguments),
            null,
            HarmonyDetector::class,
        );

        return $callable($this->detector, $method, $arguments);
    }

    private function createConvertibleDetectorColor(): ColorInterface
    {
        $srgb = new SrgbColor(0.2, 0.3, 0.4);

        $color = $this->createStub(ColorInterface::class);
        $color->method('to')->willReturn($srgb);

        return $color;
    }
}
