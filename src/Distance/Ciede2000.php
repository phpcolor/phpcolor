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

namespace PhpColor\Color\Distance;

use PhpColor\Color\ColorInterface;

/**
 * CIEDE2000 color distance algorithm.
 *
 * The CIEDE2000 formula is the most accurate and modern color distance calculation,
 * published by the CIE in 2001. It improves upon previous Delta E formulas by
 * accounting for perceptual non-uniformities in the CIELAB color space.
 */
final readonly class Ciede2000 implements ColorDistanceInterface
{
    /**
     * Create a new CIEDE2000 distance calculator.
     *
     * @param float $kL Weighting factor for lightness (default: 1.0)
     * @param float $kC Weighting factor for chroma (default: 1.0)
     * @param float $kH Weighting factor for hue (default: 1.0)
     */
    public function __construct(
        public float $kL = 1.0,
        public float $kC = 1.0,
        public float $kH = 1.0,
    ) {
    }

    public function calculate(ColorInterface $color1, ColorInterface $color2): float
    {
        $lab1 = $color1->to('lab');
        $lab2 = $color2->to('lab');

        $channels1 = $lab1->getChannels();
        $channels2 = $lab2->getChannels();

        $L1 = $channels1['l'];
        $a1 = $channels1['a'];
        $b1 = $channels1['b'];

        $L2 = $channels2['l'];
        $a2 = $channels2['a'];
        $b2 = $channels2['b'];

        return $this->calculateFromLabInternal($L1, $a1, $b1, $L2, $a2, $b2);
    }

    /**
     * Calculate CIEDE2000 distance from Lab values directly.
     *
     * @internal
     */
    public function calculateFromLab(
        float $L1,
        float $a1,
        float $b1,
        float $L2,
        float $a2,
        float $b2,
    ): float {
        return $this->calculateFromLabInternal($L1, $a1, $b1, $L2, $a2, $b2);
    }

    public function getName(): string
    {
        return 'CIEDE2000';
    }

    /**
     * Internal implementation of the CIEDE2000 formula.
     */
    private function calculateFromLabInternal(
        float $L1,
        float $a1,
        float $b1,
        float $L2,
        float $a2,
        float $b2,
    ): float {
        $C1 = sqrt($a1 * $a1 + $b1 * $b1);
        $C2 = sqrt($a2 * $a2 + $b2 * $b2);
        $C_bar = ($C1 + $C2) / 2.0;

        $C_bar_7 = $C_bar ** 7;
        $G = 0.5 * (1.0 - sqrt($C_bar_7 / ($C_bar_7 + 25.0 ** 7)));

        $a1_prime = (1.0 + $G) * $a1;
        $a2_prime = (1.0 + $G) * $a2;

        $C1_prime = sqrt($a1_prime * $a1_prime + $b1 * $b1);
        $C2_prime = sqrt($a2_prime * $a2_prime + $b2 * $b2);

        $h1_prime = self::calculateHuePrime($b1, $a1_prime);
        $h2_prime = self::calculateHuePrime($b2, $a2_prime);

        $deltaL_prime = $L2 - $L1;
        $deltaC_prime = $C2_prime - $C1_prime;

        $deltah_prime = self::calculateDeltaHuePrime($C1_prime, $C2_prime, $h1_prime, $h2_prime);

        $deltaH_prime = 2.0 * sqrt($C1_prime * $C2_prime) * sin(self::deg2rad($deltah_prime) / 2.0);

        $L_bar_prime = ($L1 + $L2) / 2.0;
        $C_bar_prime = ($C1_prime + $C2_prime) / 2.0;
        $h_bar_prime = self::calculateMeanHuePrime($C1_prime, $C2_prime, $h1_prime, $h2_prime);

        $T = 1.0 - 0.17 * cos(self::deg2rad($h_bar_prime - 30.0))
            + 0.24 * cos(self::deg2rad(2.0 * $h_bar_prime))
            + 0.32 * cos(self::deg2rad(3.0 * $h_bar_prime + 6.0))
            - 0.20 * cos(self::deg2rad(4.0 * $h_bar_prime - 63.0));

        $delta_theta = 30.0 * exp(-(($h_bar_prime - 275.0) / 25.0) ** 2);

        $C_bar_prime_7 = $C_bar_prime ** 7;
        $RC = 2.0 * sqrt($C_bar_prime_7 / ($C_bar_prime_7 + 25.0 ** 7));

        $L_bar_prime_minus_50_sq = ($L_bar_prime - 50.0) ** 2;
        $SL = 1.0 + (0.015 * $L_bar_prime_minus_50_sq) / sqrt(20.0 + $L_bar_prime_minus_50_sq);
        $SC = 1.0 + 0.045 * $C_bar_prime;
        $SH = 1.0 + 0.015 * $C_bar_prime * $T;

        $RT = -sin(self::deg2rad(2.0 * $delta_theta)) * $RC;

        return sqrt(
            ($deltaL_prime / ($this->kL * $SL)) ** 2
            + ($deltaC_prime / ($this->kC * $SC)) ** 2
            + ($deltaH_prime / ($this->kH * $SH)) ** 2
            + $RT * ($deltaC_prime / ($this->kC * $SC)) * ($deltaH_prime / ($this->kH * $SH))
        );
    }

    /**
     * Calculate the delta hue prime component.
     */
    private static function calculateDeltaHuePrime(float $C1_prime, float $C2_prime, float $h1_prime, float $h2_prime): float
    {
        if (0.0 === $C1_prime || 0.0 === $C2_prime) {
            return 0.0;
        }

        $diff = $h2_prime - $h1_prime;

        if (abs($diff) <= 180.0) {
            return $diff;
        }

        if ($diff > 180.0) {
            return $diff - 360.0;
        }

        return $diff + 360.0;
    }

    /**
     * Calculate the hue prime component.
     */
    private static function calculateHuePrime(float $b, float $a_prime): float
    {
        if (0.0 === $b && 0.0 === $a_prime) {
            return 0.0;
        }

        $h = self::rad2deg(atan2($b, $a_prime));

        return $h >= 0.0 ? $h : $h + 360.0;
    }

    /**
     * Calculate the mean hue prime component.
     */
    private static function calculateMeanHuePrime(float $C1_prime, float $C2_prime, float $h1_prime, float $h2_prime): float
    {
        if (0.0 === $C1_prime || 0.0 === $C2_prime) {
            return $h1_prime + $h2_prime;
        }

        $diff = abs($h1_prime - $h2_prime);

        if ($diff <= 180.0) {
            return ($h1_prime + $h2_prime) / 2.0;
        }

        if ($h1_prime + $h2_prime < 360.0) {
            return ($h1_prime + $h2_prime + 360.0) / 2.0;
        }

        return ($h1_prime + $h2_prime - 360.0) / 2.0;
    }

    /**
     * Convert degrees to radians.
     */
    private static function deg2rad(float $degrees): float
    {
        return $degrees * \M_PI / 180.0;
    }

    /**
     * Convert radians to degrees.
     */
    private static function rad2deg(float $radians): float
    {
        return $radians * 180.0 / \M_PI;
    }
}
