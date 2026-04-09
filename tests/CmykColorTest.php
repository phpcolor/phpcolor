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

namespace PhpColor\Color\Tests;

use PhpColor\Color\CmykColor;
use PhpColor\Color\Color;
use PhpColor\Color\ColorInterface;
use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\Exception\ParseException;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(CmykColor::class)]
final class CmykColorTest extends ColorTestCase
{
    protected function createColor(): ColorInterface
    {
        return new CmykColor(0.5, 0.3, 0.2, 0.1, 0.8);
    }

    protected function getExpectedColorClass(): string
    {
        return CmykColor::class;
    }

    public static function provideColorSamples(): iterable
    {
        // color, hex, hexWithAlpha, css
        yield 'pure cyan' => [
            new CmykColor(1.0, 0.0, 0.0, 0.0),
            '#00ffff',
            '#00ffffff',
            'device-cmyk(100% 0% 0% 0%)',
        ];
        yield 'pure magenta' => [
            new CmykColor(0.0, 1.0, 0.0, 0.0),
            '#ff00ff',
            '#ff00ffff',
            'device-cmyk(0% 100% 0% 0%)',
        ];
        yield 'pure yellow' => [
            new CmykColor(0.0, 0.0, 1.0, 0.0),
            '#ffff00',
            '#ffff00ff',
            'device-cmyk(0% 0% 100% 0%)',
        ];
        yield 'pure black' => [
            new CmykColor(0.0, 0.0, 0.0, 1.0),
            '#000000',
            '#000000ff',
            'device-cmyk(0% 0% 0% 100%)',
        ];
        yield 'translucent color' => [
            new CmykColor(0.5, 0.3, 0.2, 0.1, 0.5),
            '#73a1b8',
            '#73a1b880',
            'device-cmyk(50% 30% 20% 10% / 0.5)',
        ];
    }

    public static function provideFromInputs(): iterable
    {
        yield [new SrgbColor(1.0, 0.0, 0.0)];
        yield ['#ff0000'];
        yield ['device-cmyk(100% 0% 0% 0%)'];
    }

    public static function provideInvalidCssOutputSpaces(): array
    {
        return [
            ['lab'],
            ['lch'],
            ['oklab'],
            ['oklch'],
            ['display-p3'],
            ['xyz'],
            ['a98-rgb'],
            ['rec2020'],
            ['prophoto-rgb'],
            ['hsl'],
            ['hwb'],
        ];
    }

    public function testAlphaIsClamped(): void
    {
        $cmyk1 = new CmykColor(0.5, 0.5, 0.5, 0.5, -0.1);
        $this->assertSame(0.0, $cmyk1->alpha);

        $cmyk2 = new CmykColor(0.5, 0.5, 0.5, 0.5, 1.5);
        $this->assertSame(1.0, $cmyk2->alpha);
    }

    public function testChannelGetters(): void
    {
        $c = new CmykColor(0.6, 0.4, 0.3, 0.2, 0.9);
        $this->assertSame(0.6, $c->getCyan());
        $this->assertSame(0.4, $c->getMagenta());
        $this->assertSame(0.3, $c->getYellow());
        $this->assertSame(0.2, $c->getBlack());
        $this->assertSame(0.9, $c->getAlpha());
    }

    public function testChannelsAreClamped(): void
    {
        // Test upper clamping
        $cmyk1 = new CmykColor(1.5, 2.0, 3.0, 4.0);
        $this->assertSame(1.0, $cmyk1->c);
        $this->assertSame(1.0, $cmyk1->m);
        $this->assertSame(1.0, $cmyk1->y);
        $this->assertSame(1.0, $cmyk1->k);

        // Test lower clamping
        $cmyk2 = new CmykColor(-0.5, -1.0, -2.0, -3.0);
        $this->assertSame(0.0, $cmyk2->c);
        $this->assertSame(0.0, $cmyk2->m);
        $this->assertSame(0.0, $cmyk2->y);
        $this->assertSame(0.0, $cmyk2->k);
    }

    public function testConstruction(): void
    {
        $cmyk = new CmykColor(0.5, 0.3, 0.2, 0.1, 0.8);

        $this->assertSame(0.5, $cmyk->c);
        $this->assertSame(0.3, $cmyk->m);
        $this->assertSame(0.2, $cmyk->y);
        $this->assertSame(0.1, $cmyk->k);
        $this->assertSame(0.8, $cmyk->alpha);
    }

    #[\Override]
    public function testFromChannels(): void
    {
        $channels = ['c' => 0.7, 'm' => 0.5, 'y' => 0.3, 'k' => 0.1];
        $cmyk = CmykColor::fromChannels($channels, 0.9);

        $this->assertSame(0.7, $cmyk->c);
        $this->assertSame(0.5, $cmyk->m);
        $this->assertSame(0.3, $cmyk->y);
        $this->assertSame(0.1, $cmyk->k);
        $this->assertSame(0.9, $cmyk->alpha);
    }

    public function testFromChannelsWithMissingValues(): void
    {
        $channels = ['c' => 0.5];
        $cmyk = CmykColor::fromChannels($channels);

        $this->assertSame(0.5, $cmyk->c);
        $this->assertSame(0.0, $cmyk->m);
        $this->assertSame(0.0, $cmyk->y);
        $this->assertSame(0.0, $cmyk->k);
    }

    public function testFromSrgbBlack(): void
    {
        $srgb = new SrgbColor(0.0, 0.0, 0.0);
        $cmyk = CmykColor::fromSrgb($srgb);

        $this->assertSame(0.0, $cmyk->c);
        $this->assertSame(0.0, $cmyk->m);
        $this->assertSame(0.0, $cmyk->y);
        $this->assertSame(1.0, $cmyk->k);
    }

    public function testFromSrgbCyan(): void
    {
        $srgb = new SrgbColor(0.0, 1.0, 1.0);
        $cmyk = CmykColor::fromSrgb($srgb);

        $this->assertSame(1.0, $cmyk->c);
        $this->assertEqualsWithDelta(0.0, $cmyk->m, 0.0001);
        $this->assertEqualsWithDelta(0.0, $cmyk->y, 0.0001);
        $this->assertSame(0.0, $cmyk->k);
    }

    public function testFromSrgbGray(): void
    {
        $srgb = new SrgbColor(0.5, 0.5, 0.5);
        $cmyk = CmykColor::fromSrgb($srgb);

        $this->assertEqualsWithDelta(0.0, $cmyk->c, 0.0001);
        $this->assertEqualsWithDelta(0.0, $cmyk->m, 0.0001);
        $this->assertEqualsWithDelta(0.0, $cmyk->y, 0.0001);
        $this->assertSame(0.5, $cmyk->k);
    }

    public function testFromSrgbMagenta(): void
    {
        $srgb = new SrgbColor(1.0, 0.0, 1.0);
        $cmyk = CmykColor::fromSrgb($srgb);

        $this->assertEqualsWithDelta(0.0, $cmyk->c, 0.0001);
        $this->assertSame(1.0, $cmyk->m);
        $this->assertEqualsWithDelta(0.0, $cmyk->y, 0.0001);
        $this->assertSame(0.0, $cmyk->k);
    }

    public function testFromSrgbRed(): void
    {
        $srgb = new SrgbColor(1.0, 0.0, 0.0);
        $cmyk = CmykColor::fromSrgb($srgb);

        $this->assertSame(0.0, $cmyk->c);
        $this->assertSame(1.0, $cmyk->m);
        $this->assertSame(1.0, $cmyk->y);
        $this->assertSame(0.0, $cmyk->k);
    }

    public function testFromSrgbWhite(): void
    {
        $srgb = new SrgbColor(1.0, 1.0, 1.0);
        $cmyk = CmykColor::fromSrgb($srgb);

        $this->assertSame(0.0, $cmyk->c);
        $this->assertSame(0.0, $cmyk->m);
        $this->assertSame(0.0, $cmyk->y);
        $this->assertSame(0.0, $cmyk->k);
    }

    public function testFromSrgbYellow(): void
    {
        $srgb = new SrgbColor(1.0, 1.0, 0.0);
        $cmyk = CmykColor::fromSrgb($srgb);

        $this->assertEqualsWithDelta(0.0, $cmyk->c, 0.0001);
        $this->assertEqualsWithDelta(0.0, $cmyk->m, 0.0001);
        $this->assertSame(1.0, $cmyk->y);
        $this->assertSame(0.0, $cmyk->k);
    }

    #[\Override]
    public function testGetChannels(): void
    {
        $cmyk = new CmykColor(0.6, 0.4, 0.3, 0.2, 0.9);
        $channels = $cmyk->getChannels();

        $this->assertSame(['c' => 0.6, 'm' => 0.4, 'y' => 0.3, 'k' => 0.2], $channels);
    }

    public function testGetSpaceName(): void
    {
        $this->assertSame('cmyk', CmykColor::getSpaceName());
    }

    #[DataProvider('provideParseExamples')]
    public function testParse(string $input, float $c, float $m, float $y, float $k, float $alpha = 1.0): void
    {
        $cmyk = CmykColor::parse($input);

        $this->assertEqualsWithDelta($c, $cmyk->c, 0.0001);
        $this->assertEqualsWithDelta($m, $cmyk->m, 0.0001);
        $this->assertEqualsWithDelta($y, $cmyk->y, 0.0001);
        $this->assertEqualsWithDelta($k, $cmyk->k, 0.0001);
        $this->assertEqualsWithDelta($alpha, $cmyk->alpha, 0.0001);
    }

    public static function provideParseExamples(): iterable
    {
        yield 'percentages' => ['device-cmyk(100% 50% 25% 0%)', 1.0, 0.5, 0.25, 0.0];
        yield 'percentages with alpha' => ['device-cmyk(100% 50% 25% 0% / 0.5)', 1.0, 0.5, 0.25, 0.0, 0.5];
        yield 'decimals' => ['device-cmyk(1 0.5 0.25 0)', 1.0, 0.5, 0.25, 0.0];
        yield 'decimals with alpha' => ['device-cmyk(1 0.5 0.25 0 / 0.8)', 1.0, 0.5, 0.25, 0.0, 0.8];
        yield 'mixed format' => ['device-cmyk(100% 0.5 25% 0)', 1.0, 0.5, 0.25, 0.0];
        yield 'with spaces' => ['device-cmyk(  50%  30%  20%  10%  )', 0.5, 0.3, 0.2, 0.1];
    }

    public function testParseInvalidFormat(): void
    {
        $this->expectException(ParseException::class);
        CmykColor::parse('not-valid');
    }

    public function testParseInvalidFormatMissingChannel(): void
    {
        $this->expectException(ParseException::class);
        CmykColor::parse('device-cmyk(100% 50% 25%)');
    }

    public function testRoundTripConversionBlack(): void
    {
        $original = new CmykColor(0.0, 0.0, 0.0, 1.0);
        $srgb = $original->toSrgb();
        $back = CmykColor::fromSrgb($srgb);

        $this->assertEqualsWithDelta($original->c, $back->c, 0.0001);
        $this->assertEqualsWithDelta($original->m, $back->m, 0.0001);
        $this->assertEqualsWithDelta($original->y, $back->y, 0.0001);
        $this->assertEqualsWithDelta($original->k, $back->k, 0.0001);
    }

    public function testRoundTripConversionCyan(): void
    {
        $original = new CmykColor(1.0, 0.0, 0.0, 0.0);
        $srgb = $original->toSrgb();
        $back = CmykColor::fromSrgb($srgb);

        $this->assertEqualsWithDelta($original->c, $back->c, 0.0001);
        $this->assertEqualsWithDelta($original->m, $back->m, 0.0001);
        $this->assertEqualsWithDelta($original->y, $back->y, 0.0001);
        $this->assertEqualsWithDelta($original->k, $back->k, 0.0001);
    }

    public function testRoundTripConversionMixed(): void
    {
        $original = new CmykColor(0.5, 0.3, 0.2, 0.1);
        $srgb = $original->toSrgb();
        $back = CmykColor::fromSrgb($srgb);
        $roundTrip = $back->toSrgb();

        $this->assertEqualsWithDelta($srgb->r, $roundTrip->r, 0.0001);
        $this->assertEqualsWithDelta($srgb->g, $roundTrip->g, 0.0001);
        $this->assertEqualsWithDelta($srgb->b, $roundTrip->b, 0.0001);
        $this->assertEqualsWithDelta($srgb->a, $roundTrip->a, 0.0001);
    }

    public function testRoundTripConversionWhite(): void
    {
        $original = new CmykColor(0.0, 0.0, 0.0, 0.0);
        $srgb = $original->toSrgb();
        $back = CmykColor::fromSrgb($srgb);

        $this->assertEqualsWithDelta($original->c, $back->c, 0.0001);
        $this->assertEqualsWithDelta($original->m, $back->m, 0.0001);
        $this->assertEqualsWithDelta($original->y, $back->y, 0.0001);
        $this->assertEqualsWithDelta($original->k, $back->k, 0.0001);
    }

    public function testToCssDefault(): void
    {
        $cmyk = new CmykColor(0.5, 0.3, 0.2, 0.1);
        $css = $cmyk->toCss();

        $this->assertSame('device-cmyk(50% 30% 20% 10%)', $css);
    }

    public function testToCssWithAlpha(): void
    {
        $cmyk = new CmykColor(0.5, 0.3, 0.2, 0.1, 0.5);
        $css = $cmyk->toCss();

        $this->assertSame('device-cmyk(50% 30% 20% 10% / 0.5)', $css);
    }

    public function testToCssWithCmykSpace(): void
    {
        $cmyk = new CmykColor(0.5, 0.3, 0.2, 0.1);
        $css = $cmyk->toCss('cmyk');

        $this->assertSame('device-cmyk(50% 30% 20% 10%)', $css);
    }

    public function testToCssWithInvalidSpace(): void
    {
        $cmyk = new CmykColor(0.5, 0.3, 0.2, 0.1);

        $this->expectException(InvalidColorException::class);
        $cmyk->toCss('oklab');
    }

    public function testToSrgbBlack(): void
    {
        $cmyk = new CmykColor(0.0, 0.0, 0.0, 1.0);
        $srgb = $cmyk->toSrgb();

        $this->assertEqualsWithDelta(0.0, $srgb->r, 0.0001);
        $this->assertEqualsWithDelta(0.0, $srgb->g, 0.0001);
        $this->assertEqualsWithDelta(0.0, $srgb->b, 0.0001);
    }

    public function testToSrgbCyan(): void
    {
        $cmyk = new CmykColor(1.0, 0.0, 0.0, 0.0);
        $srgb = $cmyk->toSrgb();

        $this->assertEqualsWithDelta(0.0, $srgb->r, 0.0001);
        $this->assertEqualsWithDelta(1.0, $srgb->g, 0.0001);
        $this->assertEqualsWithDelta(1.0, $srgb->b, 0.0001);
    }

    public function testToSrgbMagenta(): void
    {
        $cmyk = new CmykColor(0.0, 1.0, 0.0, 0.0);
        $srgb = $cmyk->toSrgb();

        $this->assertEqualsWithDelta(1.0, $srgb->r, 0.0001);
        $this->assertEqualsWithDelta(0.0, $srgb->g, 0.0001);
        $this->assertEqualsWithDelta(1.0, $srgb->b, 0.0001);
    }

    public function testToSrgbWhite(): void
    {
        $cmyk = new CmykColor(0.0, 0.0, 0.0, 0.0);
        $srgb = $cmyk->toSrgb();

        $this->assertEqualsWithDelta(1.0, $srgb->r, 0.0001);
        $this->assertEqualsWithDelta(1.0, $srgb->g, 0.0001);
        $this->assertEqualsWithDelta(1.0, $srgb->b, 0.0001);
    }

    public function testToSrgbYellow(): void
    {
        $cmyk = new CmykColor(0.0, 0.0, 1.0, 0.0);
        $srgb = $cmyk->toSrgb();

        $this->assertEqualsWithDelta(1.0, $srgb->r, 0.0001);
        $this->assertEqualsWithDelta(1.0, $srgb->g, 0.0001);
        $this->assertEqualsWithDelta(0.0, $srgb->b, 0.0001);
    }

    public function testToWithCmykSpace(): void
    {
        $cmyk = new CmykColor(0.5, 0.3, 0.2, 0.1);
        $converted = $cmyk->to('cmyk');

        $this->assertSame($cmyk, $converted);
    }

    public function testViaColorParse(): void
    {
        $cmyk = Color::parse('device-cmyk(50% 30% 20% 10%)');

        $this->assertInstanceOf(CmykColor::class, $cmyk);
        $this->assertSame(0.5, $cmyk->c);
        $this->assertSame(0.3, $cmyk->m);
        $this->assertSame(0.2, $cmyk->y);
        $this->assertSame(0.1, $cmyk->k);
    }

    public function testWithAlpha(): void
    {
        $cmyk = new CmykColor(0.5, 0.3, 0.2, 0.1, 0.8);
        $modified = $cmyk->withAlpha(0.5);

        $this->assertSame(0.5, $modified->c);
        $this->assertSame(0.3, $modified->m);
        $this->assertSame(0.2, $modified->y);
        $this->assertSame(0.1, $modified->k);
        $this->assertSame(0.5, $modified->alpha);
    }

    public function testWithChannel(): void
    {
        $cmyk = new CmykColor(0.5, 0.3, 0.2, 0.1);
        $modified = $cmyk->withChannel('c', 0.8);

        $this->assertSame(0.8, $modified->c);
        $this->assertSame(0.3, $modified->m);
        $this->assertSame(0.2, $modified->y);
        $this->assertSame(0.1, $modified->k);
    }

    public function testWithChannels(): void
    {
        $cmyk = new CmykColor(0.5, 0.3, 0.2, 0.1);
        $modified = $cmyk->withChannels(['c' => 0.8, 'y' => 0.9]);

        $this->assertSame(0.8, $modified->c);
        $this->assertSame(0.3, $modified->m);
        $this->assertSame(0.9, $modified->y);
        $this->assertSame(0.1, $modified->k);
    }
}
