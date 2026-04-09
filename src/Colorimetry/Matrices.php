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

namespace PhpColor\Color\Colorimetry;

/**
 * Canonical transformation matrices for standard color spaces.
 *
 * Provides the fundamental transformation matrices between linear RGB and
 * CIE XYZ color spaces for various industry standards.
 */
final readonly class Matrices
{
    /**
     * linear sRGB to XYZ D65 transformation matrix.
     */
    public const array SRGB_TO_XYZ_D65 = [
        [0.4124564, 0.3575761, 0.1804375],
        [0.2126729, 0.7151522, 0.0721750],
        [0.0193339, 0.1191920, 0.9503041],
    ];

    /**
     * XYZ D65 to linear sRGB transformation matrix.
     */
    public const array XYZ_D65_TO_SRGB = [
        [3.2404542, -1.5371385, -0.4985314],
        [-0.9692660, 1.8760108, 0.0415560],
        [0.0556434, -0.2040259, 1.0572252],
    ];

    /**
     * linear sRGB to linear Display P3 transformation matrix.
     */
    public const array SRGB_LINEAR_TO_DISPLAY_P3_LINEAR = [
        [0.8164966, 0.1907837, 0.0000000],
        [0.0307442, 0.9692558, 0.0000000],
        [0.0154876, 0.0668767, 0.9176357],
    ];

    /**
     * linear Display P3 to linear sRGB transformation matrix.
     */
    public const array DISPLAY_P3_LINEAR_TO_SRGB_LINEAR = [
        [1.2249401, -0.2249404, 0.0000000],
        [-0.0420569, 1.0420571, -0.0000002],
        [-0.0196376, -0.0786361, 1.0982735],
    ];

    /**
     * linear Rec.2020 to XYZ D65 transformation matrix.
     */
    public const array REC2020_TO_XYZ_D65 = [
        [0.6369580483012914, 0.14461690358620832, 0.1688809751641721],
        [0.2627002120112671, 0.6779980715188708, 0.05930171646986196],
        [0.0, 0.028072693049087428, 1.060985057710791],
    ];

    /**
     * XYZ D65 to linear Rec.2020 transformation matrix.
     */
    public const array XYZ_D65_TO_REC2020 = [
        [1.716651187971268, -0.355670783776392, -0.25336628137365995],
        [-0.666684351832489, 1.6164812366349395, 0.0157685458139111],
        [0.017639857445311, -0.0427706132578085, 0.9421031212354739],
    ];

    /**
     * linear Adobe RGB (1998) to XYZ D65 transformation matrix.
     */
    public const array A98_RGB_TO_XYZ_D65 = [
        [0.5766690429101305, 0.1855582379065463, 0.1882286462349947],
        [0.29734497525053605, 0.6273635662554661, 0.07529145849399788],
        [0.02703136138641234, 0.07068885253582723, 0.9913375368376388],
    ];

    /**
     * XYZ D65 to linear Adobe RGB (1998) transformation matrix.
     */
    public const array XYZ_D65_TO_A98_RGB = [
        [2.041369046870695, -0.5649463723589435, -0.34469438437784876],
        [-0.9692660305051868, 1.8760108454466942, 0.041556017530349834],
        [0.013447387226917042, -0.11838974235411726, 1.0154096305796205],
    ];

    /**
     * linear ProPhoto RGB to XYZ D50 transformation matrix.
     */
    public const array PROPHOTO_RGB_TO_XYZ_D50 = [
        [0.7977604896723027, 0.13518583717574031, 0.0313493495815248],
        [0.2880711282292934, 0.7118432178101014, 0.00008565396060525902],
        [0.0, 0.0, 0.8251046025104601],
    ];

    /**
     * XYZ D50 to linear ProPhoto RGB transformation matrix.
     */
    public const array XYZ_D50_TO_PROPHOTO_RGB = [
        [1.3459433009386652, -0.25560750931683004, -0.05111183071848621],
        [-0.5445989641976783, 1.508167019877345, 0.02053510919291806],
        [0.0, 0.0, 1.2118127506937628],
    ];

    /**
     * Verify that a matrix and its inverse form an identity when multiplied.
     *
     * @param array<int, array<int, float>> $m         The forward matrix
     * @param array<int, array<int, float>> $mInv      The inverse matrix
     * @param float                         $tolerance Maximum allowed error (default 1e-6)
     */
    public static function verifyInverse(array $m, array $mInv, float $tolerance = 1e-6): bool
    {
        $result = self::mul3x3($m, $mInv);

        $identity = [
            [1.0, 0.0, 0.0],
            [0.0, 1.0, 0.0],
            [0.0, 0.0, 1.0],
        ];

        for ($i = 0; $i < 3; ++$i) {
            for ($j = 0; $j < 3; ++$j) {
                if (abs($result[$i][$j] - $identity[$i][$j]) > $tolerance) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Multiply two 3x3 matrices.
     *
     * @param array<int, array<int, float>> $m1 First matrix
     * @param array<int, array<int, float>> $m2 Second matrix
     *
     * @return array<int, array<int, float>> Result matrix
     */
    private static function mul3x3(array $m1, array $m2): array
    {
        return [
            [
                $m1[0][0] * $m2[0][0] + $m1[0][1] * $m2[1][0] + $m1[0][2] * $m2[2][0],
                $m1[0][0] * $m2[0][1] + $m1[0][1] * $m2[1][1] + $m1[0][2] * $m2[2][1],
                $m1[0][0] * $m2[0][2] + $m1[0][1] * $m2[1][2] + $m1[0][2] * $m2[2][2],
            ],
            [
                $m1[1][0] * $m2[0][0] + $m1[1][1] * $m2[1][0] + $m1[1][2] * $m2[2][0],
                $m1[1][0] * $m2[0][1] + $m1[1][1] * $m2[1][1] + $m1[1][2] * $m2[2][1],
                $m1[1][0] * $m2[0][2] + $m1[1][1] * $m2[1][2] + $m1[1][2] * $m2[2][2],
            ],
            [
                $m1[2][0] * $m2[0][0] + $m1[2][1] * $m2[1][0] + $m1[2][2] * $m2[2][0],
                $m1[2][0] * $m2[0][1] + $m1[2][1] * $m2[1][1] + $m1[2][2] * $m2[2][1],
                $m1[2][0] * $m2[0][2] + $m1[2][1] * $m2[1][2] + $m1[2][2] * $m2[2][2],
            ],
        ];
    }
}
