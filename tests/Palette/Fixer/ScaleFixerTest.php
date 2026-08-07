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

namespace PhpColor\Color\Tests\Palette\Fixer;

use PhpColor\Color\ColorInterface;
use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\OklchColor;
use PhpColor\Color\Palette\ColorPalette;
use PhpColor\Color\Palette\ColorPaletteInterface;
use PhpColor\Color\Palette\Fixer\ScaleFixer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ScaleFixer::class)]
final class ScaleFixerTest extends TestCase
{
    private ScaleFixer $fixer;

    protected function setUp(): void
    {
        $this->fixer = new ScaleFixer();
    }

    public function testFixSpacesAnAscendingScaleEvenly(): void
    {
        $fixed = $this->fixer->fix(self::scaleOf([0.1, 0.5, 0.55, 0.6, 0.9]));

        self::assertLightness([0.1, 0.3, 0.5, 0.7, 0.9], $fixed);
    }

    public function testFixSpacesADescendingScaleEvenly(): void
    {
        $fixed = $this->fixer->fix(self::scaleOf([0.9, 0.8, 0.75, 0.7, 0.1]));

        self::assertLightness([0.9, 0.7, 0.5, 0.3, 0.1], $fixed);
    }

    public function testFixInfersAscendingDirection(): void
    {
        $fixed = $this->fixer->fix(self::scaleOf([0.2, 0.3, 0.8]), ['direction' => 'auto']);

        self::assertLightness([0.2, 0.5, 0.8], $fixed);
    }

    public function testFixInfersDescendingDirection(): void
    {
        $fixed = $this->fixer->fix(self::scaleOf([0.8, 0.7, 0.2]), ['direction' => 'auto']);

        self::assertLightness([0.8, 0.5, 0.2], $fixed);
    }

    public function testFixTreatsAFlatPaletteAsAscending(): void
    {
        $fixed = $this->fixer->fix(self::scaleOf([0.4, 0.9, 0.4]));

        self::assertLightness([0.4, 0.4, 0.4], $fixed);
    }

    public function testFixRepairsIrregularMiddleEntriesOnly(): void
    {
        $fixed = $this->fixer->fix(self::scaleOf([0.05, 0.07, 0.09, 0.11, 0.85]));

        self::assertLightness([0.05, 0.25, 0.45, 0.65, 0.85], $fixed);
    }

    public function testFixKeepsEndpointsWithoutExplicitBounds(): void
    {
        $lightness = self::lightnessOf($this->fixer->fix(self::scaleOf([0.23, 0.9, 0.41, 0.77])));

        self::assertEqualsWithDelta(0.23, $lightness[0], 1.0e-9);
        self::assertEqualsWithDelta(0.77, $lightness[3], 1.0e-9);
    }

    public function testFixAppliesExplicitBounds(): void
    {
        $fixed = $this->fixer->fix(self::scaleOf([0.3, 0.4, 0.5]), [
            'min_lightness' => 0.0,
            'max_lightness' => 1.0,
        ]);

        self::assertLightness([0.0, 0.5, 1.0], $fixed);
    }

    public function testFixAppliesExplicitBoundsToADescendingScale(): void
    {
        $fixed = $this->fixer->fix(self::scaleOf([0.5, 0.4, 0.3]), [
            'min_lightness' => 0.2,
            'max_lightness' => 0.8,
        ]);

        self::assertLightness([0.8, 0.5, 0.2], $fixed);
    }

    public function testFixAppliesTheLowerBoundAlone(): void
    {
        $fixed = $this->fixer->fix(self::scaleOf([0.3, 0.4, 0.5]), ['min_lightness' => 0.1]);

        self::assertLightness([0.1, 0.3, 0.5], $fixed);
    }

    public function testFixAppliesTheUpperBoundAlone(): void
    {
        $fixed = $this->fixer->fix(self::scaleOf([0.3, 0.4, 0.5]), ['max_lightness' => 0.9]);

        self::assertLightness([0.3, 0.6, 0.9], $fixed);
    }

    public function testFixAcceptsIntegerBounds(): void
    {
        $fixed = $this->fixer->fix(self::scaleOf([0.3, 0.4, 0.5]), [
            'min_lightness' => 0,
            'max_lightness' => 1,
        ]);

        self::assertLightness([0.0, 0.5, 1.0], $fixed);
    }

    public function testFixTreatsNullBoundsAsAbsent(): void
    {
        $fixed = $this->fixer->fix(self::scaleOf([0.2, 0.3, 0.8]), [
            'min_lightness' => null,
            'max_lightness' => null,
        ]);

        self::assertLightness([0.2, 0.5, 0.8], $fixed);
    }

    public function testFixKeepsNamedKeysAndOrder(): void
    {
        $palette = ColorPalette::named([
            'light' => new OklchColor(0.9, 0.1, 250.0),
            'base' => new OklchColor(0.8, 0.1, 250.0),
            'dark' => new OklchColor(0.1, 0.1, 250.0),
        ]);

        $fixed = $this->fixer->fix($palette);

        self::assertTrue($fixed->isNamed());
        self::assertSame(['light', 'base', 'dark'], array_keys($fixed->all()));
        self::assertEqualsWithDelta([0.9, 0.5, 0.1], array_values(self::lightnessOf($fixed)), 1.0e-9);
    }

    public function testFixPreservesHueByDefault(): void
    {
        $fixed = $this->fixer->fix(self::scaleOfHues([30.0, 150.0, 270.0]));

        self::assertEqualsWithDelta([30.0, 150.0, 270.0], self::channelOf($fixed, 'h'), 1.0e-9);
    }

    public function testFixAppliesTheFirstHueWhenPreserveHueIsDisabled(): void
    {
        $fixed = $this->fixer->fix(self::scaleOfHues([30.0, 150.0, 270.0]), ['preserve_hue' => false]);

        self::assertEqualsWithDelta([30.0, 30.0, 30.0], self::channelOf($fixed, 'h'), 1.0e-9);
    }

    public function testFixPreservesChromaByDefault(): void
    {
        $fixed = $this->fixer->fix(self::scaleOfChromas([0.05, 0.15, 0.25]));

        self::assertEqualsWithDelta([0.05, 0.15, 0.25], self::channelOf($fixed, 'c'), 1.0e-9);
    }

    public function testFixAppliesTheFirstChromaWhenPreserveChromaIsDisabled(): void
    {
        $fixed = $this->fixer->fix(self::scaleOfChromas([0.05, 0.15, 0.25]), ['preserve_chroma' => false]);

        self::assertEqualsWithDelta([0.05, 0.05, 0.05], self::channelOf($fixed, 'c'), 1.0e-9);
    }

    public function testFixPreservesAlphaByDefault(): void
    {
        $fixed = $this->fixer->fix(self::scaleOfAlphas([1.0, 0.5, 0.25]));

        self::assertEqualsWithDelta([1.0, 0.5, 0.25], self::channelOf($fixed, 'alpha'), 1.0e-9);
    }

    public function testFixAppliesTheFirstAlphaWhenPreserveAlphaIsDisabled(): void
    {
        $fixed = $this->fixer->fix(self::scaleOfAlphas([0.4, 0.5, 0.25]), ['preserve_alpha' => false]);

        self::assertEqualsWithDelta([0.4, 0.4, 0.4], self::channelOf($fixed, 'alpha'), 1.0e-9);
    }

    public function testFixDoesNotMutateTheSourcePalette(): void
    {
        $palette = self::scaleOf([0.1, 0.8, 0.2, 0.9]);
        $before = self::lightnessOf($palette);

        $fixed = $this->fixer->fix($palette);

        self::assertNotSame($palette, $fixed);
        self::assertSame($before, self::lightnessOf($palette));
    }

    public function testFixIsIdempotent(): void
    {
        $palette = self::scaleOf([0.12, 0.62, 0.31, 0.88]);

        $once = $this->fixer->fix($palette);
        $twice = $this->fixer->fix($once);

        self::assertEqualsWithDelta(
            array_values(self::lightnessOf($once)),
            array_values(self::lightnessOf($twice)),
            1.0e-9
        );
    }

    public function testFixIsIdempotentWithAForcedDirection(): void
    {
        $palette = self::scaleOf([0.9, 0.6, 0.1]);

        $once = $this->fixer->fix($palette, ['direction' => 'ascending']);
        $twice = $this->fixer->fix($once, ['direction' => 'ascending']);

        self::assertEqualsWithDelta(
            array_values(self::lightnessOf($once)),
            array_values(self::lightnessOf($twice)),
            1.0e-9
        );
    }

    public function testFixReorientsADescendingPaletteWhenAscendingIsForced(): void
    {
        $fixed = $this->fixer->fix(self::scaleOf([0.9, 0.6, 0.1]), ['direction' => 'ascending']);

        self::assertLightness([0.1, 0.5, 0.9], $fixed);
    }

    public function testFixReorientsAnAscendingPaletteWhenDescendingIsForced(): void
    {
        $fixed = $this->fixer->fix(self::scaleOf([0.1, 0.6, 0.9]), ['direction' => 'descending']);

        self::assertLightness([0.9, 0.5, 0.1], $fixed);
    }

    public function testFixReturnsASingleColorPaletteUnchanged(): void
    {
        $palette = self::scaleOf([0.42]);

        $fixed = $this->fixer->fix($palette, ['min_lightness' => 0.0, 'max_lightness' => 1.0]);

        self::assertNotSame($palette, $fixed);
        self::assertCount(1, $fixed);
        self::assertEqualsWithDelta([0.42], array_values(self::lightnessOf($fixed)), 1.0e-9);
    }

    public function testFixReturnsASingleNamedColorPaletteUnchanged(): void
    {
        $palette = ColorPalette::named(['base' => new OklchColor(0.42, 0.1, 250.0)]);

        $fixed = $this->fixer->fix($palette);

        self::assertTrue($fixed->isNamed());
        self::assertSame(['base'], array_keys($fixed->all()));
    }

    public function testFixReturnsAnEmptyPaletteAsIs(): void
    {
        $palette = $this->createStub(ColorPaletteInterface::class);
        $palette->method('all')->willReturn([]);

        self::assertSame($palette, $this->fixer->fix($palette));
    }

    public function testFixRejectsUnknownOptions(): void
    {
        $this->expectException(InvalidColorException::class);
        $this->expectExceptionMessage('Unknown option(s): steps.');

        $this->fixer->fix(self::scaleOf([0.1, 0.9]), ['steps' => 5]);
    }

    public function testFixRejectsAnUnknownDirection(): void
    {
        $this->expectException(InvalidColorException::class);

        $this->fixer->fix(self::scaleOf([0.1, 0.9]), ['direction' => 'sideways']);
    }

    public function testFixRejectsANonStringDirection(): void
    {
        $this->expectException(InvalidColorException::class);

        $this->fixer->fix(self::scaleOf([0.1, 0.9]), ['direction' => 1]);
    }

    public function testFixRejectsANonNumericBound(): void
    {
        $this->expectException(InvalidColorException::class);
        $this->expectExceptionMessage('min_lightness option must be numeric.');

        $this->fixer->fix(self::scaleOf([0.1, 0.9]), ['min_lightness' => '0.2']);
    }

    public function testFixRejectsABoundBelowZero(): void
    {
        $this->expectException(InvalidColorException::class);

        $this->fixer->fix(self::scaleOf([0.1, 0.9]), ['min_lightness' => -0.1]);
    }

    public function testFixRejectsABoundAboveOne(): void
    {
        $this->expectException(InvalidColorException::class);
        $this->expectExceptionMessage('max_lightness option must be between 0.0 and 1.0');

        $this->fixer->fix(self::scaleOf([0.1, 0.9]), ['max_lightness' => 1.5]);
    }

    public function testFixRejectsInvertedBounds(): void
    {
        $this->expectException(InvalidColorException::class);
        $this->expectExceptionMessage('Lightness bounds are inverted');

        $this->fixer->fix(self::scaleOf([0.1, 0.9]), ['min_lightness' => 0.8, 'max_lightness' => 0.2]);
    }

    public function testFixRejectsALowerBoundAboveTheHighestEndpoint(): void
    {
        $this->expectException(InvalidColorException::class);
        $this->expectExceptionMessage('Lightness bounds are inverted');

        $this->fixer->fix(self::scaleOf([0.1, 0.3, 0.5]), ['min_lightness' => 0.9]);
    }

    public function testAnalyzeReportsAnEvenScaleAsHealthy(): void
    {
        $analysis = $this->fixer->analyze(self::scaleOf([0.1, 0.3, 0.5, 0.7, 0.9]));

        self::assertSame([], $analysis['issues']);
        self::assertFalse($analysis['needs_repair']);
        self::assertSame('ascending', $analysis['direction']);
        self::assertSame(5, $analysis['color_count']);
        self::assertSame([], $analysis['non_monotonic']);
        self::assertEqualsWithDelta(0.0, $analysis['max_step_deviation'], 1.0e-9);
        self::assertSame($analysis['current_lightness'], $analysis['expected_lightness']);
    }

    public function testAnalyzeReportsIrregularSteps(): void
    {
        $analysis = $this->fixer->analyze(self::scaleOf([0.1, 0.5, 0.55, 0.6, 0.9]));

        self::assertTrue($analysis['needs_repair']);
        self::assertCount(1, $analysis['issues']);
        self::assertStringContainsString('deviate', $analysis['issues'][0]);
        self::assertSame([], $analysis['non_monotonic']);
        self::assertEqualsWithDelta(0.2, $analysis['max_step_deviation'], 1.0e-4);
        self::assertSame([0.1, 0.5, 0.55, 0.6, 0.9], array_values($analysis['current_lightness']));
        self::assertSame([0.1, 0.3, 0.5, 0.7, 0.9], array_values($analysis['expected_lightness']));
    }

    public function testAnalyzeReportsNonMonotonicEntries(): void
    {
        $analysis = $this->fixer->analyze(self::scaleOf([0.1, 0.6, 0.4, 0.9]));

        self::assertTrue($analysis['needs_repair']);
        self::assertSame([2], $analysis['non_monotonic']);
        self::assertStringContainsString('not ascending at: 2', $analysis['issues'][0]);
    }

    public function testAnalyzeReportsNonMonotonicEntriesOfADescendingScale(): void
    {
        $analysis = $this->fixer->analyze(self::scaleOf([0.9, 0.4, 0.6, 0.1]));

        self::assertSame('descending', $analysis['direction']);
        self::assertSame([2], $analysis['non_monotonic']);
        self::assertStringContainsString('not descending at: 2', $analysis['issues'][0]);
    }

    public function testAnalyzeUsesNamedKeys(): void
    {
        $palette = ColorPalette::named([
            'light' => new OklchColor(0.9, 0.1, 250.0),
            'base' => new OklchColor(0.8, 0.1, 250.0),
            'dark' => new OklchColor(0.1, 0.1, 250.0),
        ]);

        $analysis = $this->fixer->analyze($palette);

        self::assertSame(['light', 'base', 'dark'], array_keys($analysis['current_lightness']));
        self::assertSame(['light', 'base', 'dark'], array_keys($analysis['expected_lightness']));
        self::assertSame(0.5, $analysis['expected_lightness']['base']);
    }

    public function testAnalyzeReportsASingleColorPalette(): void
    {
        $analysis = $this->fixer->analyze(self::scaleOf([0.42]));

        self::assertNull($analysis['direction']);
        self::assertSame(1, $analysis['color_count']);
        self::assertFalse($analysis['needs_repair']);
        self::assertCount(1, $analysis['issues']);
        self::assertSame([0.42], array_values($analysis['current_lightness']));
        self::assertSame($analysis['current_lightness'], $analysis['expected_lightness']);
    }

    public function testAnalyzeLeavesThePaletteUntouched(): void
    {
        $palette = self::scaleOf([0.1, 0.8, 0.2, 0.9]);
        $before = self::lightnessOf($palette);

        $this->fixer->analyze($palette);

        self::assertSame($before, self::lightnessOf($palette));
    }

    public function testAnalyzeExpectationsMatchTheFixResult(): void
    {
        $palette = self::scaleOf([0.15, 0.7, 0.35, 0.85]);

        $analysis = $this->fixer->analyze($palette);
        $fixed = self::lightnessOf($this->fixer->fix($palette));

        self::assertEqualsWithDelta(
            array_values($analysis['expected_lightness']),
            array_values($fixed),
            1.0e-4
        );
    }

    /**
     * @param list<float> $lightness
     */
    private static function scaleOf(array $lightness): ColorPalette
    {
        return ColorPalette::scale(array_map(
            static fn (float $l): OklchColor => new OklchColor($l, 0.1, 250.0),
            $lightness
        ));
    }

    /**
     * @param list<float> $hues
     */
    private static function scaleOfHues(array $hues): ColorPalette
    {
        return ColorPalette::scale(array_map(
            static fn (float $h, int $i): OklchColor => new OklchColor(0.2 + 0.05 * $i, 0.1, $h),
            $hues,
            array_keys($hues)
        ));
    }

    /**
     * @param list<float> $chromas
     */
    private static function scaleOfChromas(array $chromas): ColorPalette
    {
        return ColorPalette::scale(array_map(
            static fn (float $c, int $i): OklchColor => new OklchColor(0.2 + 0.05 * $i, $c, 250.0),
            $chromas,
            array_keys($chromas)
        ));
    }

    /**
     * @param list<float> $alphas
     */
    private static function scaleOfAlphas(array $alphas): ColorPalette
    {
        return ColorPalette::scale(array_map(
            static fn (float $alpha, int $i): OklchColor => new OklchColor(0.2 + 0.05 * $i, 0.1, 250.0, $alpha),
            $alphas,
            array_keys($alphas)
        ));
    }

    /**
     * @return array<int|string, float>
     */
    private static function lightnessOf(ColorPaletteInterface $palette): array
    {
        $lightness = [];
        foreach ($palette->all() as $key => $color) {
            $lightness[$key] = self::oklchOf($color)->l;
        }

        return $lightness;
    }

    /**
     * @return list<float>
     */
    private static function channelOf(ColorPaletteInterface $palette, string $channel): array
    {
        $values = [];
        foreach ($palette->all() as $color) {
            $oklch = self::oklchOf($color);
            $values[] = match ($channel) {
                'c' => $oklch->c,
                'h' => $oklch->h,
                default => $oklch->alpha,
            };
        }

        return $values;
    }

    private static function oklchOf(ColorInterface $color): OklchColor
    {
        $oklch = $color->to('oklch');
        self::assertInstanceOf(OklchColor::class, $oklch);

        return $oklch;
    }

    /**
     * @param list<float> $expected
     */
    private static function assertLightness(array $expected, ColorPaletteInterface $palette): void
    {
        self::assertEqualsWithDelta($expected, array_values(self::lightnessOf($palette)), 1.0e-9);
    }
}
