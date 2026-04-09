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
use PhpColor\Color\Css\CssResolvableInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CssColor::class)]
final class PartialResolutionTest extends TestCase
{
    public function testLightDarkRemainsWhenSchemeUnknown(): void
    {
        $expr = CssColor::parse('light-dark(var(--fg), var(--fg-dark, white))');
        $out = CssColor::resolve($expr, new CssContext(['--fg' => '#111']));

        $this->assertInstanceOf(CssResolvableInterface::class, $out);
        $this->assertStringStartsWith('light-dark(', $out->toCss());
    }

    public function testMixWithUnresolvedVar(): void
    {
        $expr = CssColor::mix('oklab', 'var(--color1)', '#fff', 0.5, 0.5);
        $out = CssColor::resolve($expr, new CssContext([]));

        $this->assertInstanceOf(CssResolvableInterface::class, $out);
        $this->assertStringContainsString('color-mix', $out->toCss());
    }

    public function testVarInVarFallback(): void
    {
        $expr = CssColor::parse('var(--primary, var(--fallback, #000))');
        $ctx = new CssContext(['--fallback' => '#ff0000']);

        $out = CssColor::resolve($expr, $ctx);
        $this->assertInstanceOf(ColorInterface::class, $out);
        $this->assertSame('#ff0000', strtolower($out->toHex()));
    }

    public function testVarWithoutFallbackRemainsUnresolved(): void
    {
        $expr = CssColor::parse('var(--brand)');
        $this->assertInstanceOf(CssResolvableInterface::class, $expr);

        $out = CssColor::resolve($expr, new CssContext([]));
        $this->assertInstanceOf(CssResolvableInterface::class, $out);
        $this->assertSame('var(--brand)', $out->toCss());
    }
}
