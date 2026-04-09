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

namespace PhpColor\Color\Colorimetry\Adaptation;

use PhpColor\Color\Colorimetry\WhitePoint;

/**
 * Bradford chromatic adaptation algorithm.
 *
 * Provides methods to adapt XYZ colors between different white points using
 * the Bradford transform.
 */
final class Bradford
{
    /**
     * Bradford cone response matrix (M).
     */
    private const array M = [
        [0.8951000,  0.2664000, -0.1614000],
        [-0.7502000,  1.7135000,  0.0367000],
        [0.0389000, -0.0685000,  1.0296000],
    ];

    /**
     * Inverse Bradford cone response matrix (M^-1).
     */
    private const array M_INV = [
        [0.9869929, -0.1470543,  0.1599627],
        [0.4323053,  0.5183603,  0.0492912],
        [-0.0085287,  0.0400428,  0.9684867],
    ];

    /**
     * Adapt XYZ from source to destination white point using the Bradford transform.
     *
     * @param array{float,float,float} $xyz   XYZ values
     * @param WhitePoint               $srcWP Source white point
     * @param WhitePoint               $dstWP Destination white point
     *
     * @return array{float,float,float} Adapted XYZ values
     */
    public static function adaptXYZ(array $xyz, WhitePoint $srcWP, WhitePoint $dstWP): array
    {
        if (abs($srcWP->X - $dstWP->X) < 1e-12 && abs($srcWP->Z - $dstWP->Z) < 1e-12) {
            return [$xyz[0], $xyz[1], $xyz[2]];
        }

        $srcCone = self::mul3x3(self::M, [$srcWP->X, $srcWP->Y, $srcWP->Z]);
        $dstCone = self::mul3x3(self::M, [$dstWP->X, $dstWP->Y, $dstWP->Z]);
        $xyzCone = self::mul3x3(self::M, $xyz);

        $scale = [
            $dstCone[0] / $srcCone[0],
            $dstCone[1] / $srcCone[1],
            $dstCone[2] / $srcCone[2],
        ];
        $adaptedCone = [
            $xyzCone[0] * $scale[0],
            $xyzCone[1] * $scale[1],
            $xyzCone[2] * $scale[2],
        ];

        return self::mul3x3(self::M_INV, $adaptedCone);
    }

    /**
     * Multiply a 3x3 matrix with a 3D vector.
     *
     * @param array{array{float,float,float},array{float,float,float},array{float,float,float}} $m
     * @param array{float,float,float}                                                          $v
     *
     * @return array{float,float,float}
     */
    private static function mul3x3(array $m, array $v): array
    {
        return [
            $m[0][0] * $v[0] + $m[0][1] * $v[1] + $m[0][2] * $v[2],
            $m[1][0] * $v[0] + $m[1][1] * $v[1] + $m[1][2] * $v[2],
            $m[2][0] * $v[0] + $m[2][1] * $v[1] + $m[2][2] * $v[2],
        ];
    }
}
