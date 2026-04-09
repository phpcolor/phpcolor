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

namespace PhpColor\Color\Tests\Palette\Transformer;

use PhpColor\Color\Color;
use PhpColor\Color\OklchColor;
use PhpColor\Color\Palette\ColorPalette;
use PhpColor\Color\Palette\Transformer\ColorPaletteTransformerInterface;
use PhpColor\Color\Palette\Transformer\LightModeColorPaletteTransformer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LightModeColorPaletteTransformer::class)]
final class LightModeColorPaletteTransformerTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $transformer = new LightModeColorPaletteTransformer();

        $this->assertInstanceOf(ColorPaletteTransformerInterface::class, $transformer);
    }

    public function testDefaultTransformation(): void
    {
        $palette = ColorPalette::named([
            'surface' => Color::parse('#1a1a1a'),
            'text' => Color::parse('#f5f5f5'),
            'primary' => Color::parse('#4f88fc'),
        ]);

        $transformer = new LightModeColorPaletteTransformer();
        $lightPalette = $transformer->transform($palette);

        $this->assertInstanceOf(ColorPalette::class, $lightPalette);
        $this->assertTrue($lightPalette->isNamed());
        $this->assertSame(3, $lightPalette->count());
    }

    public function testDarkBackgroundLightnessInversion(): void
    {
        $dark = Color::parse('#1a1a1a');
        $palette = ColorPalette::named(['bg' => $dark]);

        $transformer = new LightModeColorPaletteTransformer(bgLevel: 0.95);
        $lightPalette = $transformer->transform($palette);

        $lightBg = $lightPalette->get('bg')->to('oklch');
        $this->assertInstanceOf(OklchColor::class, $lightBg);
        $this->assertGreaterThan(0.8, $lightBg->l);
    }

    public function testLightTextLightnessInversion(): void
    {
        $white = Color::parse('#f5f5f5');
        $palette = ColorPalette::named(['text' => $white]);

        $transformer = new LightModeColorPaletteTransformer(textLevel: 0.15);
        $lightPalette = $transformer->transform($palette);

        $lightText = $lightPalette->get('text')->to('oklch');
        $this->assertInstanceOf(OklchColor::class, $lightText);
        $this->assertLessThan(0.4, $lightText->l);
    }

    public function testChromaBoosting(): void
    {
        $mutedBlue = Color::parse('#4f88fc');
        $palette = ColorPalette::named(['primary' => $mutedBlue]);

        $transformer = new LightModeColorPaletteTransformer(chromaBoosting: 1.15);
        $lightPalette = $transformer->transform($palette);

        $originalOklch = $mutedBlue->to('oklch');
        $lightOklch = $lightPalette->get('primary')->to('oklch');

        $this->assertInstanceOf(OklchColor::class, $originalOklch);
        $this->assertInstanceOf(OklchColor::class, $lightOklch);

        if ($originalOklch->c > 0.05) {
            $this->assertGreaterThanOrEqual($originalOklch->c, $lightOklch->c);
        }
    }

    public function testChromaCapAtMaximum(): void
    {
        $vibrant = Color::parse('#ff0000');
        $palette = ColorPalette::named(['accent' => $vibrant]);

        $transformer = new LightModeColorPaletteTransformer(chromaBoosting: 2.0);
        $lightPalette = $transformer->transform($palette);

        $lightOklch = $lightPalette->get('accent')->to('oklch');
        $this->assertInstanceOf(OklchColor::class, $lightOklch);
        $this->assertLessThanOrEqual(0.4, $lightOklch->c);
    }

    public function testNoChromaBoostingForLowChroma(): void
    {
        $gray = Color::parse('#404040');
        $palette = ColorPalette::named(['gray' => $gray]);

        $transformer = new LightModeColorPaletteTransformer(chromaBoosting: 1.5);
        $lightPalette = $transformer->transform($palette);

        $originalOklch = $gray->to('oklch');
        $lightOklch = $lightPalette->get('gray')->to('oklch');

        $this->assertInstanceOf(OklchColor::class, $originalOklch);
        $this->assertInstanceOf(OklchColor::class, $lightOklch);

        if ($originalOklch->c <= 0.05) {
            $this->assertEqualsWithDelta($originalOklch->c, $lightOklch->c, 0.01);
        }
    }

    public function testCustomParameters(): void
    {
        $palette = ColorPalette::named([
            'surface' => Color::parse('#1a1a1a'),
        ]);

        $transformer = new LightModeColorPaletteTransformer(
            bgLevel: 0.98,
            textLevel: 0.1,
            chromaBoosting: 1.2,
        );

        $lightPalette = $transformer->transform($palette);
        $this->assertInstanceOf(ColorPalette::class, $lightPalette);
    }

    public function testMidtoneLightness(): void
    {
        $midtone = Color::parse('#606060');
        $palette = ColorPalette::named(['mid' => $midtone]);

        $transformer = new LightModeColorPaletteTransformer();
        $lightPalette = $transformer->transform($palette);

        $originalOklch = $midtone->to('oklch');
        $lightOklch = $lightPalette->get('mid')->to('oklch');

        $this->assertInstanceOf(OklchColor::class, $originalOklch);
        $this->assertInstanceOf(OklchColor::class, $lightOklch);

        $this->assertLessThanOrEqual($originalOklch->l, $lightOklch->l);
    }

    public function testPreservesColorSpace(): void
    {
        $srgb = Color::parse('#1a1a1a');
        $displayP3 = $srgb->to('display-p3');

        $palette = ColorPalette::named([
            'srgb' => $srgb,
            'p3' => $displayP3,
        ]);

        $transformer = new LightModeColorPaletteTransformer();
        $lightPalette = $transformer->transform($palette);

        $this->assertSame('srgb', $lightPalette->get('srgb')::getSpaceName());
        $this->assertSame('display-p3', $lightPalette->get('p3')::getSpaceName());
    }

    public function testMultipleColors(): void
    {
        $palette = ColorPalette::named([
            'bg' => Color::parse('#1a1a1a'),
            'text' => Color::parse('#f5f5f5'),
            'primary' => Color::parse('#4f88fc'),
            'secondary' => Color::parse('#10b981'),
            'accent' => Color::parse('#f59e0b'),
        ]);

        $transformer = new LightModeColorPaletteTransformer();
        $lightPalette = $transformer->transform($palette);

        $this->assertSame(5, $lightPalette->count());
        $this->assertTrue($lightPalette->isNamed());

        foreach (['bg', 'text', 'primary', 'secondary', 'accent'] as $name) {
            $this->assertNotNull($lightPalette->get($name));
        }
    }

    public function testDarkToLightRoundTrip(): void
    {
        $darkPalette = ColorPalette::named([
            'surface' => Color::parse('#1a1a1a'),
            'text' => Color::parse('#f5f5f5'),
        ]);

        $transformer = new LightModeColorPaletteTransformer();
        $lightPalette = $transformer->transform($darkPalette);

        $darkSurface = $darkPalette->get('surface')->to('oklch');
        $lightSurface = $lightPalette->get('surface')->to('oklch');

        $this->assertInstanceOf(OklchColor::class, $darkSurface);
        $this->assertInstanceOf(OklchColor::class, $lightSurface);
        $this->assertLessThan($lightSurface->l, $darkSurface->l);
    }
}
