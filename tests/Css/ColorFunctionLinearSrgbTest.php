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

use PhpColor\Color\Css\CssColorParser;
use PhpColor\Color\LinearSrgbColor;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CssColorParser::class)]
final class ColorFunctionLinearSrgbTest extends TestCase
{
    public function testParseColorFunctionSrgbLinear(): void
    {
        $c = CssColorParser::parse('color(srgb-linear 0.25 0.5 0.75 / 0.5)');
        $this->assertInstanceOf(LinearSrgbColor::class, $c);
        $this->assertSame(0.5, $c->getAlpha());

        // Converting to sRGB should gamma-encode
        $srgb = $c->toSrgb();
        $this->assertInstanceOf(SrgbColor::class, $srgb);

        // Basic monotonic checks: linear 0.25->srgb > 0.25; 0.5->~0.73; 0.75->~0.88
        $this->assertGreaterThan(0.25, $srgb->r);
        $this->assertGreaterThan(0.5, $srgb->g);
        $this->assertGreaterThan(0.75, $srgb->b);
    }
}
