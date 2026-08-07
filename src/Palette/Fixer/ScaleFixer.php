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

use PhpColor\Color\ColorInterface;
use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\OklchColor;
use PhpColor\Color\Palette\ColorPalette;
use PhpColor\Color\Palette\ColorPaletteInterface;

/**
 * Fixes irregular lightness progression in ordered palettes.
 *
 * Redistributes Oklch lightness evenly between the two endpoints of a palette
 * that is already ordered as a scale. The entry order and the palette keys are
 * left untouched, so a repaired palette keeps its semantics.
 */
final readonly class ScaleFixer implements PaletteFixerInterface
{
    private const string DIRECTION_AUTO = 'auto';
    private const string DIRECTION_ASCENDING = 'ascending';
    private const string DIRECTION_DESCENDING = 'descending';

    /**
     * Largest step irregularity tolerated before a palette is reported as broken.
     */
    private const float STEP_TOLERANCE = 1.0e-4;

    private const array KNOWN_OPTIONS = [
        'direction',
        'min_lightness',
        'max_lightness',
        'preserve_hue',
        'preserve_chroma',
        'preserve_alpha',
    ];

    /**
     * Rebuild the palette with evenly spaced Oklch lightness values.
     *
     * Supported options:
     *  - direction: "auto" (default), "ascending" or "descending". A forced
     *    direction re-orients the ramp: the entries keep their position, but the
     *    lower lightness bound moves to the end the direction asks for.
     *  - min_lightness: lower Oklch lightness bound, defaults to the darkest endpoint.
     *  - max_lightness: upper Oklch lightness bound, defaults to the lightest endpoint.
     *  - preserve_hue: keep each hue (default true), otherwise apply the first entry hue.
     *  - preserve_chroma: keep each chroma (default true), otherwise apply the first entry chroma.
     *  - preserve_alpha: keep each alpha (default true), otherwise apply the first entry alpha.
     *
     * Palettes with fewer than two colors have no ramp to repair and are returned as is.
     *
     * @param array<string, mixed> $options
     *
     * @throws InvalidColorException If an option is unknown, of the wrong type, or out of range
     */
    public function fix(ColorPaletteInterface $palette, array $options = []): ColorPaletteInterface
    {
        $this->assertKnownOptions($options);

        $direction = $this->resolveDirection($options);
        $minLightness = $this->resolveBound($options, 'min_lightness');
        $maxLightness = $this->resolveBound($options, 'max_lightness');
        $preserveHue = \array_key_exists('preserve_hue', $options) ? (bool) $options['preserve_hue'] : true;
        $preserveChroma = \array_key_exists('preserve_chroma', $options) ? (bool) $options['preserve_chroma'] : true;
        $preserveAlpha = \array_key_exists('preserve_alpha', $options) ? (bool) $options['preserve_alpha'] : true;

        $colors = array_values($palette->all());
        $count = \count($colors);

        if ($count < 2) {
            return [] === $colors ? $palette : $this->rebuild($palette, $colors);
        }

        $oklchColors = array_map(static fn (ColorInterface $c): OklchColor => $c->toOklch(), $colors);
        $base = $oklchColors[0];

        if (self::DIRECTION_AUTO === $direction) {
            $direction = $this->inferDirection($oklchColors);
        }

        $lower = $minLightness ?? min($base->l, $oklchColors[$count - 1]->l);
        $upper = $maxLightness ?? max($base->l, $oklchColors[$count - 1]->l);

        if ($lower > $upper) {
            throw new InvalidColorException(\sprintf('Lightness bounds are inverted: lower bound %.4F is greater than upper bound %.4F.', $lower, $upper));
        }

        [$start, $end] = self::DIRECTION_ASCENDING === $direction ? [$lower, $upper] : [$upper, $lower];

        $fixedColors = [];
        foreach ($oklchColors as $index => $oklch) {
            $t = $index / ($count - 1);

            $fixedColors[] = new OklchColor(
                (1.0 - $t) * $start + $t * $end,
                $preserveChroma ? $oklch->c : $base->c,
                $preserveHue ? $oklch->h : $base->h,
                $preserveAlpha ? $oklch->alpha : $base->alpha,
            );
        }

        return $this->rebuild($palette, $fixedColors);
    }

    /**
     * Report how far the palette lightness ramp is from an even progression.
     *
     * The direction is always inferred from the endpoints, and the expected
     * values are the ones a default fix() call would produce.
     *
     * @return array{
     *     issues: list<string>,
     *     direction: 'ascending'|'descending'|null,
     *     color_count: int,
     *     current_lightness: array<int|string, float>,
     *     expected_lightness: array<int|string, float>,
     *     non_monotonic: list<int|string>,
     *     max_step_deviation: float,
     *     needs_repair: bool,
     * }
     */
    public function analyze(ColorPaletteInterface $palette): array
    {
        $lightness = [];
        foreach ($palette->all() as $key => $color) {
            $lightness[$key] = $color->toOklch()->l;
        }

        $count = \count($lightness);

        if ($count < 2) {
            $rounded = array_map(static fn (float $l): float => round($l, 4), $lightness);

            return [
                'issues' => ['Palette must have at least 2 colors for scale analysis'],
                'direction' => null,
                'color_count' => $count,
                'current_lightness' => $rounded,
                'expected_lightness' => $rounded,
                'non_monotonic' => [],
                'max_step_deviation' => 0.0,
                'needs_repair' => false,
            ];
        }

        $values = array_values($lightness);
        $start = $values[0];
        $end = $values[$count - 1];
        $direction = $end >= $start ? self::DIRECTION_ASCENDING : self::DIRECTION_DESCENDING;
        $evenStep = ($end - $start) / ($count - 1);

        $current = [];
        $expected = [];
        $nonMonotonic = [];
        $maxDeviation = 0.0;
        $index = 0;
        $previous = null;

        foreach ($lightness as $key => $l) {
            $t = $index / ($count - 1);
            $current[$key] = round($l, 4);
            $expected[$key] = round((1.0 - $t) * $start + $t * $end, 4);

            if (null !== $previous) {
                $step = $l - $previous;

                if (self::DIRECTION_ASCENDING === $direction ? $step < 0.0 : $step > 0.0) {
                    $nonMonotonic[] = $key;
                }

                $maxDeviation = max($maxDeviation, abs($step - $evenStep));
            }

            $previous = $l;
            ++$index;
        }

        $issues = [];

        if ([] !== $nonMonotonic) {
            $issues[] = \sprintf('Lightness is not %s at: %s', $direction, implode(', ', array_map(strval(...), $nonMonotonic)));
        }

        if ($maxDeviation > self::STEP_TOLERANCE) {
            $issues[] = \sprintf('Lightness steps deviate by up to %.4F from the even step %.4F', $maxDeviation, $evenStep);
        }

        return [
            'issues' => $issues,
            'direction' => $direction,
            'color_count' => $count,
            'current_lightness' => $current,
            'expected_lightness' => $expected,
            'non_monotonic' => $nonMonotonic,
            'max_step_deviation' => round($maxDeviation, 4),
            'needs_repair' => [] !== $issues,
        ];
    }

    /**
     * @param array<string, mixed> $options
     */
    private function assertKnownOptions(array $options): void
    {
        $unknown = array_diff(array_keys($options), self::KNOWN_OPTIONS);

        if ([] !== $unknown) {
            throw new InvalidColorException(\sprintf('Unknown option(s): %s.', implode(', ', $unknown)));
        }
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return self::DIRECTION_*
     */
    private function resolveDirection(array $options): string
    {
        $direction = $options['direction'] ?? self::DIRECTION_AUTO;

        return match (true) {
            self::DIRECTION_AUTO === $direction => self::DIRECTION_AUTO,
            self::DIRECTION_ASCENDING === $direction => self::DIRECTION_ASCENDING,
            self::DIRECTION_DESCENDING === $direction => self::DIRECTION_DESCENDING,
            default => throw new InvalidColorException('direction option must be one of "auto", "ascending" or "descending".'),
        };
    }

    /**
     * @param array<string, mixed> $options
     */
    private function resolveBound(array $options, string $name): ?float
    {
        $value = $options[$name] ?? null;

        if (null === $value) {
            return null;
        }

        if (!\is_float($value) && !\is_int($value)) {
            throw new InvalidColorException(\sprintf('%s option must be numeric.', $name));
        }

        $bound = (float) $value;

        if ($bound < 0.0 || $bound > 1.0) {
            throw new InvalidColorException(\sprintf('%s option must be between 0.0 and 1.0, %.4F given.', $name, $bound));
        }

        return $bound;
    }

    /**
     * @param non-empty-list<OklchColor> $colors
     *
     * @return self::DIRECTION_ASCENDING|self::DIRECTION_DESCENDING
     */
    private function inferDirection(array $colors): string
    {
        $last = $colors[\count($colors) - 1];

        return $last->l >= $colors[0]->l ? self::DIRECTION_ASCENDING : self::DIRECTION_DESCENDING;
    }

    /**
     * @param list<ColorInterface> $colors
     */
    private function rebuild(ColorPaletteInterface $palette, array $colors): ColorPaletteInterface
    {
        if (!$palette->isNamed()) {
            return ColorPalette::scale($colors);
        }

        $namedColors = [];
        foreach (array_keys($palette->all()) as $index => $key) {
            $namedColors[(string) $key] = $colors[$index];
        }

        return ColorPalette::named($namedColors);
    }
}
