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
use PhpColor\Color\Css\ColorMix;
use PhpColor\Color\Css\CssColor;
use PhpColor\Color\Css\CssContext;
use PhpColor\Color\Exception\InvalidColorException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ColorMix::class)]
final class ColorMixTest extends TestCase
{
    public function testComplementWeightForPercentageInputFirstWeight(): void
    {
        $left = CssColor::parse('#ff0000');
        $right = CssColor::parse('#0000ff');

        $ctx = CssContext::light();

        // 60% implies first weight = 60, second defaults to 40 (complement branch: 100 - w)
        $explicit = new ColorMix('oklab', $left, $right, 60.0, 40.0);
        $implicit = new ColorMix('oklab', $left, $right, 60.0);

        $this->assertSame(
            strtolower($explicit->resolve($ctx)->toHex()),
            strtolower($implicit->resolve($ctx)->toHex())
        );
    }

    public function testComplementWeightForPercentageInputSecondWeight(): void
    {
        $left = CssColor::parse('#ff0000');
        $right = CssColor::parse('#0000ff');

        $ctx = CssContext::light();

        // 70% for second, first defaults to 30 (complement branch)
        $explicit = new ColorMix('oklab', $left, $right, 30.0, 70.0);
        $implicit = new ColorMix('oklab', $left, $right, null, 70.0);

        $this->assertSame(
            strtolower($explicit->resolve($ctx)->toHex()),
            strtolower($implicit->resolve($ctx)->toHex())
        );
    }

    public function testMissingFirstWeightDefaultsToComplement(): void
    {
        $left = CssColor::parse('#ff0000');
        $right = CssColor::parse('#0000ff');

        $ctx = CssContext::light();

        $explicit = new ColorMix('oklab', $left, $right, 0.40, 0.60);
        $implicit = new ColorMix('oklab', $left, $right, null, 0.60);

        $this->assertSame(
            strtolower($explicit->resolve($ctx)->toHex()),
            strtolower($implicit->resolve($ctx)->toHex())
        );
    }

    public function testMissingSecondWeightDefaultsToComplement(): void
    {
        $left = CssColor::parse('#ff0000');
        $right = CssColor::parse('#0000ff');

        $ctx = CssContext::light();

        $explicit = new ColorMix('oklab', $left, $right, 0.25, 0.75);
        $implicit = new ColorMix('oklab', $left, $right, 0.25);

        $this->assertSame(
            strtolower($explicit->resolve($ctx)->toHex()),
            strtolower($implicit->resolve($ctx)->toHex())
        );
    }

    public function testMixInSrgbSpace(): void
    {
        $left = CssColor::parse('#ff0000');
        $right = CssColor::parse('#0000ff');
        $mix = new ColorMix('srgb', $left, $right, 0.5, 0.5);

        $ctx = CssContext::light();
        $result = $mix->resolve($ctx);

        $this->assertInstanceOf(ColorInterface::class, $result);
    }

    public function testMixRemainsUnresolvedWithUnresolvedInputs(): void
    {
        $left = CssColor::parse('var(--left)');
        $right = CssColor::parse('#fff');
        $mix = new ColorMix('oklab', $left, $right, 0.5, 0.5);

        $ctx = new CssContext();
        $result = $mix->resolve($ctx);

        $this->assertInstanceOf(ColorMix::class, $result);
    }

    public function testMixThrowsOnZeroWeights(): void
    {
        $this->expectException(InvalidColorException::class);

        $left = CssColor::parse('#000');
        $right = CssColor::parse('#fff');
        $mix = new ColorMix('oklab', $left, $right, 0.0, 0.0);

        $ctx = CssContext::light();
        $mix->resolve($ctx);
    }

    public function testMixToCss(): void
    {
        $left = CssColor::parse('#000');
        $right = CssColor::parse('#fff');
        $mix = new ColorMix('oklab', $left, $right, 0.3, 0.7);

        $css = $mix->toCss();
        $this->assertStringContainsString('color-mix(in oklab', $css);
        $this->assertStringContainsString('rgb(0 0 0)', $css);
        $this->assertStringContainsString('rgb(255 255 255)', $css);
    }

    public function testMixTwoColors(): void
    {
        $left = CssColor::parse('#000');
        $right = CssColor::parse('#fff');
        $mix = new ColorMix('oklab', $left, $right, 0.5, 0.5);

        $ctx = CssContext::light();
        $result = $mix->resolve($ctx);

        $this->assertInstanceOf(ColorInterface::class, $result);
        $this->assertSame('#636363', strtolower($result->toHex()));
    }

    public function testMixWithDefaultWeights(): void
    {
        $left = CssColor::parse('#000');
        $right = CssColor::parse('#fff');
        $mix = new ColorMix('oklab', $left, $right);

        $ctx = CssContext::light();
        $result = $mix->resolve($ctx);

        $this->assertInstanceOf(ColorInterface::class, $result);
    }

    public function testMixWithUnequalWeights(): void
    {
        $left = CssColor::parse('#000');
        $right = CssColor::parse('#fff');
        $mix = new ColorMix('oklab', $left, $right, 0.25, 0.75);

        $ctx = CssContext::light();
        $result = $mix->resolve($ctx);

        $this->assertInstanceOf(ColorInterface::class, $result);
        $this->assertSame('#aeaeae', strtolower($result->toHex()));
    }
}
