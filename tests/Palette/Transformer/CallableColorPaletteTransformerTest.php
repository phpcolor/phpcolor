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
use PhpColor\Color\ColorInterface;
use PhpColor\Color\OklchColor;
use PhpColor\Color\Palette\ColorPalette;
use PhpColor\Color\Palette\Transformer\CallableColorPaletteTransformer;
use PhpColor\Color\Palette\Transformer\ColorPaletteTransformerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CallableColorPaletteTransformer::class)]
final class CallableColorPaletteTransformerTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $transformer = new CallableColorPaletteTransformer(static fn (ColorInterface $c): ColorInterface => $c);

        $this->assertInstanceOf(ColorPaletteTransformerInterface::class, $transformer);
    }

    public function testTransformWithIdentityCallable(): void
    {
        $palette = ColorPalette::named([
            'primary' => Color::parse('#ff0000'),
            'secondary' => Color::parse('#00ff00'),
        ]);

        $transformer = new CallableColorPaletteTransformer(static fn (ColorInterface $c): ColorInterface => $c);
        $result = $transformer->transform($palette);

        $this->assertInstanceOf(ColorPalette::class, $result);
        $this->assertTrue($result->isNamed());
        $this->assertSame(2, $result->count());
    }

    public function testTransformAppliesCallableToEachColor(): void
    {
        $palette = ColorPalette::named([
            'light' => Color::parse('#ffffff'),
            'dark' => Color::parse('#000000'),
        ]);

        // Darken all colors by 50%
        $transformer = new CallableColorPaletteTransformer(static fn (ColorInterface $c): ColorInterface => $c->darken(0.5));
        $result = $transformer->transform($palette);

        $lightOklch = $result->get('light')->to('oklch');
        $this->assertInstanceOf(OklchColor::class, $lightOklch);
        $this->assertLessThan(1.0, $lightOklch->l);
    }

    public function testSepiaTransformation(): void
    {
        $palette = ColorPalette::named([
            'surface' => Color::parse('#ffffff'),
            'text' => Color::parse('#000000'),
            'accent' => Color::parse('#ff0000'),
        ]);

        // Sepia effect: reduce chroma and shift hue to warm tones
        $sepia = new CallableColorPaletteTransformer(static function (ColorInterface $color): ColorInterface {
            $oklch = $color->to('oklch');
            if (!$oklch instanceof OklchColor) {
                $oklch = OklchColor::fromSrgb($oklch->toSrgb());
            }

            return new OklchColor(
                $oklch->l,
                min(0.05, $oklch->c * 0.3),
                70.0, // Warm hue angle
                $oklch->alpha,
            );
        });

        $sepiaPalette = $sepia->transform($palette);

        $this->assertInstanceOf(ColorPalette::class, $sepiaPalette);
        $this->assertSame(3, $sepiaPalette->count());

        // Check that all colors have warm hue
        foreach (['surface', 'text', 'accent'] as $name) {
            $oklch = $sepiaPalette->get($name)->to('oklch');
            $this->assertInstanceOf(OklchColor::class, $oklch);
            $this->assertEqualsWithDelta(70.0, $oklch->h, 0.01);
        }
    }

    public function testGrayscaleTransformation(): void
    {
        $palette = ColorPalette::named([
            'red' => Color::parse('#ff0000'),
            'blue' => Color::parse('#0000ff'),
        ]);

        // Grayscale effect: remove all chroma
        $grayscale = new CallableColorPaletteTransformer(static function (ColorInterface $color): ColorInterface {
            $oklch = $color->to('oklch');
            if (!$oklch instanceof OklchColor) {
                $oklch = OklchColor::fromSrgb($oklch->toSrgb());
            }

            return new OklchColor(
                $oklch->l,
                0.0,
                $oklch->h,
                $oklch->alpha,
            );
        });

        $grayPalette = $grayscale->transform($palette);

        foreach (['red', 'blue'] as $name) {
            $oklch = $grayPalette->get($name)->to('oklch');
            $this->assertInstanceOf(OklchColor::class, $oklch);
            $this->assertEqualsWithDelta(0.0, $oklch->c, 0.01);
        }
    }

    public function testHueRotationTransformation(): void
    {
        $palette = ColorPalette::named([
            'primary' => Color::parse('#ff0000'), // Red
        ]);

        // Rotate hue by 120 degrees (red -> green)
        $rotator = new CallableColorPaletteTransformer(static fn (ColorInterface $color): ColorInterface => $color->rotateHue(120.0));

        $rotatedPalette = $rotator->transform($palette);

        $originalOklch = $palette->get('primary')->to('oklch');
        $rotatedOklch = $rotatedPalette->get('primary')->to('oklch');

        $this->assertInstanceOf(OklchColor::class, $originalOklch);
        $this->assertInstanceOf(OklchColor::class, $rotatedOklch);

        // Hue should be rotated by 120 degrees (with tolerance for gamut mapping)
        $expectedHue = fmod($originalOklch->h + 120.0, 360.0);
        $this->assertEqualsWithDelta($expectedHue, $rotatedOklch->h, 10.0);
    }

    public function testPreservesColorNames(): void
    {
        $palette = ColorPalette::named([
            'bg-primary' => Color::parse('#ffffff'),
            'text-primary' => Color::parse('#111827'),
            'brand-accent' => Color::parse('#2563eb'),
        ]);

        $transformer = new CallableColorPaletteTransformer(static fn (ColorInterface $c): ColorInterface => $c->lighten(0.1));
        $result = $transformer->transform($palette);

        $this->assertTrue($result->isNamed());
        $names = $result->names();

        $this->assertContains('bg-primary', $names);
        $this->assertContains('text-primary', $names);
        $this->assertContains('brand-accent', $names);
    }

    public function testClosureWithExternalState(): void
    {
        $palette = ColorPalette::named([
            'a' => Color::parse('#ff0000'),
            'b' => Color::parse('#00ff00'),
        ]);

        $amount = 0.25;
        $transformer = new CallableColorPaletteTransformer(
            static fn (ColorInterface $c): ColorInterface => $c->desaturate($amount),
        );

        $result = $transformer->transform($palette);

        $this->assertInstanceOf(ColorPalette::class, $result);
        $this->assertSame(2, $result->count());
    }

    public function testInvokableClass(): void
    {
        $palette = ColorPalette::named([
            'primary' => Color::parse('#2563eb'),
        ]);

        $invokable = new class {
            public function __invoke(ColorInterface $color): ColorInterface
            {
                return $color->lighten(0.2);
            }
        };

        $transformer = new CallableColorPaletteTransformer($invokable);
        $result = $transformer->transform($palette);

        $this->assertInstanceOf(ColorPalette::class, $result);
    }

    public function testStaticMethodCallable(): void
    {
        $palette = ColorPalette::named([
            'primary' => Color::parse('#2563eb'),
        ]);

        $transformer = new CallableColorPaletteTransformer(
            static fn (ColorInterface $c): ColorInterface => $c->saturate(0.1),
        );

        $result = $transformer->transform($palette);
        $this->assertInstanceOf(ColorPalette::class, $result);
    }
}
