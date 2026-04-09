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

use PhpColor\Color\Color;
use PhpColor\Color\ColorInterface;
use PhpColor\Color\Css\CssColor;
use PhpColor\Color\Css\CssContext;
use PhpColor\Color\Css\CssResolvableInterface;
use PhpColor\Color\Css\ResolvedColor;
use PhpColor\Color\Exception\InvalidColorException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CssColor::class)]
#[CoversClass(CssContext::class)]
#[CoversClass(ResolvedColor::class)]
final class CssColorTest extends TestCase
{
    public function testColorMixAlias(): void
    {
        $expr1 = CssColor::mix('oklab', '#000', '#fff', 0.25, 0.75);
        $expr2 = CssColor::colorMix('oklab', '#000', '#fff', 0.25, 0.75);

        $out1 = CssColor::resolve($expr1, CssContext::light());
        $out2 = CssColor::resolve($expr2, CssContext::light());

        $this->assertSame(strtolower($out1->toHex()), strtolower($out2->toHex()));
    }

    public function testColorMixBuilder(): void
    {
        $expr = CssColor::mix('oklab', '#000', '#fff', 0.25, 0.75);
        $out = CssColor::resolve($expr, CssContext::light());

        $this->assertInstanceOf(ColorInterface::class, $out);
        $hex = strtolower($out->toHex());
        $this->assertSame('#aeaeae', $hex);
    }

    public function testColorMixDefaultAndComplementWeights(): void
    {
        // Both null => defaults equal weights
        $expr1 = CssColor::mix('oklab', '#000', '#fff');
        $out1 = CssColor::resolve($expr1, CssContext::light());
        $this->assertSame('#636363', strtolower($out1->toHex()));

        // Only w2 provided => w1 is complement
        $expr2 = CssColor::mix('oklab', '#000', '#fff', null, 0.75);
        $out2 = CssColor::resolve($expr2, CssContext::light());
        $this->assertNotSame(strtolower($out1->toHex()), strtolower($out2->toHex()));
    }

    public function testContextColorScheme(): void
    {
        $lightCtx = CssContext::light();
        $this->assertSame('light', $lightCtx->colorScheme());

        $darkCtx = CssContext::dark();
        $this->assertSame('dark', $darkCtx->colorScheme());
    }

    public function testContextStrictMode(): void
    {
        $ctx = new CssContext([], null, true);
        $this->assertTrue($ctx->isStrict());

        $ctx2 = new CssContext([], null, false);
        $this->assertFalse($ctx2->isStrict());
    }

    public function testContextWithVarMethod(): void
    {
        $ctx = new CssContext();
        $ctx2 = $ctx->withVar('--color', '#ff0000');

        $this->assertNull($ctx->getVar('--color'));
        $this->assertSame('#ff0000', $ctx2->getVar('--color'));
    }

    public function testCurrentColorBuilder(): void
    {
        $expr = CssColor::currentColor();
        $this->assertSame('currentColor', $expr->toCss());
    }

    public function testFromAliasRelative(): void
    {
        $expr1 = CssColor::relative('oklch', '#ff0000', ['l' => 'l', 'c' => 'c', 'h' => 'h'], '0.9');
        $expr2 = CssColor::from('oklch', '#ff0000', ['l' => 'l', 'c' => 'c', 'h' => 'h'], '0.9');

        $this->assertSame($expr1->toCss(), $expr2->toCss());
        $out1 = CssColor::resolve($expr1, CssContext::light());
        $out2 = CssColor::resolve($expr2, CssContext::light());
        $this->assertSame($out1->toCss('oklch'), $out2->toCss('oklch'));
    }

    public function testLightDarkBuilder(): void
    {
        $expr = CssColor::lightDark('var(--fg)', 'var(--fg-dark, white)');

        $out = CssColor::resolve($expr, CssContext::light(['--fg' => '#333']));
        $this->assertInstanceOf(ColorInterface::class, $out);
        $this->assertSame('#333333', strtolower($out->toHex()));
    }

    public function testLightDarkResolvesByScheme(): void
    {
        $expr = CssColor::parse('light-dark(var(--fg), var(--fg-dark, white))');

        $ctxLight = CssContext::light(['--fg' => '#222']);
        $outLight = CssColor::resolve($expr, $ctxLight);
        $this->assertInstanceOf(ColorInterface::class, $outLight);
        $this->assertSame('#222222', strtolower($outLight->toHex()));

        $ctxDark = CssContext::dark(['--fg-dark' => 'oklch(0.9 0 0)']);
        $outDark = CssColor::resolve($expr, $ctxDark);
        $this->assertInstanceOf(ColorInterface::class, $outDark);
        $this->assertStringStartsWith('oklch(', $outDark->toCss('oklch'));
    }

    public function testLightDarkSplitTwoArgsErrorsOnEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('light-dark() expects exactly two arguments.');
        CssColor::parse('light-dark()');
    }

    public function testLightDarkSplitTwoArgsErrorsOnUnbalancedParens(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unbalanced parentheses');
        CssColor::parse('light-dark(rgb(255 0 0, #000)');
    }

    public function testLightDarkSplitTwoArgsErrorsOnUnexpectedClosingParen(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unbalanced parentheses');
        // Extra ')' before the comma causes an unexpected closing parenthesis while level=0
        CssColor::parse('light-dark(rgb(255 0 0)), #000)');
    }

    public function testLightDarkSplitTwoArgsErrorsOnWrongCount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('light-dark() expects exactly two arguments.');
        CssColor::parse('light-dark(#111, #222, #333)');
    }

    public function testLightDarkSplitTwoArgsErrorsOnWrongCountInComplexPath(): void
    {
        // First arg has parentheses -> complex path engaged; two top-level commas => 3 parts
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('light-dark() expects exactly two arguments.');
        CssColor::parse('light-dark(rgb(255 0 0), #000, #111)');
    }

    public function testLightDarkSplitTwoArgsNestedParenthesesContinuePath(): void
    {
        // First argument contains nested parentheses via var(--x, var(--y, #000)) so the ')' branch
        // decrements level and continues scanning inside nested parens
        $expr = CssColor::parse('light-dark(var(--x, var(--y, #000)), #000)');
        $this->assertStringStartsWith('light-dark(var(', $expr->toCss());

        $resolved = CssColor::resolve($expr, CssContext::light(['--x' => '#123', '--y' => '#456']));
        $this->assertInstanceOf(ColorInterface::class, $resolved);
    }

    public function testLightDarkSplitTwoArgsTopLevelCommaPath(): void
    {
        // Ensure we take the complex path (presence of parentheses) and split on the top-level comma
        $expr = CssColor::parse('light-dark(rgb(255 0 0), #000)');
        $this->assertSame('light-dark(rgb(255 0 0), rgb(0 0 0))', $expr->toCss());

        $resolved = CssColor::resolve($expr, CssContext::light());
        $this->assertInstanceOf(ColorInterface::class, $resolved);
    }

    public function testMixWithColorInterface(): void
    {
        $black = Color::parse('#000');
        $white = Color::parse('#fff');

        $expr = CssColor::mix('oklab', $black, $white, 0.5, 0.5);
        $out = CssColor::resolve($expr, CssContext::light());

        $this->assertInstanceOf(ColorInterface::class, $out);
        $this->assertSame('#636363', strtolower($out->toHex()));
    }

    public function testMixWithDefaultSpace(): void
    {
        $expr = CssColor::mix('oklab', '#000', '#fff');
        $out = CssColor::resolve($expr, CssContext::light());
        $this->assertInstanceOf(ColorInterface::class, $out);
    }

    public function testParseFallbacksToResolvedColor(): void
    {
        $expr = CssColor::parse('oklch(0.5 0.1 25 / 0.9)');
        $this->assertInstanceOf(ResolvedColor::class, $expr);

        $resolved = CssColor::resolve($expr, CssContext::light());
        $this->assertInstanceOf(ColorInterface::class, $resolved);
        $this->assertStringStartsWith('oklch(', $resolved->toCss('oklch'));
    }

    public function testParseHexReturnsResolved(): void
    {
        $expr = CssColor::parse('#ff0000');
        $this->assertInstanceOf(ResolvedColor::class, $expr);

        $resolved = CssColor::resolve($expr, CssContext::light());
        $this->assertInstanceOf(ColorInterface::class, $resolved);
        $this->assertSame('#ff0000', strtolower($resolved->toHex()));
    }

    public function testParseRgbReturnsResolved(): void
    {
        $expr = CssColor::parse('rgb(255 0 0)');
        $this->assertInstanceOf(ResolvedColor::class, $expr);

        $resolved = CssColor::resolve($expr, CssContext::light());
        $this->assertInstanceOf(ColorInterface::class, $resolved);
        $this->assertSame('#ff0000', strtolower($resolved->toHex()));
    }

    public function testParseWrapsInvalidColorException(): void
    {
        $this->expectException(InvalidColorException::class);
        $this->expectExceptionMessage('Unable to parse CSS color');

        CssColor::parse('definitely-not-a-color-token');
    }

    public function testResolveReturnsColorInterfaceAsIs(): void
    {
        $color = Color::parse('#ff0000');
        $out = CssColor::resolve($color, CssContext::light());
        $this->assertSame($color, $out);
    }

    public function testVarBuilder(): void
    {
        $expr = CssColor::var('--color', '#ff0000');
        $ctx = new CssContext();

        $out = CssColor::resolve($expr, $ctx);
        $this->assertInstanceOf(ColorInterface::class, $out);
        $this->assertSame('#ff0000', strtolower($out->toHex()));
    }

    public function testVarMissingAndNonStrictReturnsUnresolved(): void
    {
        $expr = CssColor::parse('var(--missing)');
        $ctx = new CssContext([], null, false);
        $out = CssColor::resolve($expr, $ctx);
        $this->assertInstanceOf(CssResolvableInterface::class, $out);
        $this->assertSame('var(--missing)', $out->toCss());
    }

    public function testVarMissingStrictThrows(): void
    {
        $this->expectException(InvalidColorException::class);
        $expr = CssColor::parse('var(--missing)');
        $ctx = new CssContext([], null, true);
        CssColor::resolve($expr, $ctx);
    }

    public function testVarMissingUsesFallback(): void
    {
        $expr = CssColor::parse('var(--missing, #09c)');
        $ctx = new CssContext([]);

        $resolved = CssColor::resolve($expr, $ctx);
        $this->assertInstanceOf(ColorInterface::class, $resolved);
        $this->assertSame('#0099cc', strtolower($resolved->toHex()));
    }

    public function testVarParsesAndResolvesWithContext(): void
    {
        $expr = CssColor::parse('var(--brand, oklch(0.62 0.12 25))');
        $this->assertInstanceOf(CssResolvableInterface::class, $expr);

        $ctx = new CssContext(['--brand' => '#ff3366'], 'light');
        $resolved = CssColor::resolve($expr, $ctx);

        $this->assertInstanceOf(ColorInterface::class, $resolved);
        $this->assertStringStartsWith('rgb(', $resolved->toCss('srgb'));
    }
}
