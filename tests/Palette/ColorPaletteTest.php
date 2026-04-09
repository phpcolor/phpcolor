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

namespace PhpColor\Color\Tests\Palette;

use PhpColor\Color\AbstractColor;
use PhpColor\Color\Color;
use PhpColor\Color\ColorInterface;
use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\OklchColor;
use PhpColor\Color\Palette\ColorPalette;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ColorPalette::class)]
final class ColorPaletteTest extends TestCase
{
    public function testGetIterator(): void
    {
        $colors = [Color::parse('#ff0000'), Color::parse('#00ff00')];
        $palette = ColorPalette::scale($colors);

        $iterated = [];
        foreach ($palette as $key => $color) {
            $iterated[$key] = $color;
        }

        $this->assertSame($colors, $iterated);
    }

    public function testGetIteratorNamed(): void
    {
        $colors = ['red' => Color::parse('#ff0000'), 'blue' => Color::parse('#0000ff')];
        $palette = ColorPalette::named($colors);

        $iterated = [];
        foreach ($palette as $key => $color) {
            $iterated[$key] = $color;
        }

        $this->assertSame($colors, $iterated);
    }

    public function testAll(): void
    {
        $colors = [
            Color::parse('#ff0000'),
            Color::parse('#00ff00'),
        ];
        $palette = ColorPalette::scale($colors);

        $this->assertSame($colors, $palette->all());
    }

    public function testAt(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00', '#0000ff']);

        $start = $palette->at(0.0);
        $middle = $palette->at(0.5);
        $end = $palette->at(1.0);

        $this->assertSame('#ff0000', $start->toHex());
        $this->assertSame('#0000ff', $end->toHex());
        $this->assertNotSame('#ff0000', $middle->toHex());
    }

    public function testAtClampsBounds(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00']);

        $before = $palette->at(-0.5);
        $after = $palette->at(1.5);

        $this->assertSame('#ff0000', $before->toHex());
        $this->assertSame('#00ff00', $after->toHex());
    }

    public function testAtInterpolation(): void
    {
        // Test that at() interpolates correctly
        $red = Color::parse('#ff0000');
        $blue = Color::parse('#0000ff');
        $palette = ColorPalette::scale([$red, $blue]);

        $mid = $palette->at(0.5);
        $expected = Color::mix($red, $blue, 0.5, 'oklab');

        $this->assertEqualsWithDelta(
            $expected->to('oklab')->l,
            $mid->to('oklab')->l,
            0.01
        );
    }

    public function testAtNamedThrows(): void
    {
        $palette = ColorPalette::fromHex(['red' => '#ff0000', 'green' => '#00ff00']);

        $this->expectException(InvalidColorException::class);
        $palette->at(0.5);
    }

    public function testAtNearEdge(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00', '#0000ff']);

        // Very close to 1.0 but not exactly
        $at099 = $palette->at(0.99);

        // Should interpolate but be very close to last color
        $srgb = $at099->toSrgb();
        $this->assertGreaterThan(0.9, $srgb->b);
    }

    public function testAtSingleColor(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000']);
        $color = $palette->at(0.5);

        $this->assertSame('#ff0000', $color->toHex());
    }

    public function testAtWithEmptyPaletteThrows(): void
    {
        $palette = ColorPalette::scale([Color::parse('#ff0000')]);
        $filtered = $palette->filter(static fn (): false => false);

        $this->expectException(InvalidColorException::class);
        $filtered->at(0.5);
    }

    public function testAtWithExactIndices(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00', '#0000ff']);

        // At exact indices (0.0, 0.5, 1.0)
        $at0 = $palette->at(0.0);
        $at05 = $palette->at(0.5);
        $at1 = $palette->at(1.0);

        $this->assertSame('#ff0000', $at0->toHex());
        $this->assertSame('#00ff00', $at05->toHex());
        $this->assertSame('#0000ff', $at1->toHex());
    }

    public function testAtWithFractionalPosition(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00', '#0000ff']);

        // At fractional position (should interpolate)
        $at025 = $palette->at(0.25);

        // Should be between red and green
        $srgb = $at025->toSrgb();
        $this->assertGreaterThan(0.0, $srgb->r);
        $this->assertGreaterThan(0.0, $srgb->g);
    }

    public function testClosest(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00', '#0000ff']);
        $target = Color::parse('#fe0000'); // Almost red

        $closest = $palette->closest($target);

        $this->assertSame('#ff0000', $closest->toHex());
    }

    public function testClosestCastsNonOklab(): void
    {
        $p = ColorPalette::scale([
            Color::parse('#ff0000'),
            $this->fake(0.0, 0.0, 1.0),
        ]);
        $target = $this->fake(0.9, 0.1, 0.1);
        $closest = $p->closest($target);
        $this->assertInstanceOf(ColorInterface::class, $closest);
    }

    public function testClosestEmptyThrows(): void
    {
        $palette = ColorPalette::scale([Color::parse('#ff0000')]);
        $filtered = $palette->filter(static fn (): false => false);

        $this->expectException(InvalidColorException::class);
        $filtered->closest(Color::parse('#000000'));
    }

    public function testClosestWithExactMatch(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00', '#0000ff']);
        $target = Color::parse('#00ff00');

        $closest = $palette->closest($target);

        $this->assertSame('#00ff00', $closest->toHex());
    }

    public function testClosestWithSingleColor(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000']);
        $target = Color::parse('#0000ff');

        $closest = $palette->closest($target);

        $this->assertSame('#ff0000', $closest->toHex());
    }

    public function testDarken(): void
    {
        $palette = ColorPalette::fromHex(['#3b82f6']);
        $darkened = $palette->darken(0.2);

        $original = $palette->get(0)->to('oklch');
        $modified = $darkened->get(0)->to('oklch');

        $this->assertLessThan($original->l, $modified->l);
    }

    public function testDesaturate(): void
    {
        $palette = ColorPalette::fromHex(['#3b82f6']);
        $desaturated = $palette->desaturate(0.1);

        $original = $palette->get(0)->to('oklch');
        $modified = $desaturated->get(0)->to('oklch');

        $this->assertLessThan($original->c, $modified->c);
    }

    public function testEmptyNamedThrows(): void
    {
        $this->expectException(InvalidColorException::class);
        ColorPalette::named([]);
    }

    public function testEmptyScaleThrows(): void
    {
        $this->expectException(InvalidColorException::class);
        ColorPalette::scale([]);
    }

    public function testFilter(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00', '#0000ff']);
        $filtered = $palette->filter(static fn (ColorInterface $c): bool => $c->toSrgb()->r > 0.5);

        $this->assertSame(1, $filtered->count());
    }

    public function testFilterKeepsAllColors(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00', '#0000ff']);
        $filtered = $palette->filter(static fn (): true => true);

        $this->assertSame(3, $filtered->count());
    }

    public function testFilterRemovesAllColors(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00', '#0000ff']);
        $filtered = $palette->filter(static fn (): false => false);

        $this->assertSame(0, $filtered->count());
    }

    public function testFromHexNamed(): void
    {
        $palette = ColorPalette::fromHex([
            'red' => '#ff0000',
            'green' => '#00ff00',
            'blue' => '#0000ff',
        ]);

        $this->assertTrue($palette->isNamed());
        $this->assertSame(3, $palette->count());
        $this->assertSame(['red', 'green', 'blue'], $palette->names());
    }

    public function testFromHexScale(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00', '#0000ff']);

        $this->assertFalse($palette->isNamed());
        $this->assertSame(3, $palette->count());
    }

    public function testGetByIndex(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00', '#0000ff']);
        $color = $palette->get(1);

        $this->assertEqualsWithDelta(0.0, $color->toSrgb()->r, 0.01);
        $this->assertEqualsWithDelta(1.0, $color->toSrgb()->g, 0.01);
    }

    public function testGetByName(): void
    {
        $palette = ColorPalette::fromHex(['red' => '#ff0000', 'green' => '#00ff00']);
        $color = $palette->get('green');

        $this->assertEqualsWithDelta(1.0, $color->toSrgb()->g, 0.01);
    }

    public function testGetInvalidKeyThrows(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000']);

        $this->expectException(InvalidColorException::class);
        $palette->get(5);
    }

    public function testInterpolate(): void
    {
        $red = Color::parse('#ff0000');
        $blue = Color::parse('#0000ff');
        $palette = ColorPalette::interpolate($red, $blue, 10);

        $this->assertSame(10, $palette->count());

        $colors = $palette->all();
        $first = array_shift($colors);
        $last = array_pop($colors);

        // First should be red, last should be blue
        $this->assertEqualsWithDelta(1.0, $first->toSrgb()->r, 0.01);
        $this->assertEqualsWithDelta(1.0, $last->toSrgb()->b, 0.01);
    }

    public function testInterpolateInDifferentSpace(): void
    {
        $red = Color::parse('#ff0000');
        $blue = Color::parse('#0000ff');
        $palette = ColorPalette::interpolate($red, $blue, 5, 'srgb');

        $this->assertSame(5, $palette->count());
        // Results should be different from oklab interpolation
        $this->assertInstanceOf(SrgbColor::class, $palette->get(0));
    }

    public function testInterpolateTooFewStepsThrows(): void
    {
        $this->expectException(InvalidColorException::class);
        ColorPalette::interpolate(Color::parse('#ff0000'), Color::parse('#0000ff'), 1);
    }

    public function testInterpolateTwoSteps(): void
    {
        $red = Color::parse('#ff0000');
        $blue = Color::parse('#0000ff');
        $palette = ColorPalette::interpolate($red, $blue, 2);

        $this->assertSame(2, $palette->count());

        // First should be red, last should be blue
        $colors = $palette->all();
        $this->assertSame('#ff0000', $colors[0]->toHex());
        $this->assertSame('#0000ff', $colors[1]->toHex());
    }

    public function testLighten(): void
    {
        $palette = ColorPalette::fromHex(['#3b82f6']);
        $lightened = $palette->lighten(0.2);

        $original = $palette->get(0)->to('oklch');
        $modified = $lightened->get(0)->to('oklch');

        $this->assertGreaterThan($original->l, $modified->l);
    }

    public function testLightenNegativeAmount(): void
    {
        $palette = ColorPalette::fromHex(['#808080']); // Gray
        $darkened = $palette->lighten(-0.2);

        $original = $palette->get(0)->to('oklch');
        $result = $darkened->get(0)->to('oklch');

        // Negative lighten should darken
        $this->assertLessThan($original->l, $result->l);
    }

    public function testLightnessScale(): void
    {
        $blue = Color::parse('#3b82f6');
        $palette = ColorPalette::lightnessScale($blue, 10);

        $this->assertSame(10, $palette->count());

        $colors = $palette->all();
        $oklchFirst = $colors[0]->to('oklch');
        $oklchLast = $colors[9]->to('oklch');

        $this->assertLessThan($oklchLast->l, $oklchFirst->l);
    }

    public function testLightnessScaleCastsNonOklch(): void
    {
        $fake = $this->fake(0.2, 0.4, 0.6);
        $palette = ColorPalette::lightnessScale($fake, 3);
        $this->assertSame(3, $palette->count());
        $this->assertStringStartsWith('oklch(', $palette->get(0)->toCss('oklch'));
    }

    public function testLightnessScaleWithCustomRange(): void
    {
        $blue = Color::parse('#3b82f6');
        $palette = ColorPalette::lightnessScale($blue, 5, 0.2, 0.8);

        $this->assertSame(5, $palette->count());

        $colors = $palette->all();
        $firstL = $colors[0]->to('oklch')->l;
        $lastL = $colors[4]->to('oklch')->l;

        $this->assertEqualsWithDelta(0.2, $firstL, 0.01);
        $this->assertEqualsWithDelta(0.8, $lastL, 0.01);
    }

    public function testLightnessScaleWithOneStep(): void
    {
        $blue = Color::parse('#3b82f6');
        $palette = ColorPalette::lightnessScale($blue, 1);

        $this->assertSame(1, $palette->count());

        // With 1 step, should use min lightness
        $color = $palette->get(0);
        $oklch = $color->to('oklch');
        $this->assertEqualsWithDelta(0.05, $oklch->l, 0.01);
    }

    public function testLightnessScaleWithZeroStepsThrows(): void
    {
        $this->expectException(InvalidColorException::class);
        ColorPalette::lightnessScale(Color::parse('#ff0000'), 0);
    }

    public function testMap(): void
    {
        $palette = ColorPalette::fromHex(['#3b82f6']);
        $lightened = $palette->map(static fn (ColorInterface $c): ColorInterface => $c->lighten(0.2));

        $this->assertSame(1, $lightened->count());
        $this->assertNotSame($palette->get(0), $lightened->get(0));
    }

    public function testMapMaintainsKeys(): void
    {
        $palette = ColorPalette::fromHex(['red' => '#ff0000', 'green' => '#00ff00']);
        $mapped = $palette->map(static fn ($c): ColorInterface => $c->lighten(0.1));

        $this->assertTrue($mapped->isNamed());
        $this->assertSame(['red', 'green'], $mapped->names());
    }

    public function testMerge(): void
    {
        $palette1 = ColorPalette::fromHex(['#ff0000', '#00ff00']);
        $palette2 = ColorPalette::fromHex(['#0000ff', '#ffff00']);
        $merged = $palette1->merge($palette2);

        $this->assertSame(4, $merged->count());
        $this->assertSame('#ff0000', $merged->get(0)->toHex());
        $this->assertSame('#ffff00', $merged->get(3)->toHex());
    }

    public function testMergeEmptyPalettes(): void
    {
        $palette1 = ColorPalette::scale([Color::parse('#ff0000')]);
        $palette2 = ColorPalette::scale([Color::parse('#00ff00')]);

        $filtered1 = $palette1->filter(static fn (): false => false); // Empty
        $merged = $filtered1->merge($palette2);

        $this->assertSame(1, $merged->count());
    }

    public function testMergeMixedNamedThrows(): void
    {
        $palette1 = ColorPalette::fromHex(['#ff0000']);
        $palette2 = ColorPalette::fromHex(['red' => '#ff0000']);

        $this->expectException(InvalidColorException::class);
        $palette2->merge($palette1);
    }

    public function testMergeNamedThrows(): void
    {
        $palette1 = ColorPalette::fromHex(['red' => '#ff0000']);
        $palette2 = ColorPalette::fromHex(['#0000ff']);

        $this->expectException(InvalidColorException::class);
        $palette1->merge($palette2);
    }

    public function testMergeWithSelf(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00']);
        $merged = $palette->merge($palette);

        // Should double the colors
        $this->assertSame(4, $merged->count());
    }

    public function testNamedCreation(): void
    {
        $colors = [
            'primary' => Color::parse('#3b82f6'),
            'secondary' => Color::parse('#10b981'),
            'accent' => Color::parse('#f59e0b'),
        ];

        $palette = ColorPalette::named($colors);

        $this->assertTrue($palette->isNamed());
        $this->assertSame(3, $palette->count());
        $this->assertSame(['primary', 'secondary', 'accent'], $palette->names());
    }

    public function testNamesOnScaleReturnsEmpty(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00']);

        $this->assertSame([], $palette->names());
    }

    public function testParseNamed(): void
    {
        $palette = ColorPalette::parse([
            'red' => 'rgb(255 0 0)',
            'green' => 'hsl(120 100% 50%)',
            'blue' => '#0000ff',
        ]);

        $this->assertTrue($palette->isNamed());
        $this->assertSame(3, $palette->count());
    }

    public function testParseScale(): void
    {
        $palette = ColorPalette::parse(['rgb(255 0 0)', 'hsl(120 100% 50%)', '#0000ff']);

        $this->assertFalse($palette->isNamed());
        $this->assertSame(3, $palette->count());
    }

    public function testReverse(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00', '#0000ff']);
        $reversed = $palette->reverse();

        $this->assertSame('#0000ff', $reversed->get(0)->toHex());
        $this->assertSame('#ff0000', $reversed->get(2)->toHex());
    }

    public function testReverseNamedThrows(): void
    {
        $palette = ColorPalette::fromHex(['red' => '#ff0000', 'green' => '#00ff00']);

        $this->expectException(InvalidColorException::class);
        $palette->reverse();
    }

    public function testRotateHue(): void
    {
        $palette = ColorPalette::fromHex(['#3b82f6']);
        $rotated = $palette->rotateHue(180);

        $original = $palette->get(0)->to('oklch');
        $modified = $rotated->get(0)->to('oklch');

        $this->assertNotEquals($original->h, $modified->h);
    }

    public function testRotateHueFullCircle(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000']);
        $rotated = $palette->rotateHue(360);

        $original = $palette->get(0)->to('oklch');
        $result = $rotated->get(0)->to('oklch');

        // 360 degree rotation should return similar hue (with rounding)
        $this->assertEqualsWithDelta($original->h, $result->h, 1.0);
    }

    public function testRotateHueNegative(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000']);
        $rotated = $palette->rotateHue(-180);

        $original = $palette->get(0)->to('oklch');
        $result = $rotated->get(0)->to('oklch');

        // Should rotate counterclockwise
        $this->assertNotEquals($original->h, $result->h);
    }

    public function testSaturate(): void
    {
        $palette = ColorPalette::fromHex(['#3b82f6']);
        $saturated = $palette->saturate(0.1);

        $original = $palette->get(0)->to('oklch');
        $modified = $saturated->get(0)->to('oklch');

        $this->assertGreaterThan($original->c, $modified->c);
    }

    public function testSaturateNegativeAmount(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000']);
        $desaturated = $palette->saturate(-0.1);

        $original = $palette->get(0)->to('oklch');
        $result = $desaturated->get(0)->to('oklch');

        // Negative saturate should desaturate
        $this->assertLessThan($original->c, $result->c);
    }

    public function testScaleCreation(): void
    {
        $colors = [
            Color::parse('#ff0000'),
            Color::parse('#00ff00'),
            Color::parse('#0000ff'),
        ];

        $palette = ColorPalette::scale($colors);

        $this->assertFalse($palette->isNamed());
        $this->assertSame(3, $palette->count());
    }

    public function testShades(): void
    {
        $red = Color::parse('#ff0000');
        $palette = ColorPalette::shades($red, 5);

        $this->assertFalse($palette->isNamed());
        $this->assertSame(5, $palette->count());

        $colors = $palette->all();
        $last = array_pop($colors);

        // Last should be black
        $this->assertEqualsWithDelta(0.0, $last->toSrgb()->r, 0.01);
        $this->assertEqualsWithDelta(0.0, $last->toSrgb()->g, 0.01);
        $this->assertEqualsWithDelta(0.0, $last->toSrgb()->b, 0.01);
    }

    public function testShadesWithOneStep(): void
    {
        $red = Color::parse('#ff0000');
        $palette = ColorPalette::shades($red, 1);

        $this->assertSame(1, $palette->count());

        // With 1 step, t = 0.0, should return original color
        $color = $palette->get(0);
        $this->assertEqualsWithDelta(1.0, $color->toSrgb()->r, 0.01);
    }

    public function testShadesWithZeroStepsThrows(): void
    {
        $this->expectException(InvalidColorException::class);
        ColorPalette::shades(Color::parse('#ff0000'), 0);
    }

    public function testSlice(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00', '#0000ff', '#ffff00']);
        $sliced = $palette->slice(1, 2);

        $this->assertSame(2, $sliced->count());
        $this->assertSame('#00ff00', $sliced->get(1)->toHex());
    }

    public function testSliceNamedThrows(): void
    {
        $palette = ColorPalette::fromHex(['red' => '#ff0000', 'green' => '#00ff00']);

        $this->expectException(InvalidColorException::class);
        $palette->slice(0, 1);
    }

    public function testSliceWithNegativeOffset(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00', '#0000ff', '#ffff00']);
        $sliced = $palette->slice(-2); // Last 2 colors

        $this->assertSame(2, $sliced->count());
    }

    public function testSliceWithOffsetOnly(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00', '#0000ff', '#ffff00']);
        $sliced = $palette->slice(2); // No length specified

        // Should take all colors from offset 2 to end
        $this->assertSame(2, $sliced->count());
        $this->assertSame('#0000ff', $sliced->get(2)->toHex());
        $this->assertSame('#ffff00', $sliced->get(3)->toHex());
    }

    public function testSliceZeroLength(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00', '#0000ff']);
        $sliced = $palette->slice(1, 0);

        $this->assertSame(0, $sliced->count());
    }

    public function testTints(): void
    {
        $red = Color::parse('#ff0000');
        $palette = ColorPalette::tints($red, 5);

        $this->assertFalse($palette->isNamed());
        $this->assertSame(5, $palette->count());

        $colors = $palette->all();
        $first = array_shift($colors);
        $last = array_pop($colors);

        // First should be original, last should be white
        $this->assertEqualsWithDelta(1.0, $first->toSrgb()->r, 0.01);
        $this->assertEqualsWithDelta(1.0, $last->toSrgb()->r, 0.01);
        $this->assertEqualsWithDelta(1.0, $last->toSrgb()->g, 0.01);
        $this->assertEqualsWithDelta(1.0, $last->toSrgb()->b, 0.01);
    }

    public function testTintsWithNegativeStepsThrows(): void
    {
        $this->expectException(InvalidColorException::class);
        ColorPalette::tints(Color::parse('#ff0000'), -5);
    }

    public function testTintsWithOneStep(): void
    {
        $red = Color::parse('#ff0000');
        $palette = ColorPalette::tints($red, 1);

        $this->assertSame(1, $palette->count());

        // With 1 step, t = 0.0, should return original color
        $color = $palette->get(0);
        $this->assertEqualsWithDelta(1.0, $color->toSrgb()->r, 0.01);
    }

    public function testTintsWithZeroStepsThrows(): void
    {
        $this->expectException(InvalidColorException::class);
        ColorPalette::tints(Color::parse('#ff0000'), 0);
    }

    public function testToColorSpace(): void
    {
        $palette = ColorPalette::fromHex(['#3b82f6']);
        $converted = $palette->to('oklch');

        $color = $converted->get(0);
        $this->assertInstanceOf(OklchColor::class, $color);
    }

    public function testToCss(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00']);
        $css = $palette->toCss();

        $this->assertSame(2, \count($css));
        $this->assertStringContainsString('rgb', $css[0]);
    }

    public function testToCssPreservesKeys(): void
    {
        $palette = ColorPalette::fromHex(['red' => '#ff0000', 'green' => '#00ff00']);
        $css = $palette->toCss();

        $this->assertArrayHasKey('red', $css);
        $this->assertArrayHasKey('green', $css);
    }

    public function testToCssVariables(): void
    {
        $palette = ColorPalette::fromHex([
            'primary' => '#3b82f6',
            'secondary' => '#10b981',
        ]);

        $css = $palette->toCssVariables('color');

        $this->assertStringContainsString('--color-primary: #3b82f6', $css);
        $this->assertStringContainsString('--color-secondary: #10b981', $css);
    }

    public function testToCssVariablesFormatting(): void
    {
        $palette = ColorPalette::fromHex(['test' => '#ff0000']);
        $css = $palette->toCssVariables('color');

        $this->assertStringContainsString('--color-test: #ff0000;', $css);
    }

    public function testToCssVariablesScaleThrows(): void
    {
        $palette = ColorPalette::fromHex(['#3b82f6', '#10b981']);

        $this->expectException(InvalidColorException::class);
        $palette->toCssVariables();
    }

    public function testToCssVariablesWithCustomPrefix(): void
    {
        $palette = ColorPalette::fromHex([
            'primary' => '#3b82f6',
            'secondary' => '#10b981',
        ]);

        $css = $palette->toCssVariables('theme');

        $this->assertStringContainsString('--theme-primary', $css);
        $this->assertStringContainsString('--theme-secondary', $css);
    }

    public function testToCssWithSpace(): void
    {
        // Convert palette to oklch first, then toCss
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00']);
        $oklchPalette = $palette->to('oklch');
        $css = $oklchPalette->toCss();

        $this->assertSame(2, \count($css));
        // Should output oklch format
        foreach ($css as $colorCss) {
            $this->assertStringContainsString('oklch', $colorCss);
        }
    }

    public function testToHex(): void
    {
        $palette = ColorPalette::fromHex(['#ff0000', '#00ff00', '#0000ff']);
        $hex = $palette->toHex();

        $this->assertSame(['#ff0000', '#00ff00', '#0000ff'], $hex);
    }

    public function testToHexNamed(): void
    {
        $palette = ColorPalette::fromHex(['red' => '#ff0000', 'green' => '#00ff00']);
        $hex = $palette->toHex();

        $this->assertSame(['red' => '#ff0000', 'green' => '#00ff00'], $hex);
    }

    private function fake(float $r, float $g, float $b, float $a = 1.0): ColorInterface
    {
        return new readonly class(new SrgbColor($r, $g, $b, $a)) extends AbstractColor {
            public function __construct(private SrgbColor $inner)
            {
            }

            public static function fromSrgb(SrgbColor $srgb): static
            {
                return new self($srgb);
            }

            public static function getSpaceName(): string
            {
                return 'fake';
            }

            public function getAlpha(): float
            {
                return $this->inner->a;
            }

            public function getChannels(): array
            {
                return ['r' => $this->inner->r, 'g' => $this->inner->g, 'b' => $this->inner->b];
            }

            public function toCss(?string $space = null): string
            {
                return $this->inner->toCss($space);
            }

            public function toSrgb(): SrgbColor
            {
                return $this->inner;
            }

            protected static function fromChannels(array $channels, float $alpha = 1.0): static
            {
                return new self(new SrgbColor($channels['r'] ?? 0, $channels['g'] ?? 0, $channels['b'] ?? 0, $alpha));
            }

            public function to(string|ColorInterface $space): ColorInterface
            {
                // Return a non-OKLCH instance when asked for 'oklch' to trigger conversion branch
                if (\is_string($space) && 'oklch' === strtolower($space)) {
                    return new SrgbColor($this->inner->r, $this->inner->g, $this->inner->b, $this->inner->a);
                }
                if (\is_string($space) && 'oklab' === strtolower($space)) {
                    return new SrgbColor($this->inner->r, $this->inner->g, $this->inner->b, $this->inner->a);
                }

                return $this->inner->to($space);
            }
        };
    }
}
