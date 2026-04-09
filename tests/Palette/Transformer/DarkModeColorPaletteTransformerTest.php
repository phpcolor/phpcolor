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
use PhpColor\Color\Palette\Transformer\DarkModeColorPaletteTransformer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DarkModeColorPaletteTransformer::class)]
final class DarkModeColorPaletteTransformerTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $transformer = new DarkModeColorPaletteTransformer();

        $this->assertInstanceOf(ColorPaletteTransformerInterface::class, $transformer);
    }

    public function testDefaultTransformation(): void
    {
        $palette = ColorPalette::named([
            'surface' => Color::parse('#ffffff'),
            'text' => Color::parse('#111827'),
            'primary' => Color::parse('#2563eb'),
        ]);

        $transformer = new DarkModeColorPaletteTransformer();
        $darkPalette = $transformer->transform($palette);

        $this->assertInstanceOf(ColorPalette::class, $darkPalette);
        $this->assertTrue($darkPalette->isNamed());
        $this->assertSame(3, $darkPalette->count());
    }

    public function testBackgroundLightnessInversion(): void
    {
        $white = Color::parse('#ffffff');
        $palette = ColorPalette::named(['bg' => $white]);

        $transformer = new DarkModeColorPaletteTransformer(bgLevel: 0.15);
        $darkPalette = $transformer->transform($palette);

        $darkBg = $darkPalette->get('bg')->to('oklch');
        $this->assertInstanceOf(OklchColor::class, $darkBg);
        $this->assertLessThan(0.3, $darkBg->l);
    }

    public function testTextLightnessInversion(): void
    {
        $black = Color::parse('#000000');
        $palette = ColorPalette::named(['text' => $black]);

        $transformer = new DarkModeColorPaletteTransformer(textLevel: 0.95);
        $darkPalette = $transformer->transform($palette);

        $darkText = $darkPalette->get('text')->to('oklch');
        $this->assertInstanceOf(OklchColor::class, $darkText);
        $this->assertGreaterThan(0.85, $darkText->l);
    }

    public function testChromaDampening(): void
    {
        $vibrantBlue = Color::parse('#2563eb');
        $palette = ColorPalette::named(['primary' => $vibrantBlue]);

        $transformer = new DarkModeColorPaletteTransformer(chromaDampening: 0.85);
        $darkPalette = $transformer->transform($palette);

        $originalOklch = $vibrantBlue->to('oklch');
        $darkOklch = $darkPalette->get('primary')->to('oklch');

        $this->assertInstanceOf(OklchColor::class, $originalOklch);
        $this->assertInstanceOf(OklchColor::class, $darkOklch);

        if ($originalOklch->c > 0.05) {
            $this->assertLessThan($originalOklch->c, $darkOklch->c);
        }
    }

    public function testNoChromaDampeningForLowChroma(): void
    {
        $gray = Color::parse('#808080');
        $palette = ColorPalette::named(['gray' => $gray]);

        $transformer = new DarkModeColorPaletteTransformer(chromaDampening: 0.5);
        $darkPalette = $transformer->transform($palette);

        $originalOklch = $gray->to('oklch');
        $darkOklch = $darkPalette->get('gray')->to('oklch');

        $this->assertInstanceOf(OklchColor::class, $originalOklch);
        $this->assertInstanceOf(OklchColor::class, $darkOklch);

        if ($originalOklch->c <= 0.05) {
            $this->assertEqualsWithDelta($originalOklch->c, $darkOklch->c, 0.01);
        }
    }

    public function testCustomParameters(): void
    {
        $palette = ColorPalette::named([
            'surface' => Color::parse('#ffffff'),
        ]);

        $transformer = new DarkModeColorPaletteTransformer(
            bgLevel: 0.1,
            textLevel: 0.9,
            chromaDampening: 0.7,
        );

        $darkPalette = $transformer->transform($palette);
        $this->assertInstanceOf(ColorPalette::class, $darkPalette);
    }

    public function testMidtoneLightness(): void
    {
        $midtone = Color::parse('#808080');
        $palette = ColorPalette::named(['mid' => $midtone]);

        $transformer = new DarkModeColorPaletteTransformer();
        $darkPalette = $transformer->transform($palette);

        $originalOklch = $midtone->to('oklch');
        $darkOklch = $darkPalette->get('mid')->to('oklch');

        $this->assertInstanceOf(OklchColor::class, $originalOklch);
        $this->assertInstanceOf(OklchColor::class, $darkOklch);

        $this->assertGreaterThanOrEqual($originalOklch->l, $darkOklch->l);
    }

    public function testPreservesColorSpace(): void
    {
        $srgb = Color::parse('#ff0000');
        $displayP3 = $srgb->to('display-p3');

        $palette = ColorPalette::named([
            'srgb' => $srgb,
            'p3' => $displayP3,
        ]);

        $transformer = new DarkModeColorPaletteTransformer();
        $darkPalette = $transformer->transform($palette);

        $this->assertSame('srgb', $darkPalette->get('srgb')::getSpaceName());
        $this->assertSame('display-p3', $darkPalette->get('p3')::getSpaceName());
    }

    public function testMultipleColors(): void
    {
        $palette = ColorPalette::named([
            'bg' => Color::parse('#ffffff'),
            'text' => Color::parse('#000000'),
            'primary' => Color::parse('#2563eb'),
            'secondary' => Color::parse('#10b981'),
            'accent' => Color::parse('#f59e0b'),
        ]);

        $transformer = new DarkModeColorPaletteTransformer();
        $darkPalette = $transformer->transform($palette);

        $this->assertSame(5, $darkPalette->count());
        $this->assertTrue($darkPalette->isNamed());

        foreach (['bg', 'text', 'primary', 'secondary', 'accent'] as $name) {
            $this->assertNotNull($darkPalette->get($name));
        }
    }
}
