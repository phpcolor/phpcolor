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

use PhpColor\Color\ColorInterface;
use PhpColor\Color\Css\CssColor;
use PhpColor\Color\Css\CssContext;
use PhpColor\Color\Css\Exception\CssResolutionException;
use PhpColor\Color\Css\RelativeColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RelativeColor::class)]
final class RelativeColorTest extends TestCase
{
    public function testRelativeColorInOklabSpace(): void
    {
        $origin = CssColor::parse('#00ff00');
        $relative = new RelativeColor('oklab', $origin, ['l' => 'l', 'a' => 'a', 'b' => 'b'], '1.0');

        $ctx = CssContext::light();
        $result = $relative->resolve($ctx);

        $this->assertInstanceOf(ColorInterface::class, $result);
        $css = $result->toCss('oklab');
        $this->assertStringStartsWith('oklab(', $css);
    }

    public function testRelativeColorRemainsUnresolvedWithUnresolvedOrigin(): void
    {
        $origin = CssColor::parse('var(--base)');
        $relative = new RelativeColor('oklch', $origin, ['l' => 'l', 'c' => 'c', 'h' => 'h']);

        $ctx = new CssContext();
        $result = $relative->resolve($ctx);

        $this->assertInstanceOf(RelativeColor::class, $result);
    }

    public function testRelativeColorResolution(): void
    {
        $origin = CssColor::parse('#ff0000');
        $relative = new RelativeColor('oklch', $origin, ['l' => 'l', 'c' => 'c', 'h' => 'h'], '0.9');

        $ctx = CssContext::light();
        $result = $relative->resolve($ctx);

        $this->assertInstanceOf(ColorInterface::class, $result);
        $css = $result->toCss('oklch');
        $this->assertStringContainsString('/ 0.9', $css);
    }

    public function testRelativeColorResolutionThrowsOnInvalidChannels(): void
    {
        $origin = CssColor::parse('#ff0000');
        // Missing one channel for oklch (expect 3)
        $relative = new RelativeColor('oklch', $origin, ['l' => 'l', 'c' => 'c']);

        $this->expectException(CssResolutionException::class);
        $this->expectExceptionMessage('Relative color resolution failed:');
        $relative->resolve(CssContext::light());
    }

    public function testRelativeColorToCss(): void
    {
        $origin = CssColor::parse('#ff0000');
        $relative = new RelativeColor('oklch', $origin, ['l' => 'l', 'c' => 'c', 'h' => 'h'], '0.9');

        $css = $relative->toCss();
        $this->assertStringContainsString('oklch', $css);
        $this->assertStringContainsString('from', $css);
        $this->assertStringContainsString('/ 0.9', $css);
    }

    public function testRelativeColorWithCurrentColor(): void
    {
        $origin = CssColor::currentColor();
        $relative = new RelativeColor('oklch', $origin, ['l' => 'l', 'c' => 'c', 'h' => 'h']);

        $ctx = CssContext::light([], '#0000ff');
        $result = $relative->resolve($ctx);

        $this->assertInstanceOf(ColorInterface::class, $result);
    }

    public function testRelativeColorWithFloatAlpha(): void
    {
        $origin = CssColor::parse('#ff0000');
        $relative = new RelativeColor('oklch', $origin, ['l' => 'l', 'c' => 'c', 'h' => 'h'], 0.8);

        $ctx = CssContext::light();
        $result = $relative->resolve($ctx);

        $this->assertInstanceOf(ColorInterface::class, $result);
        $css = $result->toCss('oklch');
        $this->assertStringContainsString('/ 0.8', $css);
    }
}
