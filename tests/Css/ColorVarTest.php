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
use PhpColor\Color\Css\ColorVar;
use PhpColor\Color\Css\CssColor;
use PhpColor\Color\Css\CssContext;
use PhpColor\Color\Css\CssResolvableInterface;
use PhpColor\Color\Exception\InvalidColorException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ColorVar::class)]
final class ColorVarTest extends TestCase
{
    public function testResolveReturnsSelfWhenMissingAndNonStrict(): void
    {
        $ctx = new CssContext([], null, false);
        $expr = new ColorVar('--missing');

        $out = $expr->resolve($ctx);
        $this->assertInstanceOf(CssResolvableInterface::class, $out);
        $this->assertSame('var(--missing)', $out->toCss());
    }

    public function testResolveThrowsWhenStrictAndMissing(): void
    {
        $this->expectException(InvalidColorException::class);
        $ctx = new CssContext([], null, true);
        (new ColorVar('--missing'))->resolve($ctx);
    }

    public function testResolveUsesDefinedVarParsesAndResolves(): void
    {
        $ctx = new CssContext(['--primary' => 'rgb(1 2 3)']);
        $expr = new ColorVar('--primary');

        $out = $expr->resolve($ctx);
        $this->assertInstanceOf(ColorInterface::class, $out);
        $this->assertSame('rgb(1 2 3)', $out->toCss('srgb'));
    }

    public function testResolveUsesFallback(): void
    {
        $fallback = CssColor::parse('#ff0000');
        $expr = new ColorVar('--missing', $fallback);
        $ctx = new CssContext([], null, false);

        $out = $expr->resolve($ctx);
        $this->assertInstanceOf(ColorInterface::class, $out);
        $this->assertSame('#ff0000', strtolower($out->toHex()));
    }

    public function testToCssWithFallback(): void
    {
        $expr = new ColorVar('--a', CssColor::parse('#f00'));
        $this->assertSame('var(--a, rgb(255 0 0))', $expr->toCss());
    }
}
