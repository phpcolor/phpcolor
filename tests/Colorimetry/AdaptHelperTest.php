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

namespace PhpColor\Color\Tests\Colorimetry;

use PhpColor\Color\Color;
use PhpColor\Color\Colorimetry\Adaptation\Adapt;
use PhpColor\Color\Colorimetry\Illuminant;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Adapt::class)]
final class AdaptHelperTest extends TestCase
{
    public function testAdaptColorRoundTripSameSpace(): void
    {
        $c = Color::parse('oklch(0.72 0.12 40)');
        $src = Illuminant::D65->whitePoint();
        $dst = Illuminant::D50->whitePoint();

        $toD50 = Adapt::color($c, $src, $dst, 'bradford');
        $back = Adapt::color($toD50, $dst, $src, 'bradford');

        // Compare in sRGB for a stable baseline
        $a = $c->toSrgb();
        $b = $back->toSrgb();
        $this->assertEqualsWithDelta($a->r, $b->r, 1e-6);
        $this->assertEqualsWithDelta($a->g, $b->g, 1e-6);
        $this->assertEqualsWithDelta($a->b, $b->b, 1e-6);
        $this->assertSame($a->a, $b->a);
    }
}
