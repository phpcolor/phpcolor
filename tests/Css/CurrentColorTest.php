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
use PhpColor\Color\Css\CurrentColor;
use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CurrentColor::class)]
#[CoversClass(CssColor::class)]
final class CurrentColorTest extends TestCase
{
    public function testColorMixWithCurrentColor(): void
    {
        $expr = CssColor::mix('oklab', CssColor::currentColor(), '#fff', 0.5, 0.5);

        $ctx = CssContext::light([], '#000');
        $out = CssColor::resolve($expr, $ctx);
        $this->assertInstanceOf(ColorInterface::class, $out);

        // Mixing black and white in oklab produces #636363, not #808080,
        // due to perceptual uniformity of oklab space
        $this->assertSame('#636363', strtolower($out->toHex()));
    }

    public function testParseKeywordReturnsResolvable(): void
    {
        $expr = CssColor::parse('currentColor');
        $this->assertInstanceOf(CssResolvableInterface::class, $expr);
        $this->assertSame('currentColor', $expr->toCss());

        $expr2 = CssColor::parse('currentcolor');
        $this->assertInstanceOf(CssResolvableInterface::class, $expr2);
        $this->assertSame('currentColor', $expr2->toCss());
    }

    public function testResolvesFromContextColorInterface(): void
    {
        $ctx = CssContext::light()->withCurrentColor(new SrgbColor(1.0, 0.0, 0.0, 1.0));
        $expr = CssColor::currentColor();

        $out = CssColor::resolve($expr, $ctx);
        $this->assertInstanceOf(ColorInterface::class, $out);
        $this->assertSame('#ff0000', strtolower($out->toHex()));
    }

    public function testResolvesFromContextCssString(): void
    {
        $ctx = CssContext::light(['--brand' => '#09c']);
        $ctx = $ctx->withCurrentColor('var(--brand)');

        $expr = CssColor::parse('currentColor');
        $out = CssColor::resolve($expr, $ctx);

        $this->assertInstanceOf(ColorInterface::class, $out);
        $this->assertSame('#0099cc', strtolower($out->toHex()));
    }

    public function testStringCurrentColorThatResolvesUnresolvedReturnsSelfWhenNonStrict(): void
    {
        $ctx = new CssContext([], null, false);
        $ctx = $ctx->withCurrentColor('var(--missing)');

        $expr = CssColor::currentColor();
        $out = CssColor::resolve($expr, $ctx);

        $this->assertInstanceOf(CssResolvableInterface::class, $out);
        $this->assertSame('currentColor', $out->toCss());
    }

    public function testThrowsWhenMissingAndStrict(): void
    {
        $this->expectException(InvalidColorException::class);
        $ctx = new CssContext([], null, true);
        $expr = CssColor::currentColor();
        CssColor::resolve($expr, $ctx);
    }

    public function testUnresolvedWhenMissingAndNonStrict(): void
    {
        $ctx = new CssContext([], null, false);
        $expr = CssColor::currentColor();
        $out = CssColor::resolve($expr, $ctx);

        $this->assertInstanceOf(CssResolvableInterface::class, $out);
        $this->assertSame('currentColor', $out->toCss());
    }

    public function testWorksInLightDarkBranches(): void
    {
        $expr = CssColor::parse('light-dark(currentColor, #fff)');

        $ctxLight = CssContext::light([], '#222');
        $outL = CssColor::resolve($expr, $ctxLight);
        $this->assertInstanceOf(ColorInterface::class, $outL);
        $this->assertSame('#222222', strtolower($outL->toHex()));

        $ctxDark = CssContext::dark();
        $outD = CssColor::resolve($expr, $ctxDark);
        $this->assertInstanceOf(ColorInterface::class, $outD);
        $this->assertSame('#ffffff', strtolower($outD->toHex()));
    }

    public function testWorksInsideVarFallback(): void
    {
        $expr = CssColor::parse('var(--fg, currentColor)');

        $ctx = CssContext::light([])->withCurrentColor('#333');
        $out = CssColor::resolve($expr, $ctx);

        $this->assertInstanceOf(ColorInterface::class, $out);
        $this->assertSame('#333333', strtolower($out->toHex()));
    }
}
