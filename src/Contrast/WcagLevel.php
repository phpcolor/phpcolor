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

namespace PhpColor\Color\Contrast;

/**
 * WCAG 2.x conformance levels for text contrast.
 */
enum WcagLevel: string
{
    case AA = 'AA';
    case AAA = 'AAA';

    /*
     * Create a WcagLevel from a string value (case-insensitive).
     *
     * @throws \ValueError if the string is not a valid WCAG level
     */
}
