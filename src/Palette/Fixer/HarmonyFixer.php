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

namespace PhpColor\Color\Palette\Fixer;

use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\OklchColor;
use PhpColor\Color\Palette\ColorPalette;
use PhpColor\Color\Palette\ColorPaletteInterface;
use PhpColor\Color\Palette\Harmony\HarmonyDetector;
use PhpColor\Color\Palette\Harmony\HarmonyPattern;

/**
 * Fixes color harmony issues in palettes.
 */
final readonly class HarmonyFixer implements PaletteFixerInterface
{
    public function __construct(
        private HarmonyDetector $detector = new HarmonyDetector(),
    ) {
    }

    public function fix(ColorPaletteInterface $palette, array $options = []): ColorPaletteInterface
    {
        $analysis = $this->analyze($palette);

        $targetHarmonyOption = $options['target_harmony'] ?? $analysis['detected_harmony'];

        if (null === $targetHarmonyOption) {
            $targetHarmony = HarmonyPattern::Analogous;
        } elseif ($targetHarmonyOption instanceof HarmonyPattern) {
            $targetHarmony = $targetHarmonyOption;
        } elseif (\is_string($targetHarmonyOption)) {
            $targetHarmony = HarmonyPattern::tryFrom($targetHarmonyOption)
                ?? throw new InvalidColorException(\sprintf('Invalid harmony type "%s".', $targetHarmonyOption));
        } else {
            throw new InvalidColorException('target_harmony option must be a string, HarmonyPattern or null.');
        }

        $preserveLightness = $options['preserve_lightness'] ?? true;
        $preserveChroma = $options['preserve_chroma'] ?? true;
        $baseColorIndex = $options['base_color_index'] ?? 0;

        if (!\is_int($baseColorIndex)) {
            throw new InvalidColorException('base_color_index option must be an integer.');
        }

        return $this->applyHarmony(
            $palette,
            $targetHarmony,
            $baseColorIndex,
            (bool) $preserveLightness,
            (bool) $preserveChroma
        );
    }

    public function analyze(ColorPaletteInterface $palette): array
    {
        $colors = array_values($palette->all());
        $count = \count($colors);

        if ($count < 2) {
            return [
                'detected_harmony' => null,
                'confidence' => 0.0,
                'issues' => ['Palette must have at least 2 colors for harmony analysis'],
                'color_count' => $count,
                'base_hue' => null,
            ];
        }

        $detection = $this->detector->detect($palette);

        return [
            'detected_harmony' => $detection['type'],
            'confidence' => $detection['confidence'],
            'issues' => [],
            'color_count' => $count,
            'base_hue' => $detection['base_hue'],
        ];
    }

    private function applyHarmony(
        ColorPaletteInterface $palette,
        HarmonyPattern $harmony,
        int $baseColorIndex,
        bool $preserveLightness,
        bool $preserveChroma,
    ): ColorPaletteInterface {
        $allColors = $palette->all();
        $colors = array_values($allColors);
        $count = \count($colors);

        if ($baseColorIndex < 0 || $baseColorIndex >= $count) {
            $baseColorIndex = 0;
        }

        $baseColor = $colors[$baseColorIndex];
        $baseOklch = $baseColor->to('oklch');
        if (!$baseOklch instanceof OklchColor) {
            $baseOklch = OklchColor::fromSrgb($baseOklch->toSrgb());
        }

        $angles = $harmony->fullAngles();
        $angleCount = \count($angles);

        $fixedColors = [];
        foreach ($colors as $i => $color) {
            $oklch = $color->to('oklch');
            if (!$oklch instanceof OklchColor) {
                $oklch = OklchColor::fromSrgb($oklch->toSrgb());
            }

            if ($oklch->c < 0.01) {
                $fixedColors[] = $color;
                continue;
            }

            $targetAngle = $angles[$i % $angleCount];
            $newHue = $this->normalizeHue($baseOklch->h + $targetAngle);
            $newL = $preserveLightness ? $oklch->l : $baseOklch->l;
            $newC = $preserveChroma ? $oklch->c : $baseOklch->c;

            $fixedColors[] = new OklchColor($newL, $newC, $newHue, $oklch->alpha);
        }

        if ($palette->isNamed()) {
            $originalKeys = array_keys($allColors);
            $namedColors = [];
            foreach ($originalKeys as $index => $key) {
                $namedColors[(string) $key] = $fixedColors[$index];
            }

            return ColorPalette::named($namedColors);
        }

        return ColorPalette::scale($fixedColors);
    }

    private function normalizeHue(float $hue): float
    {
        $hue = fmod($hue, 360.0);

        return $hue < 0.0 ? $hue + 360.0 : $hue;
    }
}
