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

use PhpColor\Color\Css\CssContext;
use PhpColor\Color\Css\ResolvedColor;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResolvedColor::class)]
final class ResolvedColorTest extends TestCase
{
    public function testGetColor(): void
    {
        $color = new SrgbColor(1, 2, 3, 0.4);
        $resolved = new ResolvedColor($color);

        $this->assertSame($resolved->color(), $color);
    }

    public function testResolveReturnsWrappedColor(): void
    {
        $color = new SrgbColor(1.0, 0.0, 0.0);
        $resolved = new ResolvedColor($color);
        $ctx = new CssContext();

        $result = $resolved->resolve($ctx);

        $this->assertSame($color, $result);
    }

    public function testToCss(): void
    {
        $color = new SrgbColor(1, 2, 3, 0.4);
        $resolved = new ResolvedColor($color);

        $this->assertSame($resolved->toCss(), $color->toCss());
    }
}
