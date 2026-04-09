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

namespace PhpColor\Color\Vision;

use PhpColor\Color\ColorInterface;
use PhpColor\Color\SrgbColor;

/**
 * Simulator for various types of color vision deficiency.
 *
 * Uses transformation matrices to approximate how colors appear to people with
 * different types of color blindness.
 */
final readonly class ColorVisionSimulator implements VisionSimulatorInterface
{
    private const float ANOMALOUS_SEVERITY = 0.6;

    /**
     * @var array<string, array{array{float, float, float}, array{float, float, float}, array{float, float, float}}>
     */
    private const array TRANSFORMATION_MATRICES = [
        'protanopia' => [
            [0.567, 0.433, 0.0],
            [0.558, 0.442, 0.0],
            [0.0, 0.242, 0.758],
        ],
        'deuteranopia' => [
            [0.625, 0.375, 0.0],
            [0.7, 0.3, 0.0],
            [0.0, 0.3, 0.7],
        ],
        'tritanopia' => [
            [0.95, 0.05, 0.0],
            [0.0, 0.433, 0.567],
            [0.0, 0.475, 0.525],
        ],
    ];

    /**
     * Create a new vision simulator instance.
     */
    public function __construct(
        private VisionProfile $profile = VisionProfile::Deuteranomaly,
    ) {
    }

    /**
     * Create a new vision simulator instance for a specific profile.
     */
    public static function create(VisionProfile $profile): self
    {
        return new self($profile);
    }

    /**
     * Get all available vision profiles.
     *
     * @return array<int, VisionProfile>
     */
    public static function profiles(): array
    {
        return VisionProfile::cases();
    }

    public function simulate(ColorInterface $color, ?VisionProfile $profile = null): ColorInterface
    {
        $profile ??= $this->profile;

        return match ($profile) {
            VisionProfile::Protanopia => $this->applyMatrix($color, self::TRANSFORMATION_MATRICES['protanopia']),
            VisionProfile::Deuteranopia => $this->applyMatrix($color, self::TRANSFORMATION_MATRICES['deuteranopia']),
            VisionProfile::Tritanopia => $this->applyMatrix($color, self::TRANSFORMATION_MATRICES['tritanopia']),
            VisionProfile::Protanomaly => $this->applyMatrix($color, $this->adjustMatrixSeverity(self::TRANSFORMATION_MATRICES['protanopia'])),
            VisionProfile::Deuteranomaly => $this->applyMatrix($color, $this->adjustMatrixSeverity(self::TRANSFORMATION_MATRICES['deuteranopia'])),
            VisionProfile::Tritanomaly => $this->applyMatrix($color, $this->adjustMatrixSeverity(self::TRANSFORMATION_MATRICES['tritanopia'])),
            VisionProfile::Monochromacy => $this->simulateMonochromatic($color),
        };
    }

    /**
     * Simulate vision deficiency for multiple colors.
     *
     * @param array<int, ColorInterface> $colors
     *
     * @return array<int, ColorInterface>
     */
    public function simulateAll(array $colors, ?VisionProfile $profile = null): array
    {
        $simulated = [];

        foreach ($colors as $index => $color) {
            $simulated[$index] = $this->simulate($color, $profile);
        }

        return $simulated;
    }

    /**
     * Check if two colors are distinguishable under a given vision profile.
     */
    public function areDistinguishable(
        ColorInterface $first,
        ColorInterface $second,
        ?VisionProfile $profile = null,
        float $threshold = 0.06,
    ): bool {
        $distance = $this->calculateColorDistance(
            $this->simulate($first, $profile),
            $this->simulate($second, $profile)
        );

        return $threshold <= $distance;
    }

    /**
     * Get the current vision profile.
     */
    public function getProfile(): VisionProfile
    {
        return $this->profile;
    }

    /**
     * Apply a transformation matrix to a color.
     *
     * @param array{array{float, float, float}, array{float, float, float}, array{float, float, float}} $matrix
     */
    private function applyMatrix(ColorInterface $color, array $matrix): ColorInterface
    {
        $srgb = $color->toSrgb();
        $alpha = $color->getAlpha();

        $newR = $matrix[0][0] * $srgb->r + $matrix[0][1] * $srgb->g + $matrix[0][2] * $srgb->b;
        $newG = $matrix[1][0] * $srgb->r + $matrix[1][1] * $srgb->g + $matrix[1][2] * $srgb->b;
        $newB = $matrix[2][0] * $srgb->r + $matrix[2][1] * $srgb->g + $matrix[2][2] * $srgb->b;

        return new SrgbColor(
            $this->clamp01($newR),
            $this->clamp01($newG),
            $this->clamp01($newB),
            $alpha
        );
    }

    /**
     * Simulate monochromatic (grayscale) vision.
     */
    private function simulateMonochromatic(ColorInterface $color): ColorInterface
    {
        $srgb = $color->toSrgb();
        $luminance = 0.299 * $srgb->r + 0.587 * $srgb->g + 0.114 * $srgb->b;

        return new SrgbColor($luminance, $luminance, $luminance, $color->getAlpha());
    }

    /**
     * Adjust the severity of a transformation matrix.
     *
     * @param array{array{float, float, float}, array{float, float, float}, array{float, float, float}} $matrix
     *
     * @return array{array{float, float, float}, array{float, float, float}, array{float, float, float}}
     */
    private function adjustMatrixSeverity(array $matrix, float $severity = self::ANOMALOUS_SEVERITY): array
    {
        if (0.0 > $severity) {
            $severity = 0.0;
        }

        if (1.0 < $severity) {
            $severity = 1.0;
        }

        $identity = [
            [1.0, 0.0, 0.0],
            [0.0, 1.0, 0.0],
            [0.0, 0.0, 1.0],
        ];
        $adjusted = [
            [0.0, 0.0, 0.0],
            [0.0, 0.0, 0.0],
            [0.0, 0.0, 0.0],
        ];

        for ($i = 0; $i < 3; ++$i) {
            for ($j = 0; $j < 3; ++$j) {
                $adjusted[$i][$j] = $identity[$i][$j] * (1.0 - $severity) + $matrix[$i][$j] * $severity;
            }
        }

        return $adjusted;
    }

    /**
     * Calculate Euclidean distance between two colors in sRGB space.
     */
    private function calculateColorDistance(ColorInterface $first, ColorInterface $second): float
    {
        $a = $first->toSrgb();
        $b = $second->toSrgb();

        $dr = $a->r - $b->r;
        $dg = $a->g - $b->g;
        $db = $a->b - $b->b;

        return sqrt($dr * $dr + $dg * $dg + $db * $db);
    }

    /**
     * Clamp a value between 0 and 1.
     */
    private function clamp01(float $value): float
    {
        if (0.0 > $value) {
            return 0.0;
        }

        if (1.0 < $value) {
            return 1.0;
        }

        return $value;
    }
}
