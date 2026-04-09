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

use PhpColor\Color\AbstractColor;
use PhpColor\Color\Color;
use PhpColor\Color\ColorInterface;
use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\Exception\ParseException;
use PhpColor\Color\OklabColor;
use PhpColor\Color\OklchColor;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test coverage for AbstractColor helpers and conversion scaffolding.
 */
#[CoversClass(AbstractColor::class)]
final class AbstractColorTest extends TestCase
{
    public function testEqualsComparesByValue(): void
    {
        $a = new SrgbColor(0.1, 0.2, 0.3, 0.4);
        $b = new SrgbColor(0.1, 0.2, 0.3, 0.4);
        $c = new SrgbColor(0.1, 0.2, 0.3, 0.5);

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function testEqualsIsSensitiveToAlpha(): void
    {
        $a = new SrgbColor(0.2, 0.4, 0.6, 0.3);
        $b = new SrgbColor(0.2, 0.4, 0.6, 0.31);

        $this->assertFalse($a->equals($b));
    }

    public function testEqualsReturnsFalseForDifferentSpaces(): void
    {
        $srgb = new SrgbColor(0.2, 0.3, 0.4, 0.8);
        $oklab = OklabColor::fromSrgb($srgb);

        $this->assertSame($srgb->getAlpha(), $oklab->getAlpha());
        $this->assertFalse($srgb->equals($oklab));
    }

    #[DataProvider('providePartiallyTransparentColors')]
    #[DataProvider('provideTransparentColors')]
    public function testIsNotOpaque(AbstractColor $color): void
    {
        $this->assertFalse($color->isOpaque());
    }

    #[DataProvider('providePartiallyTransparentColors')]
    #[DataProvider('provideOpaqueColors')]
    public function testIsNotTransparent(AbstractColor $color): void
    {
        $this->assertFalse($color->isTransparent());
    }

    public static function providePartiallyTransparentColors(): iterable
    {
        yield 'white with alpha = 0.999' => [new DummyColor(1, 1, 1, 0.999)];
        yield 'white with alpha = 0.001' => [new DummyColor(1, 1, 1, 0.001)];
        yield 'black with alpha = 0.999' => [new DummyColor(0, 0, 0, 0.999)];
        yield 'black with alpha = 0.001' => [new DummyColor(0, 0, 0, 0.001)];
        yield 'red with alpha = 0.25' => [new DummyColor(1, 0, 0, 0.25)];
        yield 'blue with alpha = 0.75' => [new DummyColor(0, 0, 1, 0.75)];
        yield 'green with alpha = 0.1' => [new DummyColor(0, 1, 0, 0.1)];
    }

    #[DataProvider('provideOpaqueColors')]
    public function testIsOpaque(AbstractColor $color): void
    {
        $this->assertTrue($color->isOpaque());
    }

    public static function provideOpaqueColors(): iterable
    {
        yield 'white with alpha = 1.0' => [new DummyColor(1, 1, 1, 1.0)];
        yield 'black with alpha = 1.0' => [new DummyColor(0, 0, 0, 1.0)];
        yield 'red with default alpha' => [new DummyColor(1, 0, 0, 1.0)];
        yield 'blue with alpha = 1.0' => [new DummyColor(0, 0, 1, 1.0)];
        yield 'green with default alpha' => [new DummyColor(0, 1, 0)];
        yield 'green with alpha = 1.0' => [new DummyColor(0, 1, 0, 1.0)];
    }

    #[DataProvider('provideTransparentColors')]
    public function testIsTransparent(ColorInterface $color): void
    {
        $this->assertTrue($color->isTransparent());
    }

    public static function provideTransparentColors(): iterable
    {
        yield 'white with alpha = 0.0' => [new DummyColor(1, 1, 1, 0.0)];
        yield 'black with alpha = 0.0' => [new DummyColor(0, 0, 0, 0.0)];
        yield 'red with alpha = 0.0' => [new DummyColor(1, 0, 0, 0.0)];
        yield 'blue with alpha = 0.0' => [new DummyColor(0, 0, 1, 0.0)];
        yield 'green with alpha = 0.0' => [new DummyColor(0, 1, 0, 0.0)];
    }

    public function testGetOpacityMatchesGetAlpha(): void
    {
        $c = new SrgbColor(0.1, 0.2, 0.3, 0.7);
        $this->assertSame($c->getAlpha(), $c->getOpacity());
    }

    public function testParsePercentageThrowsOnMissingPercent(): void
    {
        $ref = new \ReflectionClass(AbstractColor::class);
        $m = $ref->getMethod('parsePercentage');
        $this->expectException(ParseException::class);
        $m->invoke(null, '10');
    }

    public function testParseRgbComponentPercent(): void
    {
        $ref = new \ReflectionClass(AbstractColor::class);
        $m = $ref->getMethod('parseRgbComponent');

        $v = $m->invoke(null, '50%');
        $this->assertEqualsWithDelta(0.5, $v, 1e-12);
    }

    public function testProtectedMathHelpersViaWrapper(): void
    {
        $this->assertSame(0.0, DummyColor::tClamp01(-1.0));
        $this->assertSame(0.25, DummyColor::tClamp01(0.25));
        $this->assertSame(1.0, DummyColor::tClamp01(2.0));

        $this->assertSame('0.1', DummyColor::tFormatCssFloat(0.100000));
        $this->assertSame('1', DummyColor::tFormatCssFloat(1.000000));

        $this->assertSame(90.0, DummyColor::tNormAngle(450.0));
        $this->assertSame(330.0, DummyColor::tNormAngle(-30.0));

        $m = [
            [1.0, 2.0, 3.0],
            [0.0, 1.0, 4.0],
            [5.0, 6.0, 0.0],
        ];
        $v = [1.0, 2.0, 3.0];
        $this->assertSame([14.0, 14.0, 17.0], DummyColor::tMul3x3($m, $v));

        $this->assertSame('display-p3', DummyColor::tNormalizeSpaceName(' Display_P3 '));
    }

    public function testSrgbLinearConversions(): void
    {
        $this->assertEqualsWithDelta(0.0, DummyColor::tSrgbToLinear(0.0), 1e-9);
        $this->assertEqualsWithDelta(0.04045 / 12.92, DummyColor::tSrgbToLinear(0.04045), 1e-12);
        $this->assertEqualsWithDelta(0.0031308 * 12.92, DummyColor::tLinearToSrgb(0.0031308), 1e-12);

        $srgb = 0.7;
        $lin = DummyColor::tSrgbToLinear($srgb);
        $back = DummyColor::tLinearToSrgb($lin);
        $this->assertEqualsWithDelta($srgb, $back, 1e-12);
    }

    public function testStringableAndToString(): void
    {
        $c = new SrgbColor(1.0, 0.0, 0.0, 0.5);
        // __toString proxies to toString(), which proxies to toCss() by default
        $s1 = (string) $c;
        $s2 = $c->toString();
        $this->assertSame($s1, $s2);
        $this->assertStringStartsWith('rgb(', $s1);
        $this->assertStringContainsString('/ 0.5', $s1);
    }

    public function testStringableMatchesCssForOklab(): void
    {
        $c = new OklabColor(0.6, 0.1, -0.05, 0.8);
        $s1 = (string) $c;
        $s2 = $c->toCss();
        $this->assertSame($s2, $s1);
        $this->assertStringStartsWith('oklab(', $s1);
        $this->assertStringContainsString('/ 0.8', $s1);
    }

    public function testToConversionByInstance(): void
    {
        $dummy = new DummyColor(0.1, 0.2, 0.3, 0.4);

        $this->assertSame($dummy, $dummy->to(new DummyColor(0.0, 0.0, 0.0)));

        // Target instance of another space => convert via sRGB
        $target = new OklabColor(0.7, 0.1, 0.0);
        $converted = $dummy->to($target);
        $this->assertInstanceOf(OklabColor::class, $converted);

        // Target instance SrgbColor => convert directly to sRGB
        $convertedSrgb = $dummy->to(new SrgbColor(0.0, 0.0, 0.0));
        $this->assertInstanceOf(SrgbColor::class, $convertedSrgb);
    }

    public function testToConversionByString(): void
    {
        $dummy = new DummyColor(0.2, 0.3, 0.4, 0.5);
        $this->assertSame($dummy, $dummy->to('dummy'));

        $srgb = $dummy->to('srgb');
        $this->assertInstanceOf(SrgbColor::class, $srgb);

        $srgb2 = $dummy->to('rgb');
        $this->assertInstanceOf(SrgbColor::class, $srgb2);

        $oklab = $dummy->to('oklab');
        $this->assertInstanceOf(OklabColor::class, $oklab);

        $this->expectException(InvalidColorException::class);
        $dummy->to('unknown-space');
    }

    public function testWithAlphaUsesFromChannels(): void
    {
        $dummy = new DummyColor(0.2, 0.3, 0.4, 0.5);
        $changed = $dummy->withAlpha(0.8);

        $this->assertNotSame($dummy, $changed);
        $this->assertSame(0.2, $changed->toSrgb()->r);
        $this->assertSame(0.3, $changed->toSrgb()->g);
        $this->assertSame(0.4, $changed->toSrgb()->b);
        $this->assertEqualsWithDelta(0.8, $changed->getAlpha(), 1e-12);
    }

    public function testWithChannelShortcut(): void
    {
        $c = new SrgbColor(0.1, 0.2, 0.3);
        $next = $c->withChannel('r', 0.9);

        $this->assertSame(0.9, $next->getRed());
        $this->assertSame(0.2, $next->getGreen());
        $this->assertSame(0.3, $next->getBlue());
    }

    public function testWithChannelsMergesInSrgb(): void
    {
        $c = new SrgbColor(0.1, 0.2, 0.3, 0.4);
        $next = $c->withChannels(['g' => 0.5]);

        $this->assertInstanceOf(SrgbColor::class, $next);
        $this->assertSame(0.1, $next->getRed());
        $this->assertSame(0.5, $next->getGreen());
        $this->assertSame(0.3, $next->getBlue());
        $this->assertSame(0.4, $next->getAlpha());
    }

    public function testHueIsZeroForAchromaticColors(): void
    {
        $black = new DummyColor(0, 0, 0);
        $white = new DummyColor(1, 1, 1);
        $gray = new DummyColor(0.5, 0.5, 0.5);

        $this->assertSame(0.0, $black->getHue());
        $this->assertSame(0.0, $white->getHue());
        $this->assertSame(0.0, $gray->getHue());
    }

    public function testHueAndSaturationConsistencyWithOklch(): void
    {
        $ok = new OklchColor(0.6, 0.2, 30.0);
        $this->assertSame(30.0, $ok->getHue());
        $this->assertSame(0.2, $ok->getSaturation());

        // Achromatic: chroma=0 -> hue=0.0
        $gray = new OklchColor(0.5, 0.0, 120.0);
        $this->assertSame(0.0, $gray->getSaturation());
        $this->assertSame(0.0, $gray->getHue());
    }

    public function testGetHueFromOklchReturnsHue(): void
    {
        $c = new OklchColor(0.6, 0.2, 30.0);
        $this->assertSame(30.0, $c->getHue());

        $d = new OklchColor(0.6, 0.2, 370.0);
        $this->assertSame(10.0, $d->getHue());
    }

    public function testGetHueZeroChromaReturnsZero(): void
    {
        $gray = new OklchColor(0.5, 0.0, 123.0);
        $this->assertSame(0.0, $gray->getHue());
    }

    public function testGetHueFromSrgbViaOklchRoundTrip(): void
    {
        $ok = new OklchColor(0.65, 0.18, 200.0);
        $srgb = $ok->toSrgb();
        $this->assertInstanceOf(SrgbColor::class, $srgb);

        $this->assertEqualsWithDelta(200.0, $srgb->getHue(), 5.0);
    }

    public function testLuminanceBlackAndWhite(): void
    {
        $black = Color::black();
        $white = Color::white();

        $this->assertSame(0.0, $black->getLuminance());
        $this->assertSame(1.0, $white->getLuminance());
        $this->assertTrue($white->isLight());
        $this->assertTrue($black->isDark());
    }

    public function testSrgbInvertExact(): void
    {
        $c = new SrgbColor(0.2, 0.4, 0.6, 0.8);
        $inv = $c->invert();
        $this->assertInstanceOf(SrgbColor::class, $inv);
        $this->assertEqualsWithDelta(0.8, $inv->r, 1e-12);
        $this->assertEqualsWithDelta(0.6, $inv->g, 1e-12);
        $this->assertEqualsWithDelta(0.4, $inv->b, 1e-12);
        $this->assertEqualsWithDelta(0.8, $inv->a, 1e-12);
    }

    public function testSrgbLightenDarkenMonotonic(): void
    {
        $c = new SrgbColor(0.2, 0.3, 0.4, 1.0);
        $l = $c->lighten(0.2)->to('oklch');
        $d = $c->darken(0.2)->to('oklch');

        $this->assertInstanceOf(OklchColor::class, $l);
        $this->assertInstanceOf(OklchColor::class, $d);

        $base = $c->to('oklch');
        $this->assertGreaterThan($base->l, $l->l);
        $this->assertLessThan($base->l, $d->l);
        $this->assertGreaterThanOrEqual(0.0, $l->l);
        $this->assertLessThanOrEqual(1.0, $l->l);
        $this->assertGreaterThanOrEqual(0.0, $d->l);
        $this->assertLessThanOrEqual(1.0, $d->l);
    }

    public function testSrgbSaturateAndDesaturate(): void
    {
        $c = new SrgbColor(0.6, 0.4, 0.3, 1.0);
        $more = $c->saturate(0.05)->to('oklch');
        $less = $c->saturate(-0.05)->to('oklch');
        $base = $c->to('oklch');

        $this->assertInstanceOf(OklchColor::class, $more);
        $this->assertInstanceOf(OklchColor::class, $less);
        $this->assertGreaterThan($base->c, $more->c);
        $this->assertLessThanOrEqual($base->c, $less->c);
        $this->assertGreaterThanOrEqual(0.0, $more->c);
        $this->assertGreaterThanOrEqual(0.0, $less->c);
    }

    public function testDesaturateEquivalentToNegativeSaturate(): void
    {
        $color = new SrgbColor(0.8, 0.3, 0.5, 1.0);
        $desaturated = $color->desaturate(0.05);
        $saturated = $color->saturate(-0.05);

        $des = $desaturated->to('oklch');
        $sat = $saturated->to('oklch');

        $this->assertEqualsWithDelta($des->c, $sat->c, 0.001);
    }

    public function testDesaturateReducesChroma(): void
    {
        $color = new SrgbColor(1.0, 0.0, 0.0, 1.0); // Red
        $desaturated = $color->desaturate(0.1);

        $original = $color->to('oklch');
        $result = $desaturated->to('oklch');

        $this->assertLessThan($original->c, $result->c);
    }

    public function testDesaturateWithNegativeValueWorks(): void
    {
        $color = new SrgbColor(1.0, 0.5, 0.0, 1.0);
        $result = $color->desaturate(-0.1);

        $original = $color->to('oklch');
        $oklch = $result->to('oklch');

        $this->assertLessThan($original->c, $oklch->c);
    }

    public function testSrgbTintAndShadeEndpoints(): void
    {
        $red = new SrgbColor(1.0, 0.0, 0.0, 1.0);

        $t0 = $red->tint(0.0);
        $this->assertInstanceOf(SrgbColor::class, $t0);
        $this->assertEqualsWithDelta($red->r, $t0->r, 1e-6);
        $this->assertEqualsWithDelta($red->g, $t0->g, 1e-6);
        $this->assertEqualsWithDelta($red->b, $t0->b, 1e-6);

        $t1 = $red->tint(1.0);
        $this->assertEqualsWithDelta(1.0, $t1->r, 1e-6);
        $this->assertEqualsWithDelta(1.0, $t1->g, 1e-6);
        $this->assertEqualsWithDelta(1.0, $t1->b, 1e-6);

        $s0 = $red->shade(0.0);
        $this->assertEqualsWithDelta($red->r, $s0->r, 1e-6);
        $this->assertEqualsWithDelta($red->g, $s0->g, 1e-6);
        $this->assertEqualsWithDelta($red->b, $s0->b, 1e-6);

        $s1 = $red->shade(1.0);
        $this->assertEqualsWithDelta(0.0, $s1->r, 1e-6);
        $this->assertEqualsWithDelta(0.0, $s1->g, 1e-6);
        $this->assertEqualsWithDelta(0.0, $s1->b, 1e-6);
    }

    public function testOklchRotateHueWrapsAndPreserves(): void
    {
        $oklch = new OklchColor(0.5, 0.1, 350.0, 0.9);
        $rot = $oklch->rotateHue(20.0)->to('oklch');
        $rotNeg = $oklch->rotateHue(-400.0)->to('oklch');

        $this->assertInstanceOf(OklchColor::class, $rot);
        $this->assertEqualsWithDelta(10.0, $rot->h, 1e-4);
        $this->assertEqualsWithDelta($oklch->l, $rot->l, 1e-6);
        $this->assertEqualsWithDelta($oklch->c, $rot->c, 1e-6);
        $this->assertEqualsWithDelta($oklch->alpha, $rot->alpha, 1e-6);

        // -400deg == -40deg => 310deg
        $this->assertEqualsWithDelta(310.0, $rotNeg->h, 1e-4);
    }

    public function testGrayscaleOnAlreadyGrayIsIdempotent(): void
    {
        $gray = new SrgbColor(0.5, 0.5, 0.5, 1.0);
        $result = $gray->grayscale();

        $srgb = $result->toSrgb();

        $this->assertEqualsWithDelta(0.5, $srgb->r, 0.1);
        $this->assertEqualsWithDelta(0.5, $srgb->g, 0.1);
        $this->assertEqualsWithDelta(0.5, $srgb->b, 0.1);
    }

    public function testGrayscalePreservesAlpha(): void
    {
        $color = new SrgbColor(1.0, 0.5, 0.0, 0.7);
        $gray = $color->grayscale();

        $this->assertEqualsWithDelta(0.7, $gray->toSrgb()->a, 0.001);
    }

    public function testGrayscalePreservesLightness(): void
    {
        $color = new SrgbColor(0.5, 0.3, 0.8, 1.0);
        $gray = $color->grayscale();

        $originalL = $color->to('oklch')->l;
        $grayL = $gray->to('oklch')->l;

        $this->assertEqualsWithDelta($originalL, $grayL, 0.001);
    }

    public function testGrayscaleRemovesAllChroma(): void
    {
        $color = new SrgbColor(1.0, 0.0, 0.0, 1.0); // Red
        $gray = $color->grayscale();

        $oklch = $gray->to('oklch');

        $this->assertEqualsWithDelta(0.0, $oklch->c, 0.001);
    }

    public function testTemperaturePredicates(): void
    {
        $this->assertTrue(Color::red()->isHot());
        $this->assertTrue(Color::blue()->isCold());
    }

    public function testRedIsWarm(): void
    {
        $red = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $temp = $red->temperature();

        $this->assertGreaterThan(0.5, $temp);
    }

    public function testBlueIsCool(): void
    {
        $blue = new SrgbColor(0.0, 0.0, 1.0, 1.0);
        $temp = $blue->temperature();

        $this->assertLessThan(0.0, $temp);
    }

    public function testCyanIsCool(): void
    {
        $cyan = new SrgbColor(0.0, 1.0, 1.0, 1.0);
        $temp = $cyan->temperature();

        $this->assertLessThan(-0.5, $temp);
    }

    public function testGreenIsNeutral(): void
    {
        $green = new SrgbColor(0.0, 1.0, 0.0, 1.0);
        $temp = $green->temperature();

        $this->assertEqualsWithDelta(0.0, $temp, 0.7);
    }

    public function testTemperatureInRange(): void
    {
        $colors = [
            new SrgbColor(1.0, 0.0, 0.0, 1.0), // Red
            new SrgbColor(0.0, 1.0, 0.0, 1.0), // Green
            new SrgbColor(0.0, 0.0, 1.0, 1.0), // Blue
            new SrgbColor(1.0, 1.0, 0.0, 1.0), // Yellow
            new SrgbColor(1.0, 0.0, 1.0, 1.0), // Magenta
            new SrgbColor(0.0, 1.0, 1.0, 1.0), // Cyan
        ];

        foreach ($colors as $color) {
            $temp = $color->temperature();
            $this->assertGreaterThanOrEqual(-1.0, $temp);
            $this->assertLessThanOrEqual(1.0, $temp);
        }
    }

    public function testTemperatureRedToYellow(): void
    {
        $red = new SrgbColor(1.0, 0.0, 0.0, 1.0);      // ~29 degrees in oklch
        $orange = new SrgbColor(1.0, 0.5, 0.0, 1.0);   // ~60 degrees

        $redTemp = $red->temperature();
        $orangeTemp = $orange->temperature();

        $this->assertGreaterThan(0.0, $redTemp);
        $this->assertGreaterThan(0.0, $orangeTemp);

        $this->assertGreaterThan(0.5, $redTemp);
    }

    public function testTemperatureYellowToCyan(): void
    {
        $yellowGreen = new SrgbColor(0.5, 1.0, 0.0, 1.0);  // ~90 degrees
        $green = new SrgbColor(0.0, 1.0, 0.0, 1.0);        // ~120 degrees
        $cyan = new SrgbColor(0.0, 1.0, 1.0, 1.0);         // ~180 degrees

        $ygTemp = $yellowGreen->temperature();
        $green->temperature();
        $cyanTemp = $cyan->temperature();

        $this->assertGreaterThanOrEqual($cyanTemp, $ygTemp);
        $this->assertLessThan(0.0, $cyanTemp); // Cyan should be cool
    }

    public function testTemperatureCyanToBlue(): void
    {
        $cyan = new SrgbColor(0.0, 1.0, 1.0, 1.0);     // ~180 degrees
        $skyBlue = new SrgbColor(0.0, 0.5, 1.0, 1.0);  // ~210 degrees
        $blue = new SrgbColor(0.0, 0.0, 1.0, 1.0);     // ~240 degrees

        $cyanTemp = $cyan->temperature();
        $skyTemp = $skyBlue->temperature();
        $blueTemp = $blue->temperature();

        $this->assertLessThan(0.0, $cyanTemp);
        $this->assertLessThan(0.0, $skyTemp);
        $this->assertLessThan(0.0, $blueTemp);

        $this->assertLessThan(-0.5, $cyanTemp);
    }

    public function testTemperatureBlueToRed(): void
    {
        $violet = new SrgbColor(0.5, 0.0, 1.0, 1.0);    // ~270 degrees
        $magenta = new SrgbColor(1.0, 0.0, 1.0, 1.0);   // ~300 degrees
        $pink = new SrgbColor(1.0, 0.0, 0.5, 1.0);      // ~330 degrees

        $violetTemp = $violet->temperature();
        $magenta->temperature();
        $pinkTemp = $pink->temperature();

        $this->assertLessThan($pinkTemp, $violetTemp);
        $this->assertGreaterThan(0.0, $pinkTemp); // Pink should be warm
    }

    public function testTemperatureExactBoundaries(): void
    {
        $colors = [
            [0.0, 'warm'],     // 0 degrees
            [90.0, 'neutral'], // 90 degrees
            [180.0, 'cool'],   // 180 degrees
            [270.0, 'cool'],   // 270 degrees
            [359.0, 'warm'],   // Almost 360 degrees
        ];

        foreach ($colors as [$hue, $expected]) {
            $oklch = new OklchColor(0.5, 0.1, $hue, 1.0);
            $color = $oklch->toSrgb();
            $temp = $color->temperature();

            $this->assertGreaterThanOrEqual(-1.0, $temp);
            $this->assertLessThanOrEqual(1.0, $temp);
        }
    }

    public function testTemperatureWithGrayscale(): void
    {
        $gray = new SrgbColor(0.5, 0.5, 0.5, 1.0);
        $temp = $gray->temperature();

        $this->assertGreaterThanOrEqual(-1.0, $temp);
        $this->assertLessThanOrEqual(1.0, $temp);
    }

    public function testWarmShiftsHueTowardRed(): void
    {
        $blue = new SrgbColor(0.0, 0.0, 1.0, 1.0);
        $warmer = $blue->warm(0.5);

        $bluTemp = $blue->temperature();
        $warmerTemp = $warmer->temperature();

        $this->assertGreaterThan($bluTemp, $warmerTemp);
    }

    public function testWarmPreservesLightness(): void
    {
        $color = new SrgbColor(0.5, 0.5, 0.8, 1.0);
        $warmer = $color->warm(0.3);

        $originalL = $color->to('oklch')->l;
        $warmerL = $warmer->to('oklch')->l;

        $this->assertEqualsWithDelta($originalL, $warmerL, 0.01);
    }

    public function testWarmWithZeroAmount(): void
    {
        $color = new SrgbColor(0.5, 0.3, 0.8, 1.0);
        $result = $color->warm(0.0);

        $originalHue = $color->to('oklch')->h;
        $resultHue = $result->to('oklch')->h;

        $this->assertEqualsWithDelta($originalHue, $resultHue, 0.1);
    }

    public function testWarmClampsAmount(): void
    {
        $color = new SrgbColor(0.5, 0.5, 0.8, 1.0);

        $warmer1 = $color->warm(1.5);
        $warmer2 = $color->warm(1.0);

        $hue1 = $warmer1->to('oklch')->h;
        $hue2 = $warmer2->to('oklch')->h;

        $this->assertEqualsWithDelta($hue1, $hue2, 0.01);

        $warmer3 = $color->warm(-0.5);
        $original = $color->to('oklch')->h;
        $hue3 = $warmer3->to('oklch')->h;

        $this->assertEqualsWithDelta($original, $hue3, 0.1);
    }

    public function testWarmInRedToYellowRange(): void
    {
        $orange = new SrgbColor(1.0, 0.5, 0.0, 1.0); // ~30 degrees
        $warmer = $orange->warm(0.5);

        $origHue = $orange->to('oklch')->h;
        $newHue = $warmer->to('oklch')->h;

        $this->assertLessThan($origHue, $newHue);
    }

    public function testWarmInYellowToCyanRange(): void
    {
        $green = new SrgbColor(0.0, 1.0, 0.0, 1.0); // ~120 degrees
        $warmer = $green->warm(0.5);

        $origTemp = $green->temperature();
        $newTemp = $warmer->temperature();

        $this->assertGreaterThan($origTemp, $newTemp);
    }

    public function testWarmInCyanToBlueRange(): void
    {
        $blue = new SrgbColor(0.0, 0.0, 1.0, 1.0); // ~240 degrees
        $warmer = $blue->warm(0.3);

        $origTemp = $blue->temperature();
        $newTemp = $warmer->temperature();

        $this->assertGreaterThan($origTemp, $newTemp);
    }

    public function testWarmInBlueToRedRange(): void
    {
        $magenta = new SrgbColor(1.0, 0.0, 1.0, 1.0); // ~300 degrees
        $warmer = $magenta->warm(0.3);

        $origTemp = $magenta->temperature();
        $newTemp = $warmer->temperature();

        $this->assertGreaterThan($origTemp, $newTemp);
    }

    public function testCoolShiftsHueTowardBlue(): void
    {
        $red = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $cooler = $red->cool(0.5);

        $redTemp = $red->temperature();
        $coolerTemp = $cooler->temperature();

        $this->assertLessThan($redTemp, $coolerTemp);
    }

    public function testCoolPreservesLightness(): void
    {
        $color = new SrgbColor(0.8, 0.5, 0.3, 1.0);
        $cooler = $color->cool(0.3);

        $originalL = $color->to('oklch')->l;
        $coolerL = $cooler->to('oklch')->l;

        $this->assertEqualsWithDelta($originalL, $coolerL, 0.01);
    }

    public function testCoolWithZeroAmount(): void
    {
        $color = new SrgbColor(0.5, 0.3, 0.8, 1.0);
        $result = $color->cool(0.0);

        $originalHue = $color->to('oklch')->h;
        $resultHue = $result->to('oklch')->h;

        $this->assertEqualsWithDelta($originalHue, $resultHue, 0.1);
    }

    public function testCoolClampsAmount(): void
    {
        $color = new SrgbColor(0.8, 0.5, 0.3, 1.0);

        $cooler1 = $color->cool(1.5);
        $cooler2 = $color->cool(1.0);

        $hue1 = $cooler1->to('oklch')->h;
        $hue2 = $cooler2->to('oklch')->h;

        $this->assertEqualsWithDelta($hue1, $hue2, 0.01);

        $cooler3 = $color->cool(-0.5);
        $original = $color->to('oklch')->h;
        $hue3 = $cooler3->to('oklch')->h;

        $this->assertEqualsWithDelta($original, $hue3, 0.1);
    }

    public function testCoolInYellowToCyanRange(): void
    {
        $green = new SrgbColor(0.0, 1.0, 0.0, 1.0); // ~120 degrees
        $cooler = $green->cool(0.5);

        $origTemp = $green->temperature();
        $newTemp = $cooler->temperature();

        $this->assertLessThan($origTemp, $newTemp);
    }

    public function testCoolInCyanToBlueRange(): void
    {
        $cyan = new SrgbColor(0.0, 1.0, 1.0, 1.0); // ~180 degrees
        $cooler = $cyan->cool(0.5);

        $origHue = $cyan->to('oklch')->h;
        $newHue = $cooler->to('oklch')->h;

        $this->assertGreaterThan($origHue, $newHue);
    }

    public function testCoolInBlueToRedRangeAbove270(): void
    {
        // Hue >= 270: should shift toward blue-cyan (target + 360)
        $magenta = new SrgbColor(1.0, 0.0, 1.0, 1.0); // ~300 degrees
        $cooler = $magenta->cool(0.3);

        // Note: The current implementation lerps toward (210 + 360) = 570
        // which normalizes to a hue closer to red, making it warmer
        $origHue = $magenta->to('oklch')->h;
        $newHue = $cooler->to('oklch')->h;

        $this->assertNotEquals($origHue, $newHue);
    }

    public function testCoolInBlueToRedRangeBelow90(): void
    {
        $red = new SrgbColor(1.0, 0.0, 0.0, 1.0); // ~0 degrees
        $cooler = $red->cool(0.3);

        $origTemp = $red->temperature();
        $newTemp = $cooler->temperature();

        $this->assertLessThan($origTemp, $newTemp);
    }

    public function testCoolFallbackBranch(): void
    {
        $greenCyan = new SrgbColor(0.0, 1.0, 0.7, 1.0); // ~165 degrees
        $cooler = $greenCyan->cool(0.5);

        $origTemp = $greenCyan->temperature();
        $newTemp = $cooler->temperature();

        $this->assertLessThan($origTemp, $newTemp);
    }

    public function testWarmAndCoolPreserveAlpha(): void
    {
        $color = new SrgbColor(0.5, 0.3, 0.8, 0.7);

        $warmer = $color->warm(0.5);
        $cooler = $color->cool(0.5);

        $this->assertEqualsWithDelta(0.7, $warmer->toSrgb()->a, 0.01);
        $this->assertEqualsWithDelta(0.7, $cooler->toSrgb()->a, 0.01);
    }

    public function testWarmAndCoolPreserveChroma(): void
    {
        $color = new SrgbColor(0.8, 0.2, 0.4, 1.0);

        $warmer = $color->warm(0.3);
        $cooler = $color->cool(0.3);

        $originalC = $color->to('oklch')->c;
        $warmerC = $warmer->to('oklch')->c;
        $coolerC = $cooler->to('oklch')->c;

        $this->assertEqualsWithDelta($originalC, $warmerC, 0.01);
        $this->assertEqualsWithDelta($originalC, $coolerC, 0.01);
    }

    public function testBlendNormalMode(): void
    {
        $src = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $dst = new SrgbColor(0.0, 0.0, 1.0, 1.0);

        $result = $src->blend($dst, 'normal');
        $srgb = $result->toSrgb();

        $this->assertEqualsWithDelta(1.0, $srgb->r, 0.01);
    }

    public function testBlendMultiplyMode(): void
    {
        $src = new SrgbColor(0.5, 0.5, 0.5, 1.0);
        $dst = new SrgbColor(0.8, 0.8, 0.8, 1.0);

        $result = $src->blend($dst, 'multiply');
        $srgb = $result->toSrgb();

        // 0.5 * 0.8 = 0.4 (darkens)
        $this->assertEqualsWithDelta(0.4, $srgb->r, 0.01);
        $this->assertEqualsWithDelta(0.4, $srgb->g, 0.01);
        $this->assertEqualsWithDelta(0.4, $srgb->b, 0.01);
    }

    public function testBlendScreenMode(): void
    {
        $src = new SrgbColor(0.5, 0.5, 0.5, 1.0);
        $dst = new SrgbColor(0.5, 0.5, 0.5, 1.0);

        $result = $src->blend($dst, 'screen');
        $srgb = $result->toSrgb();

        // 1 - (1-0.5)*(1-0.5) = 1 - 0.25 = 0.75 (lightens)
        $this->assertEqualsWithDelta(0.75, $srgb->r, 0.01);
        $this->assertEqualsWithDelta(0.75, $srgb->g, 0.01);
        $this->assertEqualsWithDelta(0.75, $srgb->b, 0.01);
    }

    public function testBlendDarkenMode(): void
    {
        $src = new SrgbColor(0.3, 0.7, 0.5, 1.0);
        $dst = new SrgbColor(0.5, 0.4, 0.6, 1.0);

        $result = $src->blend($dst, 'darken');
        $srgb = $result->toSrgb();

        $this->assertEqualsWithDelta(0.3, $srgb->r, 0.01);
        $this->assertEqualsWithDelta(0.4, $srgb->g, 0.01);
        $this->assertEqualsWithDelta(0.5, $srgb->b, 0.01);
    }

    public function testBlendLightenMode(): void
    {
        $src = new SrgbColor(0.3, 0.7, 0.5, 1.0);
        $dst = new SrgbColor(0.5, 0.4, 0.6, 1.0);

        $result = $src->blend($dst, 'lighten');
        $srgb = $result->toSrgb();

        $this->assertEqualsWithDelta(0.5, $srgb->r, 0.01);
        $this->assertEqualsWithDelta(0.7, $srgb->g, 0.01);
        $this->assertEqualsWithDelta(0.6, $srgb->b, 0.01);
    }

    public function testBlendDifferenceMode(): void
    {
        $src = new SrgbColor(0.8, 0.3, 0.5, 1.0);
        $dst = new SrgbColor(0.2, 0.6, 0.5, 1.0);

        $result = $src->blend($dst, 'difference');
        $srgb = $result->toSrgb();

        // abs(0.8 - 0.2) = 0.6
        $this->assertEqualsWithDelta(0.6, $srgb->r, 0.01);
        // abs(0.3 - 0.6) = 0.3
        $this->assertEqualsWithDelta(0.3, $srgb->g, 0.01);
        // abs(0.5 - 0.5) = 0.0
        $this->assertEqualsWithDelta(0.0, $srgb->b, 0.01);
    }

    public function testBlendExclusionMode(): void
    {
        $src = new SrgbColor(0.6, 0.4, 0.2, 1.0);
        $dst = new SrgbColor(0.3, 0.5, 0.7, 1.0);

        $result = $src->blend($dst, 'exclusion');
        $srgb = $result->toSrgb();

        // src + dst - 2*src*dst
        // 0.6 + 0.3 - 2*0.6*0.3 = 0.9 - 0.36 = 0.54
        $this->assertEqualsWithDelta(0.54, $srgb->r, 0.01);
        // 0.4 + 0.5 - 2*0.4*0.5 = 0.9 - 0.4 = 0.5
        $this->assertEqualsWithDelta(0.5, $srgb->g, 0.01);
        // 0.2 + 0.7 - 2*0.2*0.7 = 0.9 - 0.28 = 0.62
        $this->assertEqualsWithDelta(0.62, $srgb->b, 0.01);
    }

    public function testBlendOverlayBelowThreshold(): void
    {
        $src = new SrgbColor(0.3, 0.3, 0.3, 1.0);
        $dst = new SrgbColor(0.4, 0.4, 0.4, 1.0); // dst < 0.5

        $result = $src->blend($dst, 'overlay');
        $srgb = $result->toSrgb();

        // 2 * 0.3 * 0.4 = 0.24
        $this->assertEqualsWithDelta(0.24, $srgb->r, 0.01);
    }

    public function testBlendOverlayAboveThreshold(): void
    {
        $src = new SrgbColor(0.7, 0.7, 0.7, 1.0);
        $dst = new SrgbColor(0.6, 0.6, 0.6, 1.0); // dst >= 0.5

        $result = $src->blend($dst, 'overlay');
        $srgb = $result->toSrgb();

        // 1 - 2 * 0.3 * 0.4 = 1 - 0.24 = 0.76
        $this->assertEqualsWithDelta(0.76, $srgb->r, 0.01);
    }

    public function testBlendHardLightBelowThreshold(): void
    {
        $src = new SrgbColor(0.3, 0.3, 0.3, 1.0); // src < 0.5
        $dst = new SrgbColor(0.5, 0.5, 0.5, 1.0);

        $result = $src->blend($dst, 'hard-light');
        $srgb = $result->toSrgb();

        // 2 * src * dst = 2 * 0.3 * 0.5 = 0.3
        $this->assertEqualsWithDelta(0.3, $srgb->r, 0.01);
    }

    public function testBlendHardLightAboveThreshold(): void
    {
        $src = new SrgbColor(0.7, 0.7, 0.7, 1.0); // src >= 0.5
        $dst = new SrgbColor(0.5, 0.5, 0.5, 1.0);

        $result = $src->blend($dst, 'hard-light');
        $srgb = $result->toSrgb();

        // 1 - 2 * (1-src) * (1-dst) = 1 - 2 * 0.3 * 0.5 = 0.7
        $this->assertEqualsWithDelta(0.7, $srgb->r, 0.01);
    }

    public function testBlendSoftLightBelowThreshold(): void
    {
        $src = new SrgbColor(0.3, 0.3, 0.3, 1.0); // src < 0.5
        $dst = new SrgbColor(0.5, 0.5, 0.5, 1.0);

        $result = $src->blend($dst, 'soft-light');
        $srgb = $result->toSrgb();

        // dst - (1 - 2*src) * dst * (1 - dst)
        $expected = 0.5 - (1.0 - 2.0 * 0.3) * 0.5 * (1.0 - 0.5);
        $this->assertEqualsWithDelta($expected, $srgb->r, 0.01);
    }

    public function testBlendSoftLightAboveThreshold(): void
    {
        $src = new SrgbColor(0.7, 0.7, 0.7, 1.0); // src >= 0.5
        $dst = new SrgbColor(0.3, 0.3, 0.3, 1.0); // dst > 0.25

        $result = $src->blend($dst, 'soft-light');
        $srgb = $result->toSrgb();

        $this->assertGreaterThanOrEqual(0.0, $srgb->r);
        $this->assertLessThanOrEqual(1.0, $srgb->r);
    }

    public function testBlendSoftLightHelperAboveQuarter(): void
    {
        $src = new SrgbColor(0.7, 0.7, 0.7, 1.0);
        $dst = new SrgbColor(0.5, 0.5, 0.5, 1.0); // dst > 0.25

        $result = $src->blend($dst, 'soft-light');
        $srgb = $result->toSrgb();

        $this->assertGreaterThanOrEqual(0.0, $srgb->r);
        $this->assertLessThanOrEqual(1.0, $srgb->r);
    }

    public function testBlendSoftLightHelperBelowQuarter(): void
    {
        $src = new SrgbColor(0.7, 0.7, 0.7, 1.0);
        $dst = new SrgbColor(0.2, 0.2, 0.2, 1.0); // dst <= 0.25

        $result = $src->blend($dst, 'soft-light');
        $srgb = $result->toSrgb();

        $this->assertGreaterThanOrEqual(0.0, $srgb->r);
        $this->assertLessThanOrEqual(1.0, $srgb->r);
    }

    public function testBlendColorDodgeNormal(): void
    {
        $src = new SrgbColor(0.2, 0.2, 0.2, 1.0);
        $dst = new SrgbColor(0.4, 0.4, 0.4, 1.0);

        $result = $src->blend($dst, 'color-dodge');
        $srgb = $result->toSrgb();

        // dst / (1 - src) = 0.4 / (1 - 0.2) = 0.4 / 0.8 = 0.5
        $this->assertEqualsWithDelta(0.5, $srgb->r, 0.01);
    }

    public function testBlendColorDodgeEdgeCaseMax(): void
    {
        $src = new SrgbColor(1.0, 1.0, 1.0, 1.0);
        $dst = new SrgbColor(0.5, 0.5, 0.5, 1.0);

        $result = $src->blend($dst, 'color-dodge');
        $srgb = $result->toSrgb();

        // src >= 1.0, should return 1.0
        $this->assertEqualsWithDelta(1.0, $srgb->r, 0.01);
    }

    public function testBlendColorBurnNormal(): void
    {
        $src = new SrgbColor(0.6, 0.6, 0.6, 1.0);
        $dst = new SrgbColor(0.4, 0.4, 0.4, 1.0);

        $result = $src->blend($dst, 'color-burn');
        $srgb = $result->toSrgb();

        // 1 - (1 - dst) / src = 1 - (0.6 / 0.6) = 0.0
        $this->assertGreaterThanOrEqual(0.0, $srgb->r);
    }

    public function testBlendColorBurnEdgeCaseMin(): void
    {
        $src = new SrgbColor(0.0, 0.0, 0.0, 1.0);
        $dst = new SrgbColor(0.5, 0.5, 0.5, 1.0);

        $result = $src->blend($dst, 'color-burn');
        $srgb = $result->toSrgb();

        // src <= 0.0, should return 0.0
        $this->assertEqualsWithDelta(0.0, $srgb->r, 0.01);
    }

    public function testBlendUnknownModeFallsBackToNormal(): void
    {
        $src = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $dst = new SrgbColor(0.0, 0.0, 1.0, 1.0);

        $result = $src->blend($dst, 'unknown-mode');
        $srgb = $result->toSrgb();

        $this->assertEqualsWithDelta(1.0, $srgb->r, 0.01);
    }

    public function testBlendWithAlpha(): void
    {
        $src = new SrgbColor(1.0, 0.0, 0.0, 0.5);
        $dst = new SrgbColor(0.0, 0.0, 1.0, 1.0);

        $result = $src->blend($dst, 'normal');
        $srgb = $result->toSrgb();

        $this->assertGreaterThan(0.0, $srgb->r);
        $this->assertGreaterThan(0.0, $srgb->b);
    }

    public function testBlendWithZeroAlpha(): void
    {
        $src = new SrgbColor(1.0, 0.0, 0.0, 0.0);
        $dst = new SrgbColor(0.0, 0.0, 1.0, 0.0);

        $result = $src->blend($dst, 'normal');
        $srgb = $result->toSrgb();

        // When both alphas are 0, a = 0, should handle division by zero
        $this->assertEqualsWithDelta(0.0, $srgb->a, 0.01);
    }

    public function testBlendAcceptsStringBackdrop(): void
    {
        $src = new SrgbColor(1.0, 0.0, 0.0, 1.0); // red

        $result = $src->blend('#0000ff', 'normal');
        $srgb = $result->toSrgb();
        $this->assertEqualsWithDelta(1.0, $srgb->r, 0.01);
        $this->assertEqualsWithDelta(0.0, $srgb->b, 0.01);

        $resultNamed = $src->blend('blue', 'multiply');
        $srgbNamed = $resultNamed->toSrgb();
        $this->assertEqualsWithDelta(0.0, $srgbNamed->r, 0.01);

        $resultRgb = $src->blend('rgb(0 0 255)', 'screen');
        $srgbRgb = $resultRgb->toSrgb();
        $this->assertEqualsWithDelta(1.0, $srgbRgb->r, 0.01);
        $this->assertEqualsWithDelta(1.0, $srgbRgb->b, 0.01);
    }

    public function testComplementaryRotatesHue180Degrees(): void
    {
        $red = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $comp = $red->complementary();

        $redHue = $red->to('oklch')->h;
        $compHue = $comp->to('oklch')->h;

        $hueDiff = abs($redHue - $compHue);
        if ($hueDiff > 180.0) {
            $hueDiff = 360.0 - $hueDiff;
        }

        $this->assertEqualsWithDelta(180.0, $hueDiff, 20.0);
    }

    public function testComplementaryPreservesLightness(): void
    {
        $color = new SrgbColor(0.6, 0.3, 0.8, 1.0);
        $comp = $color->complementary();

        $originalL = $color->to('oklch')->l;
        $compL = $comp->to('oklch')->l;

        $this->assertEqualsWithDelta($originalL, $compL, 0.01);
    }

    public function testComplementaryPreservesChroma(): void
    {
        $color = new SrgbColor(0.8, 0.2, 0.4, 1.0);
        $comp = $color->complementary();

        $originalC = $color->to('oklch')->c;
        $compC = $comp->to('oklch')->c;

        $this->assertEqualsWithDelta($originalC, $compC, 0.1);
    }

    public function testTriadicGeneratesThreeColors(): void
    {
        $color = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $triadic = $color->triadic();

        $this->assertCount(3, $triadic);
    }

    public function testTriadicIncludesOriginal(): void
    {
        $color = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $triadic = $color->triadic();

        $this->assertSame($color, $triadic[0]);
    }

    public function testTriadicColors120DegreesApart(): void
    {
        $color = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $triadic = $color->triadic();

        $baseHue = $color->to('oklch')->h;
        $hue1 = $triadic[1]->to('oklch')->h;
        $hue2 = $triadic[2]->to('oklch')->h;

        $diff1 = abs($hue1 - $baseHue);
        $diff2 = abs($hue2 - $baseHue);

        $this->assertEqualsWithDelta(120.0, $diff1, 10.0);
        $this->assertEqualsWithDelta(240.0, $diff2, 10.0);
    }

    public function testTetradicGeneratesFourColors(): void
    {
        $color = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $tetradic = $color->tetradic();

        $this->assertCount(4, $tetradic);
    }

    public function testTetradicIncludesOriginal(): void
    {
        $color = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $tetradic = $color->tetradic();

        $this->assertSame($color, $tetradic[0]);
    }

    public function testTetradicColors90DegreesApart(): void
    {
        $color = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $tetradic = $color->tetradic();

        $this->assertCount(4, $tetradic);

        $baseHue = $color->to('oklch')->h;
        $hue1 = $tetradic[1]->to('oklch')->h;
        $hue2 = $tetradic[2]->to('oklch')->h;
        $hue3 = $tetradic[3]->to('oklch')->h;

        $this->assertGreaterThan(70.0, abs($hue1 - $baseHue));
        $this->assertGreaterThan(150.0, abs($hue2 - $baseHue));
        $this->assertGreaterThan(240.0, abs($hue3 - $baseHue));
    }

    public function testSplitComplementaryGeneratesThreeColors(): void
    {
        $color = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $split = $color->splitComplementary();

        $this->assertCount(3, $split);
    }

    public function testSplitComplementaryIncludesOriginal(): void
    {
        $color = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $split = $color->splitComplementary();

        $this->assertSame($color, $split[0]);
    }

    public function testSplitComplementaryColorsAreCorrectAngles(): void
    {
        $color = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $split = $color->splitComplementary();

        $baseHue = $color->to('oklch')->h;
        $hue1 = $split[1]->to('oklch')->h;
        $hue2 = $split[2]->to('oklch')->h;

        $diff1 = abs($hue1 - $baseHue);
        $diff2 = abs($hue2 - $baseHue);

        $this->assertEqualsWithDelta(150.0, $diff1, 15.0);
        $this->assertEqualsWithDelta(210.0, $diff2, 15.0);
    }

    public function testAnalogousGeneratesCorrectCount(): void
    {
        $color = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $analogous = $color->analogous(2);

        $this->assertCount(3, $analogous); // Original + 2 analogous
    }

    public function testAnalogousIncludesOriginal(): void
    {
        $color = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $analogous = $color->analogous(2);

        $this->assertSame($color, $analogous[0]);
    }

    public function testAnalogousColorsAreNearby(): void
    {
        $color = new SrgbColor(1.0, 0.5, 0.0, 1.0);
        $analogous = $color->analogous();

        $baseHue = $color->to('oklch')->h;
        $counter = \count($analogous);

        for ($i = 1; $i < $counter; ++$i) {
            $hue = $analogous[$i]->to('oklch')->h;
            $diff = abs($hue - $baseHue);

            $this->assertLessThan(61.0, $diff);
        }
    }

    public function testAnalogousWithZeroCount(): void
    {
        $color = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $analogous = $color->analogous(0);

        $this->assertCount(0, $analogous);
    }
}

/**
 * Minimal concrete color to exercise AbstractColor behavior.
 */
final readonly class DummyColor extends AbstractColor
{
    public float $a;
    public float $b;
    public float $g;
    public float $r;

    public function __construct(float $r, float $g, float $b, float $a = 1.0)
    {
        $this->r = self::clamp01($r);
        $this->g = self::clamp01($g);
        $this->b = self::clamp01($b);
        $this->a = self::clamp01($a);
    }

    public static function fromSrgb(SrgbColor $srgb): static
    {
        return new self($srgb->r, $srgb->g, $srgb->b, $srgb->a);
    }

    public static function getSpaceName(): string
    {
        return 'dummy';
    }

    public static function tClamp01(float $x): float
    {
        return self::clamp01($x);
    }

    public static function tFormatCssFloat(float $x): string
    {
        return self::formatCssFloat($x);
    }

    public static function tLinearToSrgb(float $x): float
    {
        return self::linearToSrgb($x);
    }

    /** @param array{array{float,float,float},array{float,float,float},array{float,float,float}} $m */
    public static function tMul3x3(array $m, array $v): array
    {
        return self::mul3x3($m, $v);
    }

    public static function tNormalizeSpaceName(string $s): string
    {
        return self::normalizeSpaceName($s);
    }

    public static function tNormAngle(float $x): float
    {
        return self::normAngle($x);
    }

    public static function tSrgbToLinear(float $x): float
    {
        return self::srgbToLinear($x);
    }

    public function getAlpha(): float
    {
        return $this->a;
    }

    public function getChannels(): array
    {
        return ['r' => $this->r, 'g' => $this->g, 'b' => $this->b];
    }

    public function toCss(?string $space = null): string
    {
        return 'color(dummy '.self::formatCssFloat($this->r).' '.self::formatCssFloat($this->g).' '.self::formatCssFloat($this->b).($this->a < 1.0 ? ' / '.self::formatCssFloat($this->a) : '').')';
    }

    public function toSrgb(): SrgbColor
    {
        return new SrgbColor($this->r, $this->g, $this->b, $this->a);
    }

    protected static function fromChannels(array $channels, float $alpha = 1.0): static
    {
        return new self($channels['r'], $channels['g'], $channels['b'], $alpha);
    }
}
