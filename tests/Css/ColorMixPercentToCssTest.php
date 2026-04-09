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

namespace PhpColor\Color\Tests\Css;

use PhpColor\Color\Css\ColorMix;
use PhpColor\Color\Css\CssColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ColorMix::class)]
final class ColorMixPercentToCssTest extends TestCase
{
    public function testToCssFormatsPercentWeightsOverOne(): void
    {
        $left = CssColor::parse('#000');
        $right = CssColor::parse('#fff');
        $mix = new ColorMix('oklab', $left, $right, 60.0);

        $css = $mix->toCss();
        $this->assertStringContainsString('60%', $css);
        // Only provided weight is serialized; complement is implicit in resolve()
    }
}
