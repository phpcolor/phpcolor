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
use PhpColor\Color\ColorInterface;
use PhpColor\Color\XyzColor;

/**
 * Utilities for chromatic adaptation between different white points.
 */
final class Adapt
{
    /**
     * Adapt a color between white points using a specified method.
     */
    public static function color(ColorInterface $color, WhitePoint $src, WhitePoint $dst, string $method = 'bradford'): ColorInterface
    {
        $xyz = $color->to('xyz');
        $xyzColor = $xyz instanceof XyzColor ? $xyz : XyzColor::fromSrgb($xyz->toSrgb());

        $v = [$xyzColor->x, $xyzColor->y, $xyzColor->z];
        $adapted = match (strtolower($method)) {
            'cat16' => CAT16::adaptXYZ($v, $src, $dst),
            default => Bradford::adaptXYZ($v, $src, $dst),
        };

        $xyzOut = new XyzColor($adapted[0], $adapted[1], $adapted[2], $xyzColor->getAlpha());

        return $xyzOut->to($color::getSpaceName());
    }
}
