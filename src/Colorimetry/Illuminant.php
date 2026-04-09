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
 * Standard illuminants with Y normalized to 1.0.
 *
 * Provides normalized XYZ tristimulus coordinates for various standard
 * light sources, supporting both 2° and 10° standard observers.
 */
enum Illuminant: string
{
    case A = 'A';

    case C = 'C';
    case D50 = 'D50';
    case D55 = 'D55';
    case D60 = 'D60';
    case D65 = 'D65';
    case D75 = 'D75';

    case E = 'E';
    case F11 = 'F11';

    case F2 = 'F2';
    case F7 = 'F7';

    /**
     * Get the normalized XYZ tristimulus white point.
     *
     * @param Observer $observer The standard observer (default: CIE 1931 2°)
     */
    public function whitePoint(Observer $observer = Observer::TwoDegree): WhitePoint
    {
        if (Observer::TenDegree === $observer) {
            return $this->whitePointTenDegree();
        }

        return $this->whitePointTwoDegree();
    }

    /**
     * Get the normalized XYZ tristimulus white point for the CIE 1931 2° observer.
     */
    private function whitePointTwoDegree(): WhitePoint
    {
        return match ($this) {
            self::A => new WhitePoint(1.09850, 1.00000, 0.35585),
            self::C => new WhitePoint(0.98074, 1.00000, 1.18232),

            self::D50 => new WhitePoint(0.96422, 1.00000, 0.82521),
            self::D55 => new WhitePoint(0.95682, 1.00000, 0.92149),
            self::D60 => new WhitePoint(0.95231, 1.00000, 1.00883),
            self::D65 => new WhitePoint(0.95047, 1.00000, 1.08883),
            self::D75 => new WhitePoint(0.94972, 1.00000, 1.22638),

            self::E => new WhitePoint(1.00000, 1.00000, 1.00000),

            self::F2 => new WhitePoint(0.99186, 1.00000, 0.67393),
            self::F7 => new WhitePoint(0.95041, 1.00000, 1.08747),
            self::F11 => new WhitePoint(1.00962, 1.00000, 0.64350),
        };
    }

    /**
     * Get the normalized XYZ tristimulus white point for the CIE 1964 10° observer.
     */
    private function whitePointTenDegree(): WhitePoint
    {
        return match ($this) {
            self::A => new WhitePoint(1.11144, 1.00000, 0.35200),
            self::C => new WhitePoint(0.97285, 1.00000, 1.16145),

            self::D50 => new WhitePoint(0.96720, 1.00000, 0.81427),
            self::D55 => new WhitePoint(0.95799, 1.00000, 0.90926),
            self::D60 => new WhitePoint(0.95231, 1.00000, 1.00883),
            self::D65 => new WhitePoint(0.94811, 1.00000, 1.07304),
            self::D75 => new WhitePoint(0.94416, 1.00000, 1.20641),

            self::E => new WhitePoint(1.00000, 1.00000, 1.00000),

            self::F2 => new WhitePoint(0.99001, 1.00000, 0.63197),
            self::F7 => new WhitePoint(0.95792, 1.00000, 1.07655),
            self::F11 => new WhitePoint(1.00966, 1.00000, 0.64370),
        };
    }
}
