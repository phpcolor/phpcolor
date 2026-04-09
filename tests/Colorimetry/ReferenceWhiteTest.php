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

use PhpColor\Color\Colorimetry\Illuminant;
use PhpColor\Color\Colorimetry\ReferenceWhite;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReferenceWhite::class)]
final class ReferenceWhiteTest extends TestCase
{
    public function testSrgbIsD65(): void
    {
        $wp = ReferenceWhite::forSpace('srgb');
        $d65 = Illuminant::D65->whitePoint();
        $this->assertEquals($d65->toArray(), $wp->toArray());
    }

    public function testDisplayP3IsD65(): void
    {
        $wp = ReferenceWhite::forSpace('display-p3');
        $d65 = Illuminant::D65->whitePoint();
        $this->assertEquals($d65->toArray(), $wp->toArray());
    }

    public function testRec2020IsD65(): void
    {
        $wp = ReferenceWhite::forSpace('rec-2020');
        $d65 = Illuminant::D65->whitePoint();
        $this->assertEquals($d65->toArray(), $wp->toArray());
    }

    public function testProPhotoIsD50(): void
    {
        $wp = ReferenceWhite::forSpace('prophoto-rgb');
        $d50 = Illuminant::D50->whitePoint();
        $this->assertEquals($d50->toArray(), $wp->toArray());
    }

    public function testUnknownDefaultsToD65(): void
    {
        $wp = ReferenceWhite::forSpace('unknown-space');
        $d65 = Illuminant::D65->whitePoint();
        $this->assertEquals($d65->toArray(), $wp->toArray());
    }
}
