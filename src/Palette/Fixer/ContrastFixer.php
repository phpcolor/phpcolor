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

use PhpColor\Color\Color;
use PhpColor\Color\ColorInterface;
use PhpColor\Color\Exception\InvalidArgumentException;
use PhpColor\Color\OklchColor;
use PhpColor\Color\Palette\ColorPalette;
use PhpColor\Color\Palette\ColorPaletteInterface;

/**
 * Fixes contrast issues in palettes for accessibility.
 *
 * Ensures colors meet WCAG contrast requirements against a background color
 * by adjusting lightness. preserve_hue and preserve_chroma default to true
 * (lightness only); set either to false to let that dimension relax when a
 * fixed-lightness-only search cannot reach the target within max_iterations.
 */
final class ContrastFixer implements PaletteFixerInterface
{
    /**
     * WCAG contrast ratio thresholds.
     */
    private const float WCAG_AA = 4.5;
    private const float WCAG_AAA = 7.0;

    public function fix(ColorPaletteInterface $palette, array $options = []): ColorPaletteInterface
    {
        $minContrast = self::WCAG_AA;
        if (\array_key_exists('min_contrast', $options)) {
            $minContrastOption = $options['min_contrast'];
            if (!\is_float($minContrastOption) && !\is_int($minContrastOption)) {
                throw new InvalidArgumentException('min_contrast option must be numeric.');
            }
            $minContrast = (float) $minContrastOption;
        }

        $background = $this->parseBackgroundColor($options['background_color'] ?? '#ffffff');
        $adjustMode = isset($options['adjust_mode']) && \is_string($options['adjust_mode']) ? $options['adjust_mode'] : 'auto';
        $preserveHue = \array_key_exists('preserve_hue', $options) ? (bool) $options['preserve_hue'] : true;
        $preserveChroma = \array_key_exists('preserve_chroma', $options) ? (bool) $options['preserve_chroma'] : true;

        $maxIterations = 20;
        if (\array_key_exists('max_iterations', $options)) {
            $maxIterationsOption = $options['max_iterations'];
            if (!\is_int($maxIterationsOption)) {
                throw new InvalidArgumentException('max_iterations option must be an integer.');
            }
            $maxIterations = $maxIterationsOption;
        }

        $colors = $palette->all();
        $fixedColors = [];

        foreach ($colors as $key => $color) {
            $currentContrast = Color::contrast($color, $background);

            if ($currentContrast >= $minContrast) {
                $fixedColors[$key] = $color;

                continue;
            }

            $fixed = $this->adjustColorContrast(
                $color,
                $background,
                $minContrast,
                $adjustMode,
                $preserveHue,
                $preserveChroma,
                $maxIterations
            );

            $fixedColors[$key] = $fixed;
        }

        if ($palette->isNamed()) {
            /** @var array<string, ColorInterface> $namedColors */
            $namedColors = [];
            foreach ($fixedColors as $key => $color) {
                if (!\is_string($key)) {
                    throw new InvalidArgumentException('Named palettes must use string keys.');
                }
                $namedColors[$key] = $color;
            }

            return ColorPalette::named($namedColors);
        }

        return ColorPalette::scale(array_values($fixedColors));
    }

    public function analyze(ColorPaletteInterface $palette): array
    {
        $background = Color::parse('#ffffff');
        $issues = [];
        $aaFailures = 0;
        $aaaFailures = 0;

        foreach ($palette->all() as $key => $color) {
            $contrast = Color::contrast($color, $background);

            if ($contrast < self::WCAG_AA) {
                ++$aaFailures;
                $issues[] = [
                    'color_index' => $key,
                    'current_contrast' => round($contrast, 2),
                    'required_contrast' => self::WCAG_AA,
                    'severity' => 'wcag_aa_fail',
                ];
            } elseif ($contrast < self::WCAG_AAA) {
                ++$aaaFailures;
                $issues[] = [
                    'color_index' => $key,
                    'current_contrast' => round($contrast, 2),
                    'required_contrast' => self::WCAG_AAA,
                    'severity' => 'wcag_aaa_fail',
                ];
            }
        }

        return [
            'issues' => $issues,
            'background_color' => $background->toHex(),
            'wcag_aa_failures' => $aaFailures,
            'wcag_aaa_failures' => $aaaFailures,
        ];
    }

    /**
     * Adjust a color to meet contrast requirements.
     *
     * Always tries the original chroma and hue first. If that fails within
     * $maxIterations and $preserveChroma is false, it retries fully
     * desaturated. If that also fails (or was skipped) and $preserveHue is
     * false, it sweeps hue offsets, nearest first, at the original chroma.
     * The first passing candidate wins; otherwise the best-effort result
     * closest to what was actually tried is returned.
     *
     * @param ColorInterface $color          Color to adjust
     * @param ColorInterface $background     Background color
     * @param float          $minContrast    Target contrast ratio
     * @param string         $adjustMode     'lighten', 'darken', or 'auto'
     * @param bool           $preserveHue    Keep original hue
     * @param bool           $preserveChroma Keep original chroma
     * @param int            $maxIterations  Maximum attempts per candidate
     */
    private function adjustColorContrast(
        ColorInterface $color,
        ColorInterface $background,
        float $minContrast,
        string $adjustMode,
        bool $preserveHue,
        bool $preserveChroma,
        int $maxIterations,
    ): ColorInterface {
        $oklch = $color->to('oklch');
        if (!$oklch instanceof OklchColor) {
            $oklch = OklchColor::fromSrgb($oklch->toSrgb());
        }

        $bgLuminance = $background->getLuminance();

        $shouldLighten = match ($adjustMode) {
            'lighten' => true,
            'darken' => false,
            'auto' => $bgLuminance < 0.5,
            default => $bgLuminance < 0.5,
        };

        [$best, $passed] = $this->searchLightness($oklch, $oklch->c, $oklch->h, $background, $minContrast, $shouldLighten, $maxIterations);
        if ($passed) {
            return $best;
        }

        if (!$preserveChroma) {
            [$relaxed, $relaxedPassed] = $this->searchLightness($oklch, 0.0, $oklch->h, $background, $minContrast, $shouldLighten, $maxIterations);
            if ($relaxedPassed) {
                return $relaxed;
            }
            $best = $relaxed;
        }

        if (!$preserveHue) {
            foreach (self::hueSearchOffsets() as $offset) {
                [$candidate, $candidatePassed] = $this->searchLightness($oklch, $oklch->c, $oklch->h + $offset, $background, $minContrast, $shouldLighten, $maxIterations);
                if ($candidatePassed) {
                    return $candidate;
                }
            }
        }

        return $best;
    }

    /**
     * Step lightness from $source->l toward black or white at a fixed
     * chroma and hue, returning the first candidate that reaches
     * $minContrast, or the best-effort result if none does.
     *
     * @return array{0: OklchColor, 1: bool}
     */
    private function searchLightness(
        OklchColor $source,
        float $chroma,
        float $hue,
        ColorInterface $background,
        float $minContrast,
        bool $shouldLighten,
        int $maxIterations,
    ): array {
        $step = $shouldLighten ? 0.05 : -0.05;
        $newL = $source->l;
        $iterations = 0;

        while ($iterations < $maxIterations) {
            $testColor = new OklchColor($newL, $chroma, $hue, $source->alpha);

            if (Color::contrast($testColor, $background) >= $minContrast) {
                return [$testColor, true];
            }

            $newL += $step;

            if ($newL <= 0.0 || $newL >= 1.0) {
                $newL = max(0.0, min(1.0, $newL));

                break;
            }

            ++$iterations;
        }

        return [new OklchColor(max(0.0, min(1.0, $newL)), $chroma, $hue, $source->alpha), false];
    }

    /**
     * Hue offsets in degrees, nearest to farthest, both directions.
     *
     * @return list<float>
     */
    private static function hueSearchOffsets(): array
    {
        $offsets = [];
        for ($deg = 15.0; $deg < 180.0; $deg += 15.0) {
            $offsets[] = $deg;
            $offsets[] = -$deg;
        }
        $offsets[] = 180.0;

        return $offsets;
    }

    /**
     * Parse background color from various input formats.
     */
    private function parseBackgroundColor(mixed $background): ColorInterface
    {
        if ($background instanceof ColorInterface) {
            return $background;
        }

        if (\is_string($background)) {
            return Color::parse($background);
        }

        return Color::parse('#ffffff');
    }
}
