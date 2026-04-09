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
use PhpColor\Color\Css\LightDarkColor;
use PhpColor\Color\Css\ResolvedColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LightDarkColor::class)]
final class LightDarkColorTest extends TestCase
{
    public function testRemainsUnresolvedWhenNoScheme(): void
    {
        $light = CssColor::parse('#000');
        $dark = CssColor::parse('#fff');
        $lightDark = new LightDarkColor($light, $dark);

        $ctx = new CssContext();
        $result = $lightDark->resolve($ctx);

        $this->assertInstanceOf(LightDarkColor::class, $result);
    }

    public function testResolvesDarkInDarkScheme(): void
    {
        $light = new ResolvedColor(CssColor::resolve(CssColor::parse('#000'), CssContext::light()));
        $dark = new ResolvedColor(CssColor::resolve(CssColor::parse('#fff'), CssContext::light()));
        $lightDark = new LightDarkColor($light, $dark);

        $ctx = CssContext::dark();
        $result = $lightDark->resolve($ctx);

        $this->assertInstanceOf(ColorInterface::class, $result);
        $this->assertSame('#ffffff', strtolower($result->toHex()));
    }

    public function testResolvesLightInLightScheme(): void
    {
        $light = new ResolvedColor(CssColor::resolve(CssColor::parse('#000'), CssContext::light()));
        $dark = new ResolvedColor(CssColor::resolve(CssColor::parse('#fff'), CssContext::light()));
        $lightDark = new LightDarkColor($light, $dark);

        $ctx = CssContext::light();
        $result = $lightDark->resolve($ctx);

        $this->assertInstanceOf(ColorInterface::class, $result);
        $this->assertSame('#000000', strtolower($result->toHex()));
    }

    public function testToCss(): void
    {
        $light = CssColor::parse('#000');
        $dark = CssColor::parse('#fff');
        $lightDark = new LightDarkColor($light, $dark);

        $this->assertSame('light-dark(rgb(0 0 0), rgb(255 255 255))', $lightDark->toCss());
    }

    public function testWithVarInBranches(): void
    {
        $light = CssColor::parse('var(--light-color, #000)');
        $dark = CssColor::parse('var(--dark-color, #fff)');
        $lightDark = new LightDarkColor($light, $dark);

        $ctx = CssContext::light(['--light-color' => '#222']);
        $result = $lightDark->resolve($ctx);

        $this->assertInstanceOf(ColorInterface::class, $result);
        $this->assertSame('#222222', strtolower($result->toHex()));
    }
}
