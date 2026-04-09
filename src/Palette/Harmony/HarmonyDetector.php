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

namespace PhpColor\Color\Palette\Harmony;

use PhpColor\Color\ColorInterface;
use PhpColor\Color\OklchColor;
use PhpColor\Color\Palette\ColorPaletteInterface;

/**
 * Detects color harmony patterns in palettes.
 *
 * Analyzes the hue relationships between colors to identify
 * complementary, analogous, triadic, tetradic, or split-complementary harmonies.
 */
final class HarmonyDetector
{
    /**
     * Default tolerance for angle matching (degrees).
     * Increased to 25° to account for gamut mapping artifacts when colors
     * are converted between color spaces (especially sRGB ↔ Oklch).
     */
    private const float DEFAULT_TOLERANCE = 25.0;

    /**
     * Detect the harmony type in a palette.
     *
     * @param ColorPaletteInterface $palette   The palette to analyze
     * @param float                 $tolerance Angle tolerance in degrees (default: 25)
     *
     * @return array{type: HarmonyPattern|null, confidence: float, base_hue: float|null, detected_angles: array<float>}
     */
    public function detect(ColorPaletteInterface $palette, float $tolerance = self::DEFAULT_TOLERANCE): array
    {
        $colors = array_values($palette->all());
        $count = \count($colors);

        if ($count < 2) {
            return [
                'type' => null,
                'confidence' => 0.0,
                'base_hue' => null,
                'detected_angles' => [],
            ];
        }

        $hues = $this->extractHues($colors);
        $baseHue = $this->findBaseHue($colors);

        if (null === $baseHue) {
            return [
                'type' => null,
                'confidence' => 0.0,
                'base_hue' => null,
                'detected_angles' => [],
            ];
        }

        $angles = [];
        foreach ($hues as $hue) {
            $angle = $this->normalizeAngle($hue - $baseHue);
            if (abs($angle) > 0.1) {
                $angles[] = $angle;
            }
        }

        sort($angles);

        $bestMatch = null;
        $bestScore = 0.0;

        foreach (HarmonyPattern::cases() as $pattern) {
            $score = $this->matchPattern($angles, $pattern->angles(), $tolerance);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $pattern;
            }
        }

        return [
            'type' => $bestMatch,
            'confidence' => $bestScore,
            'base_hue' => $baseHue,
            'detected_angles' => $angles,
        ];
    }

    /**
     * Check if a palette follows a specific harmony type.
     *
     * @param ColorPaletteInterface $palette   The palette to check
     * @param HarmonyPattern|string $type      Harmony type to check for
     * @param float                 $tolerance Angle tolerance in degrees
     *
     * @return bool True if the palette matches the harmony type
     */
    public function isHarmony(ColorPaletteInterface $palette, HarmonyPattern|string $type, float $tolerance = self::DEFAULT_TOLERANCE): bool
    {
        if (\is_string($type)) {
            $type = HarmonyPattern::tryFrom($type) ?? throw new \InvalidArgumentException(\sprintf('Invalid harmony type "%s".', $type));
        }

        $result = $this->detect($palette, $tolerance);

        return $result['type'] === $type && $result['confidence'] >= 0.7;
    }

    /**
     * Extract hues from an array of colors.
     *
     * @param array<ColorInterface> $colors
     *
     * @return array<float>
     */
    private function extractHues(array $colors): array
    {
        $hues = [];

        foreach ($colors as $color) {
            $oklch = $color->to('oklch');
            if (!$oklch instanceof OklchColor) {
                $oklch = OklchColor::fromSrgb($oklch->toSrgb());
            }

            if ($oklch->c > 0.01) {
                $hues[] = $oklch->h;
            }
        }

        return $hues;
    }

    /**
     * Find the base hue from an array of colors.
     *
     * @param array<ColorInterface> $colors
     */
    private function findBaseHue(array $colors): ?float
    {
        foreach ($colors as $color) {
            $oklch = $color->to('oklch');
            if (!$oklch instanceof OklchColor) {
                $oklch = OklchColor::fromSrgb($oklch->toSrgb());
            }

            if ($oklch->c > 0.01) {
                return $oklch->h;
            }
        }

        return null;
    }

    /**
     * Match detected angles against a harmony pattern and return a confidence score.
     *
     * @param array<float> $detectedAngles Sorted array of detected angles
     * @param array<float> $pattern        Expected angles for this harmony
     * @param float        $tolerance      Matching tolerance in degrees
     *
     * @return float Confidence score (0.0 to 1.0)
     */
    private function matchPattern(array $detectedAngles, array $pattern, float $tolerance): float
    {
        $patternCount = \count($pattern);
        $detectedCount = \count($detectedAngles);

        if (0 === $patternCount || 0 === $detectedCount) {
            return 0.0;
        }

        $normalizedPattern = array_map($this->normalizeAngle(...), $pattern);
        sort($normalizedPattern);

        $matches = 0;
        $used = [];

        foreach ($normalizedPattern as $patternAngle) {
            foreach ($detectedAngles as $i => $detectedAngle) {
                if (\in_array($i, $used, true)) {
                    continue;
                }

                $diff = abs($this->normalizeAngle($detectedAngle - $patternAngle));

                if ($diff <= $tolerance) {
                    ++$matches;
                    $used[] = $i;

                    break;
                }
            }
        }

        $matchRatio = $matches / $patternCount;

        $extraPenalty = max(0, $detectedCount - $patternCount) * 0.2;

        $missingPenalty = ($patternCount - $matches) * 0.3;

        $countMatchBonus = ($detectedCount === $patternCount && $matches === $patternCount) ? 0.2 : 0.0;

        return max(0.0, min(1.0, $matchRatio - $extraPenalty - $missingPenalty + $countMatchBonus));
    }

    /**
     * Normalize an angle to the range [-180, 180].
     */
    private function normalizeAngle(float $angle): float
    {
        $angle = fmod($angle, 360.0);

        if ($angle > 180.0) {
            $angle -= 360.0;
        } elseif ($angle < -180.0) {
            $angle += 360.0;
        }

        return $angle;
    }
}
