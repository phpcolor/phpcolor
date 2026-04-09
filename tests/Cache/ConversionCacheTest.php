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

namespace PhpColor\Color\Tests\Cache;

use PhpColor\Color\Cache\ConversionCache;
use PhpColor\Color\OklchColor;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConversionCache::class)]
final class ConversionCacheTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetCache();
    }

    protected function tearDown(): void
    {
        $this->resetCache();
    }

    public function testGetOklchFromSrgbReturnsNullWhenNotCached(): void
    {
        $srgb = new SrgbColor(0.1, 0.2, 0.3);

        $this->assertNull(ConversionCache::getOklchFromSrgb($srgb));
    }

    public function testGetSrgbFromOklchReturnsNullWhenNotCached(): void
    {
        $oklch = new OklchColor(0.5, 0.1, 120.0);

        $this->assertNull(ConversionCache::getSrgbFromOklch($oklch));
    }

    public function testSetOklchFromSrgbCachesValue(): void
    {
        $srgb = new SrgbColor(0.4, 0.5, 0.6);
        $oklch = new OklchColor(0.5, 0.1, 120.0);

        ConversionCache::setOklchFromSrgb($srgb, $oklch);

        $this->assertSame($oklch, ConversionCache::getOklchFromSrgb($srgb));
    }

    public function testSetSrgbFromOklchCachesValue(): void
    {
        $oklch = new OklchColor(0.6, 0.2, 45.0);
        $srgb = new SrgbColor(0.3, 0.2, 0.1);

        ConversionCache::setSrgbFromOklch($oklch, $srgb);

        $this->assertSame($srgb, ConversionCache::getSrgbFromOklch($oklch));
    }

    public function testCacheBidirectionalStoresBothDirections(): void
    {
        $srgb = new SrgbColor(0.7, 0.1, 0.2);
        $oklch = new OklchColor(0.4, 0.3, 300.0);

        ConversionCache::cacheBidirectional($srgb, $oklch);

        $this->assertSame($oklch, ConversionCache::getOklchFromSrgb($srgb));
        $this->assertSame($srgb, ConversionCache::getSrgbFromOklch($oklch));
    }

    private function resetCache(): void
    {
        $reset = \Closure::bind(
            static function (): void {
                ConversionCache::$srgbToOklch = null;
                ConversionCache::$oklchToSrgb = null;
            },
            null,
            ConversionCache::class,
        );

        $reset();
    }
}
