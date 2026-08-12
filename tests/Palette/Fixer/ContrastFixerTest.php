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

use PhpColor\Color\Color;
use PhpColor\Color\ColorInterface;
use PhpColor\Color\Exception\InvalidArgumentException;
use PhpColor\Color\OklchColor;
use PhpColor\Color\Palette\ColorPalette;
use PhpColor\Color\Palette\ColorPaletteInterface;
use PhpColor\Color\Palette\Fixer\ContrastFixer;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContrastFixer::class)]
final class ContrastFixerTest extends TestCase
{
    private ContrastFixer $fixer;

    protected function setUp(): void
    {
        $this->fixer = new ContrastFixer();
    }

    public function testAnalyzeDetectsLowContrast(): void
    {
        // Light gray on white - poor contrast
        $palette = ColorPalette::fromHex(['#cccccc']);

        $analysis = $this->fixer->analyze($palette);

        $this->assertGreaterThan(0, $analysis['wcag_aa_failures']);
        $this->assertNotEmpty($analysis['issues']);
    }

    public function testAnalyzeDetectsGoodContrast(): void
    {
        // Black on white - excellent contrast
        $palette = ColorPalette::fromHex(['#000000']);

        $analysis = $this->fixer->analyze($palette);

        $this->assertSame(0, $analysis['wcag_aa_failures']);
        $this->assertEmpty($analysis['issues']);
    }

    public function testFixImprovesContrast(): void
    {
        // Light gray has poor contrast on white
        $palette = ColorPalette::fromHex(['#cccccc']);
        $white = Color::parse('#ffffff');

        $originalContrast = Color::contrast($palette->get(0), $white);

        $fixed = $this->fixer->fix($palette, [
            'min_contrast' => 4.5,
            'background_color' => $white,
        ]);

        $newContrast = Color::contrast($fixed->get(0), $white);

        $this->assertGreaterThan($originalContrast, $newContrast);
        $this->assertGreaterThanOrEqual(4.5, $newContrast);
    }

    public function testFixPreservesHue(): void
    {
        $palette = ColorPalette::fromHex(['#ffcccc']); // Light red
        $originalHue = $palette->get(0)->to('oklch')->h;

        $fixed = $this->fixer->fix($palette, [
            'min_contrast' => 4.5,
            'preserve_hue' => true,
        ]);

        $fixedHue = $fixed->get(0)->to('oklch')->h;

        $this->assertEqualsWithDelta($originalHue, $fixedHue, 1.0);
    }

    public function testFixPreservesChroma(): void
    {
        $palette = ColorPalette::fromHex(['#ffcccc']); // Light red
        $originalChroma = $palette->get(0)->to('oklch')->c;

        $fixed = $this->fixer->fix($palette, [
            'min_contrast' => 4.5,
            'preserve_chroma' => true,
        ]);

        $fixedChroma = $fixed->get(0)->to('oklch')->c;

        $this->assertEqualsWithDelta($originalChroma, $fixedChroma, 0.01);
    }

    public function testFixDefaultBehaviorUnchangedWithBothPreserved(): void
    {
        // Golden master captured from the released behavior before this
        // fixer started honoring preserve_hue/preserve_chroma: false.
        $palette = ColorPalette::parse(['oklch(0.70 0.25 0)']);
        $black = Color::parse('#000000');

        $fixed = $this->fixer->fix($palette, [
            'min_contrast' => 7.0,
            'background_color' => $black,
            'adjust_mode' => 'darken',
            'max_iterations' => 2,
        ]);

        $this->assertSame('#e9007a', $fixed->get(0)->toHex());
    }

    public function testFixRelaxesChromaWhenPreserveChromaIsFalse(): void
    {
        $palette = ColorPalette::parse(['oklch(0.70 0.25 0)']);
        $black = Color::parse('#000000');
        $options = [
            'min_contrast' => 7.0,
            'background_color' => $black,
            'adjust_mode' => 'darken',
            'max_iterations' => 2,
        ];

        $preserved = $this->fixer->fix($palette, $options + ['preserve_chroma' => true]);
        $relaxed = $this->fixer->fix($palette, $options + ['preserve_chroma' => false]);

        // The fixed chroma search cannot reach 7:1 within 2 iterations.
        $this->assertLessThan(7.0, Color::contrast($preserved->get(0), $black));

        // Relaxing chroma reaches it, by falling back to grayscale.
        $this->assertGreaterThanOrEqual(7.0, Color::contrast($relaxed->get(0), $black));
        $this->assertEqualsWithDelta(0.0, $relaxed->get(0)->to('oklch')->c, 1e-6);
    }

    public function testFixRelaxesHueWhenPreserveHueIsFalse(): void
    {
        $palette = ColorPalette::parse(['oklch(0.70 0.25 0)']);
        $black = Color::parse('#000000');
        $options = [
            'min_contrast' => 7.0,
            'background_color' => $black,
            'adjust_mode' => 'darken',
            'max_iterations' => 2,
        ];

        $preserved = $this->fixer->fix($palette, $options + ['preserve_hue' => true]);
        $relaxed = $this->fixer->fix($palette, $options + ['preserve_hue' => false]);

        $this->assertLessThan(7.0, Color::contrast($preserved->get(0), $black));
        $this->assertGreaterThanOrEqual(7.0, Color::contrast($relaxed->get(0), $black));

        $relaxedOklch = $relaxed->get(0)->to('oklch');
        // Nearest-first search picks the smallest hue offset that reaches
        // target; chroma stays untouched since preserve_chroma was not set.
        $this->assertEqualsWithDelta(30.0, $relaxedOklch->h, 0.5);
        $this->assertEqualsWithDelta(0.25, $relaxedOklch->c, 1e-6);
    }

    public function testFixRelaxesBothChromaAndHueTogether(): void
    {
        $palette = ColorPalette::parse(['oklch(0.70 0.25 0)']);
        $black = Color::parse('#000000');

        $relaxed = $this->fixer->fix($palette, [
            'min_contrast' => 7.0,
            'background_color' => $black,
            'adjust_mode' => 'darken',
            'max_iterations' => 2,
            'preserve_hue' => false,
            'preserve_chroma' => false,
        ]);

        $this->assertGreaterThanOrEqual(7.0, Color::contrast($relaxed->get(0), $black));
    }

    public function testFixKeepsHueRelaxationEffectiveAfterChromaRelaxationFails(): void
    {
        $palette = ColorPalette::parse(['oklch(0.40 0.40 90)']);
        $white = Color::parse('#ffffff');
        $options = [
            'min_contrast' => 15.0,
            'background_color' => $white,
            'adjust_mode' => 'auto',
            'max_iterations' => 2,
        ];

        $chromaOnly = $this->fixer->fix($palette, $options + [
            'preserve_hue' => true,
            'preserve_chroma' => false,
        ]);
        $bothRelaxed = $this->fixer->fix($palette, $options + [
            'preserve_hue' => false,
            'preserve_chroma' => false,
        ]);

        $this->assertLessThan(15.0, Color::contrast($chromaOnly->get(0), $white));
        $this->assertGreaterThanOrEqual(15.0, Color::contrast($bothRelaxed->get(0), $white));
        $this->assertGreaterThan(0.0, $bothRelaxed->get(0)->to('oklch')->c);
    }

    public function testFixWithDarkBackground(): void
    {
        // Light text on dark background
        $palette = ColorPalette::fromHex(['#333333']); // Dark gray
        $black = Color::parse('#000000');

        $fixed = $this->fixer->fix($palette, [
            'min_contrast' => 4.5,
            'background_color' => $black,
            'adjust_mode' => 'lighten',
        ]);

        $newContrast = Color::contrast($fixed->get(0), $black);

        $this->assertGreaterThanOrEqual(4.5, $newContrast);
    }

    public function testFixAutoMode(): void
    {
        $palette = ColorPalette::fromHex(['#cccccc']);
        $white = Color::parse('#ffffff');

        $fixed = $this->fixer->fix($palette, [
            'min_contrast' => 4.5,
            'background_color' => $white,
            'adjust_mode' => 'auto', // Should darken for light background
        ]);

        $fixedColor = $fixed->get(0);
        $originalColor = $palette->get(0);

        // Should be darker
        $this->assertLessThan(
            $originalColor->to('oklch')->l,
            $fixedColor->to('oklch')->l
        );
    }

    public function testFixLeavesGoodContrastUnchanged(): void
    {
        $palette = ColorPalette::fromHex(['#000000']); // Black - good contrast
        $white = Color::parse('#ffffff');

        $fixed = $this->fixer->fix($palette, [
            'min_contrast' => 4.5,
            'background_color' => $white,
        ]);

        $this->assertSame(
            $palette->get(0)->toHex(),
            $fixed->get(0)->toHex()
        );
    }

    public function testFixMultipleColors(): void
    {
        $palette = ColorPalette::fromHex(['#cccccc', '#dddddd', '#eeeeee']);

        $fixed = $this->fixer->fix($palette, [
            'min_contrast' => 4.5,
        ]);

        $white = Color::parse('#ffffff');

        foreach ($fixed->all() as $color) {
            $contrast = Color::contrast($color, $white);
            $this->assertGreaterThanOrEqual(4.5, $contrast);
        }
    }

    public function testFixWithWcagAaaLevel(): void
    {
        $palette = ColorPalette::fromHex(['#666666']);

        $fixed = $this->fixer->fix($palette, [
            'min_contrast' => 7.0, // WCAG AAA
        ]);

        $white = Color::parse('#ffffff');
        $contrast = Color::contrast($fixed->get(0), $white);

        $this->assertGreaterThanOrEqual(7.0, $contrast);
    }

    public function testFixPreservesNamedPalette(): void
    {
        $palette = ColorPalette::fromHex([
            'text' => '#cccccc',
            'heading' => '#dddddd',
        ]);

        $fixed = $this->fixer->fix($palette);

        $this->assertTrue($fixed->isNamed());
        $this->assertSame(['text', 'heading'], $fixed->names());
    }

    public function testFixWithStringBackgroundColor(): void
    {
        $palette = ColorPalette::fromHex(['#cccccc']);

        $fixed = $this->fixer->fix($palette, [
            'background_color' => '#000000', // String format
            'adjust_mode' => 'lighten',
        ]);

        $black = Color::parse('#000000');
        $contrast = Color::contrast($fixed->get(0), $black);

        $this->assertGreaterThanOrEqual(4.5, $contrast);
    }

    public function testAnalyzeReportsSeverity(): void
    {
        $palette = ColorPalette::fromHex([
            '#000000', // Good contrast
            '#666666', // Moderate contrast (fails AAA)
            '#cccccc', // Poor contrast (fails AA)
        ]);

        $analysis = $this->fixer->analyze($palette);

        $this->assertSame(1, $analysis['wcag_aa_failures']);
        $this->assertGreaterThanOrEqual(1, $analysis['wcag_aaa_failures']);

        // Check severity levels
        $severities = array_column($analysis['issues'], 'severity');
        $this->assertContains('wcag_aa_fail', $severities);
    }

    public function testFixThrowsWhenMinContrastIsNotNumeric(): void
    {
        $palette = ColorPalette::fromHex(['#cccccc']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('min_contrast option must be numeric.');

        $this->fixer->fix($palette, ['min_contrast' => 'high']);
    }

    public function testFixThrowsWhenMaxIterationsIsNotInteger(): void
    {
        $palette = ColorPalette::fromHex(['#cccccc']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('max_iterations option must be an integer.');

        $this->fixer->fix($palette, ['max_iterations' => 'ten']);
    }

    public function testAutoModeLightensOnDarkBackground(): void
    {
        $palette = ColorPalette::fromHex(['#222222']);
        $black = Color::parse('#000000');

        $fixed = $this->fixer->fix($palette, [
            'min_contrast' => 4.5,
            'background_color' => $black,
            'adjust_mode' => 'auto',
        ]);

        $this->assertGreaterThan(
            $palette->get(0)->to('oklch')->l,
            $fixed->get(0)->to('oklch')->l
        );
    }

    public function testLightenModeClampsAtMaximumLightness(): void
    {
        $palette = ColorPalette::fromHex(['#ffffff']);
        $black = Color::parse('#000000');

        $fixed = $this->fixer->fix($palette, [
            'min_contrast' => 22.0,
            'background_color' => $black,
            'adjust_mode' => 'lighten',
            'max_iterations' => 1,
        ]);

        $this->assertEqualsWithDelta(1.0, $fixed->get(0)->to('oklch')->l, 1e-6);
    }

    public function testAdjustColorContrastHandlesConvertibleColorsWithUnknownMode(): void
    {
        $color = $this->createConvertibleColorStub(0.45);
        $background = Color::parse('#202020');

        /** @var OklchColor $adjusted */
        $adjusted = $this->invokeFixerMethod('adjustColorContrast', [
            $color,
            $background,
            7.0,
            'unexpected-mode',
            false,
            false,
            3,
        ]);

        $this->assertInstanceOf(OklchColor::class, $adjusted);
        $this->assertGreaterThan(0.0, $adjusted->l);
    }

    public function testFixAcceptsColorInterfaceBackgroundOption(): void
    {
        $palette = ColorPalette::fromHex(['#cccccc']);
        $background = Color::parse('#101010');

        $fixed = $this->fixer->fix($palette, [
            'background_color' => $background,
            'min_contrast' => 4.5,
        ]);

        $this->assertGreaterThanOrEqual(4.5, Color::contrast($fixed->get(0), $background));
    }

    public function testFixFallsBackToWhiteBackgroundForInvalidType(): void
    {
        $palette = ColorPalette::fromHex(['#aaaaaa']);

        $defaultFixed = $this->fixer->fix($palette);
        $fallbackFixed = $this->fixer->fix($palette, [
            'background_color' => 123,
        ]);

        $this->assertSame($defaultFixed->get(0)->toHex(), $fallbackFixed->get(0)->toHex());
    }

    public function testFixThrowsWhenNamedPaletteHasNonStringKey(): void
    {
        $color = Color::parse('#333333');
        $palette = $this->createStub(ColorPaletteInterface::class);
        $palette->method('all')->willReturn([123 => $color]);
        $palette->method('isNamed')->willReturn(true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Named palettes must use string keys.');
        $this->fixer->fix($palette);
    }

    public function testParseBackgroundColorDefaultsToWhite(): void
    {
        /** @var ColorInterface $color */
        $color = $this->invokeFixerMethod('parseBackgroundColor', [null]);

        $this->assertSame('#ffffff', $color->toHex());
    }

    public function testParseBackgroundColorReturnsProvidedColorInterface(): void
    {
        $background = Color::parse('#123456');

        /** @var ColorInterface $color */
        $color = $this->invokeFixerMethod('parseBackgroundColor', [$background]);

        $this->assertSame($background, $color);
    }

    public function testAdjustColorContrastLightensUntilThreshold(): void
    {
        $color = Color::parse('#222222');
        $background = Color::parse('#000000');

        /** @var ColorInterface $adjusted */
        $adjusted = $this->invokeFixerMethod('adjustColorContrast', [
            $color,
            $background,
            7.0,
            'lighten',
            true,
            true,
            50,
        ]);

        $this->assertGreaterThanOrEqual(7.0, Color::contrast($adjusted, $background));
        $this->assertGreaterThan($color->to('oklch')->l, $adjusted->to('oklch')->l);
    }

    public function testAdjustColorContrastDarkensWhenRequested(): void
    {
        $color = Color::parse('#eeeeee');
        $background = Color::parse('#ffffff');

        /** @var ColorInterface $adjusted
         */
        $adjusted = $this->invokeFixerMethod('adjustColorContrast', [
            $color,
            $background,
            7.0,
            'darken',
            true,
            true,
            50,
        ]);

        $this->assertGreaterThanOrEqual(7.0, Color::contrast($adjusted, $background));
        $this->assertLessThan($color->to('oklch')->l, $adjusted->to('oklch')->l);
    }

    /**
     * @param non-empty-string  $method
     * @param array<int, mixed> $arguments
     */
    private function invokeFixerMethod(string $method, array $arguments = []): mixed
    {
        $callable = \Closure::bind(
            static fn (ContrastFixer $fixer, string $method, array $arguments): mixed => $fixer->{$method}(...$arguments),
            null,
            ContrastFixer::class,
        );

        return $callable($this->fixer, $method, $arguments);
    }

    private function createConvertibleColorStub(float $luminance = 0.2): ColorInterface
    {
        $srgb = new SrgbColor(0.2, 0.3, 0.4);

        $color = $this->createStub(ColorInterface::class);
        $color->method('to')->willReturn($srgb);
        $color->method('getLuminance')->willReturn($luminance);

        return $color;
    }
}
