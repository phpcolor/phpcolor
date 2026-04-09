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
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CssContext::class)]
class CssContextTest extends TestCase
{
    public function testConstructorWithColorScheme(): void
    {
        $ctx = new CssContext([], 'light');
        $this->assertSame('light', $ctx->colorScheme());
    }

    public function testConstructorWithCurrentColor(): void
    {
        $color = new SrgbColor(1.0, 0.0, 0.0);
        $ctx = new CssContext([], null, false, $color);
        $this->assertSame($color, $ctx->getCurrentColor());
    }

    public function testConstructorWithCurrentColorAsString(): void
    {
        $ctx = new CssContext([], null, false, '#ff0000');
        $this->assertSame('#ff0000', $ctx->getCurrentColor());
    }

    public function testConstructorWithDefaults(): void
    {
        $ctx = new CssContext();
        $this->assertNull($ctx->getVar('anything'));
        $this->assertNull($ctx->colorScheme());
        $this->assertFalse($ctx->isStrict());
        $this->assertNull($ctx->getCurrentColor());
    }

    public function testConstructorWithStrictMode(): void
    {
        $ctx = new CssContext([], null, true);
        $this->assertTrue($ctx->isStrict());
    }

    public function testConstructorWithVariables(): void
    {
        $ctx = new CssContext(['--primary' => '#ff0000']);
        $this->assertSame('#ff0000', $ctx->getVar('--primary'));
        $this->assertNull($ctx->getVar('--secondary'));
    }

    public function testDarkFactoryMethod(): void
    {
        $ctx = CssContext::dark();
        $this->assertSame('dark', $ctx->colorScheme());
        $this->assertFalse($ctx->isStrict());
    }

    public function testDarkWithCurrentColor(): void
    {
        $color = new SrgbColor(1.0, 1.0, 1.0);
        $ctx = CssContext::dark([], $color);
        $this->assertSame('dark', $ctx->colorScheme());
        $this->assertSame($color, $ctx->getCurrentColor());
    }

    public function testDarkWithVariables(): void
    {
        $ctx = CssContext::dark(['--bg' => 'black']);
        $this->assertSame('dark', $ctx->colorScheme());
        $this->assertSame('black', $ctx->getVar('--bg'));
    }

    public function testGetVarReturnsNullForMissingVar(): void
    {
        $ctx = new CssContext(['--color' => 'blue']);
        $this->assertNull($ctx->getVar('--missing'));
    }

    public function testGetVarReturnsValue(): void
    {
        $ctx = new CssContext(['--color' => 'blue', '--size' => '10px']);
        $this->assertSame('blue', $ctx->getVar('--color'));
        $this->assertSame('10px', $ctx->getVar('--size'));
    }

    public function testImmutability(): void
    {
        $ctx = new CssContext(['--orig' => 'value'], 'light', false);

        // Modify through withVar
        $ctx2 = $ctx->withVar('--new', 'newvalue');

        // Modify through withCurrentColor
        $color = new SrgbColor(1.0, 0.0, 0.0);
        $ctx3 = $ctx2->withCurrentColor($color);

        // Original context remains unchanged
        $this->assertSame('value', $ctx->getVar('--orig'));
        $this->assertNull($ctx->getVar('--new'));
        $this->assertSame('light', $ctx->colorScheme());
        $this->assertNull($ctx->getCurrentColor());

        // Each modification creates new instance
        $this->assertNotSame($ctx, $ctx2);
        $this->assertNotSame($ctx2, $ctx3);
        $this->assertNotSame($ctx, $ctx3);
    }

    public function testLightFactoryMethod(): void
    {
        $ctx = CssContext::light();
        $this->assertSame('light', $ctx->colorScheme());
        $this->assertFalse($ctx->isStrict());
    }

    public function testLightWithCurrentColor(): void
    {
        $color = new SrgbColor(0.0, 0.0, 0.0);
        $ctx = CssContext::light([], $color);
        $this->assertSame('light', $ctx->colorScheme());
        $this->assertSame($color, $ctx->getCurrentColor());
    }

    public function testLightWithVariables(): void
    {
        $ctx = CssContext::light(['--bg' => 'white']);
        $this->assertSame('light', $ctx->colorScheme());
        $this->assertSame('white', $ctx->getVar('--bg'));
    }

    public function testWithCurrentColorAcceptsString(): void
    {
        $ctx = new CssContext();
        $ctx2 = $ctx->withCurrentColor('#ff0000');

        $this->assertNull($ctx->getCurrentColor());
        $this->assertSame('#ff0000', $ctx2->getCurrentColor());
    }

    public function testWithCurrentColorPreservesOtherProperties(): void
    {
        $ctx = new CssContext(['--var' => 'value'], 'dark', true);
        $color = new SrgbColor(1.0, 0.0, 0.0);
        $ctx2 = $ctx->withCurrentColor($color);

        $this->assertSame('value', $ctx2->getVar('--var'));
        $this->assertSame('dark', $ctx2->colorScheme());
        $this->assertTrue($ctx2->isStrict());
    }

    public function testWithCurrentColorUpdatesColor(): void
    {
        $color1 = new SrgbColor(1.0, 0.0, 0.0);
        $color2 = new SrgbColor(0.0, 0.0, 1.0);

        $ctx = new CssContext([], null, false, $color1);
        $ctx2 = $ctx->withCurrentColor($color2);

        $this->assertSame($color1, $ctx->getCurrentColor());
        $this->assertSame($color2, $ctx2->getCurrentColor());
    }

    public function testWithVarAddsNewVariable(): void
    {
        $ctx = new CssContext(['--primary' => 'red']);
        $ctx2 = $ctx->withVar('--secondary', 'blue');

        // Original context unchanged
        $this->assertSame('red', $ctx->getVar('--primary'));
        $this->assertNull($ctx->getVar('--secondary'));

        // New context has both variables
        $this->assertSame('red', $ctx2->getVar('--primary'));
        $this->assertSame('blue', $ctx2->getVar('--secondary'));
    }

    public function testWithVarOverridesExistingVariable(): void
    {
        $ctx = new CssContext(['--color' => 'red']);
        $ctx2 = $ctx->withVar('--color', 'blue');

        $this->assertSame('red', $ctx->getVar('--color'));
        $this->assertSame('blue', $ctx2->getVar('--color'));
    }

    public function testWithVarPreservesOtherProperties(): void
    {
        $color = new SrgbColor(1.0, 0.0, 0.0);
        $ctx = new CssContext([], 'light', true, $color);
        $ctx2 = $ctx->withVar('--new', 'value');

        $this->assertSame('light', $ctx2->colorScheme());
        $this->assertTrue($ctx2->isStrict());
        $this->assertSame($color, $ctx2->getCurrentColor());
    }
}
