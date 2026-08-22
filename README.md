<h1 align="center">
  <a href="https://phpcolor.dev">
    <img src="https://cdn.jsdelivr.net/gh/phpcolor/.github@main/assets/phpcolor-banner-1200.png" alt="PHP COLOR - Modern Color Library" width="1200" height="630">
  </a>
</h1>

<div align="center">

A PHP library for color: 15 color spaces, perceptual mixing, WCAG contrast,<br>
palette generation, CSS Level 4/5 support, and vision simulation.

&nbsp; ![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-e3cffc?labelColor=050609)
&nbsp; ![CI](https://img.shields.io/github/actions/workflow/status/phpcolor/phpcolor/CI.yaml?branch=main&label=Tests&labelColor=050609&color=ff63de)
&nbsp; ![Coverage](https://img.shields.io/badge/PHPUnit-100%25-facc1d?labelColor=050609)
&nbsp; ![PHPStan](https://img.shields.io/badge/PHPStan-level%2010-6ae01d?labelColor=050609)
&nbsp; ![Release](https://img.shields.io/github/v/release/phpcolor/phpcolor?label=Stable&labelColor=050609&color=0ee8a3)
&nbsp; ![License](https://img.shields.io/github/license/phpcolor/phpcolor?label=License&labelColor=050609&color=0eecf3)
</div>

## Color Library

* **Modern CSS Support**: Full `CSS Color 4/5` parsing, including `oklch()`, `color-mix()`, and relative color syntax.
* **Perceptual Accuracy**: Uses **Oklab** for mixing and gradients to ensure uniform brightness and hue stability.
* **15 Color Spaces**: Direct conversion between `sRGB`, P3, Lab, XYZ, and more via optimized matrices.
* **Immutable API**: Chainable value objects for colors, palettes, and gradients.
* **Accessibility First**: `WCAG 2.1`/APCA contrast ratios, contrast solver, and 7-profile vision simulation.
* **Developer Friendly**: PHP 8.3+, zero dependencies, typed enums, and PHPStan level 10.

## Installation

```bash
composer require phpcolor/phpcolor
```

```php
use PhpColor\Color\Color;

$color = Color::parse('oklch(65% 0.15 210)');
$lighter = $color->lighten(0.1)->to('display-p3');

echo $lighter->toCss(); // color(display-p3 0.45 0.72 0.88)
```

## Create

The `Color` class is the main entry point. It accepts strings anywhere a
`ColorInterface` is expected, and provides named constructors for every
supported
color space.

### From CSS strings

Any valid CSS color string is accepted, including modern Level 4/5 syntax.

```php
use PhpColor\Color\Color;

$color = Color::parse('#3b82f6');
$color = Color::parse('hsl(217 91% 60%)');
$color = Color::parse('oklch(0.65 0.18 264)');
$color = Color::parse('color(display-p3 0.23 0.51 0.96)');
$color = Color::parse('rebeccapurple');
```

`Color::tryFrom()` returns `null` on invalid input instead of throwing.

### From channel values

```php
$color = Color::rgb(0.23, 0.51, 0.96);
$color = Color::rgb(0.23, 0.51, 0.96, 0.8); // with alpha
$color = Color::oklab(0.62, -0.05, -0.15);
$color = Color::oklch(0.65, 0.18, 264);
$color = Color::hex('#3b82f6');

// Named shortcuts
$black = Color::black();
$white = Color::white();
$red   = Color::red();
```

-> [Parsing docs](docs/getting-started/parsing.md)


## Convert

Every color object converts to any supported color space. Conversions are exact
and round-trip safely. The result is always a new typed object that can be
further manipulated or [formatted](#format).

```php
// Typed conversions
$oklab = $color->toOklab();
$oklch = $color->toOklch();
$srgb  = $color->toSrgb();
$p3    = $color->toDisplayP3();
$lab   = $color->toLab();
$xyz   = $color->toXyz();

// Generic conversion by name
$color->to('oklch');
$color->to('display-p3');
$color->to('rec2020');
```

-> [Color spaces docs](docs/core-concepts/color-spaces.md)

## Format

Formatting produces a string or structured value from any color object.
The output space defaults to the color's current space, but can be overridden.

```php
// CSS string (default: native space)
$color->toCss();                // 'oklch(0.65 0.18 264)'
$color->toCss('hsl');           // 'hsl(217 91% 60%)'
$color->toCss('srgb');          // 'rgb(59 130 246)'
$color->toCss('color');         // 'color(srgb 0.231 0.510 0.965)'

// Hex
$color->toHex();                // '#3b82f6'
$color->toHex(withAlpha: true); // '#3b82f6cc'

// String cast (same as toCss())
(string) $color;
$color->toString();

// Raw channel values
$color->getChannels();          // ['r' => 0.23, 'g' => 0.51, 'b' => 0.96]
```

## Inspect

Color objects expose read methods for common attributes regardless of the
underlying space. All values are normalized to a consistent scale.

### Lightness and luminance

```php
$color->isLight();       // perceived brightness
$color->isDark();
$color->getLuminance();  // relative luminance 0-1, per WCAG
```

### Hue, saturation, opacity

```php
$color->getSaturation(); // 0-1
$color->getHue();        // 0-360 degrees
$color->getOpacity();    // 0-1
$color->isOpaque();
$color->isTransparent();
$color->equals($other);  // perceptual equality check
```

### Temperature

```php
$color->temperature();   // estimated value in Kelvin
$color->isHot();
$color->isCold();
```

-> [Hue docs](docs/reference/hue.md) · [Chroma docs](docs/reference/chroma.md) · [Temperature docs](docs/reference/temperature.md)

## Manipulate

All manipulation methods return a new instance. Colors are immutable and chain
freely across color spaces.

### Lightness

```php
$lighter = $color->lighten(0.15);
$darker  = $color->darken(0.15);
$grey    = $color->grayscale();
$inv     = $color->invert();
```

### Saturation and hue

```php
$vivid   = $color->saturate(0.1);
$muted   = $color->desaturate(0.2);
$shifted = $color->rotateHue(30);
```

### Temperature

```php
$warmer = $color->warm(0.1);
$cooler = $color->cool(0.1);
```

### Transparency

```php
$semiOpaque = $color->withAlpha(0.5);
```

### Blend

Blends this color against a backdrop using standard CSS compositing modes.

```php
$blended = $color->blend('#000000', 'multiply');
$blended = $color->blend($other, 'screen');
// Also: normal, overlay, darken, lighten
```

For perceptual two-color interpolation, see [Mix](#mix).

### Channel control

Direct channel access is useful when you need to set an exact value in a
specific color space rather than applying a relative shift.

```php
$adjusted = $color->toOklch()->withChannel('l', 0.75);
$adjusted = $color->toOklch()->withChannels(['l' => 0.75, 'c' => 0.12]);

// Chain across spaces
$result = $color->toOklch()->withChannel('h', 120)->to('display-p3')->toCss();
```

-> [Hue docs](docs/reference/hue.md) · [Chroma docs](docs/reference/chroma.md)

## Mix

Mixing blends two colors at a given ratio. Oklab is the default interpolation
space, which produces perceptually uniform results without the hue shift and
brightness dip of sRGB mixing.

```php
// 50/50 blend in Oklab (default)
$mixed = Color::mix(Color::red(), Color::blue(), 0.5);

// Control the interpolation space
$mixed = Color::mix('#ff0000', '#0000ff', 0.3, 'oklch');
$mixed = Color::mix($a, $b, 0.5, 'srgb');

// Tint (toward white) and shade (toward black) on a single color
$tinted = $color->tint(0.3);  // 30% white
$shaded = $color->shade(0.3); // 30% black
```

For multi-step interpolation across a full range, see [Palettes](#palettes).

-> [Mixing docs](docs/guides/mixing.md)

## Harmonies

These methods generate sets of colors that are harmonically balanced in hue,
based on color theory intervals. They operate on any color object and return
arrays in the same color space. For detecting or fixing harmony across an
existing palette, see [Palettes](#palettes).

```php
// Two colors 30 degrees away on either side
[$left, $right] = $color->analogous();
$set = $color->analogous(count: 4);

// Direct opposite on the hue wheel
$opposite = $color->complementary();

// Two colors flanking the complement
[$a, $b] = $color->splitComplementary();

// Three colors evenly spaced (120 degrees apart)
[$a, $b, $c] = $color->triadic();

// Four colors (90 degrees apart)
[$a, $b, $c, $d] = $color->tetradic();
```

-> [Harmony docs](docs/guides/harmony.md)

## Palettes

A palette is an ordered or named collection of colors. It supports generation,
batch transformation, lookup, and CSS export.

### Generate

```php
use PhpColor\Color\Palette\ColorPalette;

// From a base color
$tints  = ColorPalette::tints(Color::parse('#3b82f6'), steps: 9);
$shades = ColorPalette::shades(Color::parse('#3b82f6'), steps: 9);
$scale  = ColorPalette::lightnessScale(Color::parse('#3b82f6'), steps: 11);
$ramp   = ColorPalette::interpolate(Color::red(), Color::blue(), steps: 7);

// From existing colors
$palette = ColorPalette::fromHex(['#ff0000', '#00ff00', '#0000ff']);
$palette = ColorPalette::parse(['red', 'oklch(0.6 0.2 264)', '#3b82f6']);
```

### Transform

All transformation methods return a new palette. They mirror the single-color
[Manipulate](#manipulate) API.

```php
$darkened  = $scale->darken(0.1);
$shifted   = $scale->rotateHue(30)->desaturate(0.05);
$p3palette = $scale->to('display-p3');

// Filter and map (map callable must return a ColorInterface)
$light    = $scale->filter(fn($c) => $c->toOklch()->l > 0.5);
$inverted = $scale->map(fn($c) => $c->rotateHue(180));

// Structural: merge(), slice(), reverse(), count()
```

### Lookup

```php
$closest = $scale->closest(Color::parse('#4f46e5')); // by Oklab distance
$mid     = $scale->at(0.5);                          // interpolated position
```

### Export

Named palettes produce CSS custom properties directly, useful for design token
pipelines.

```php
$ui = ColorPalette::named([
    'primary'   => Color::parse('#3b82f6'),
    'secondary' => Color::parse('#8b5cf6'),
    'neutral'   => Color::parse('#6b7280'),
]);

echo $ui->toCssVariables('color');
// --color-primary: #3b82f6;
// --color-secondary: #8b5cf6;
// --color-neutral: #6b7280;

// Array export
$hexArray = $scale->toHex();
$cssArray = $scale->toCss('oklch');
```

-> [Palette docs](docs/guides/palette.md)

## Gradients

The gradient builder uses a fluent API modeled on CSS gradient syntax. Oklab
interpolation is the default, avoiding the hue shift and brightness loss of sRGB
gradients. For two-color blending, see [Mix](#mix).

```php
use PhpColor\Color\Gradient\GradientBuilder;

// Linear gradient
$gradient = GradientBuilder::linear(90)
    ->from('#ff0000')
    ->via('#a855f7')
    ->to('#3b82f6')
    ->in('oklch')
    ->build();

$gradient->toCss(); // 'linear-gradient(90deg, ...)'

// Radial gradient with shape and position
GradientBuilder::radial()
    ->circle()
    ->at('center')
    ->from('white')
    ->to('black')
    ->build();

// Conic gradient with explicit stop positions (0.0-1.0)
GradientBuilder::conic(0)
    ->stop('red', 0.0)
    ->stop('yellow', 0.33)
    ->stop('blue', 0.66)
    ->build();
```

-> [Gradient docs](docs/guides/gradient.md)

## Accessibility

### WCAG contrast

`ColorContrast` calculates contrast ratios per WCAG 2.x. The `WcagLevel` enum
prevents invalid inputs and replaces the old string-based API.

```php
use PhpColor\Color\Contrast\ColorContrast;
use PhpColor\Color\Contrast\WcagLevel;

$ratio = ColorContrast::calculate(Color::black(), Color::white()); // 21.0

ColorContrast::meetsFor($text, $background, WcagLevel::AA);
ColorContrast::meetsFor($text, $background, WcagLevel::AAA, largeText: true);

ColorContrast::requiredRatio(WcagLevel::AA);  // 4.5 normal, 3.0 large text
ColorContrast::requiredRatio(WcagLevel::AAA); // 7.0 normal, 4.5 large text
```

### APCA

APCA (Accessible Perceptual Contrast Algorithm) is a newer model that accounts
for font weight and size. It returns a signed lightness contrast value rather
than a simple ratio.

```php
use PhpColor\Color\Contrast\ApcaContrast;

$lc = ApcaContrast::lc($text, $background);
```

### Solving contrast

`ContrastSolver` helps fix colors that fail a requirement, rather than just
checking them. See also [Vision simulation](#vision) for accessibility testing
across color vision profiles.

```php
use PhpColor\Color\Contrast\ContrastSolver;

// Adjust lightness until the color passes the target ratio
$fixed = ContrastSolver::adjustLightnessToContrast($text, $background, 4.5);

// Pick the most accessible option from a set of candidates
$best = ContrastSolver::bestOn($background, [$white, $black, $brand]);

// Find the minimum opacity at which a color passes on a given background
$alpha = ContrastSolver::requiredAlpha($text, $background, 4.5);
```

-> [Contrast docs](docs/reference/contrast.md)

## Distance

Color distance measures how different two colors appear to a human observer.
PHPColor uses CIEDE2000 by default, the most perceptually accurate formula
available. `Color::deltaE()` is a convenient shorthand.

```php
// Via the Color facade
$delta = Color::deltaE($a, $b);
$dist  = Color::distance($a, $b, 'CMC');

// Direct, with explicit algorithm choice
use PhpColor\Color\Distance\ColorDistance;

$delta = ColorDistance::deltaE($a, $b);                 // CIEDE2000
$dist  = ColorDistance::calculate($a, $b, 'DeltaE94');
$dist  = ColorDistance::calculate($a, $b, 'CMC');
```

Distance is used internally by `ColorPalette::closest()` and by
`ColorVisionSimulator::areDistinguishable()`. See [Vision](#vision).

-> [Distance docs](docs/reference/distance.md)

## Vision

`ColorVisionSimulator` applies transformation matrices that approximate how
colors appear under a given vision deficiency profile. Useful for testing
whether a UI or palette works for colorblind users.

```php
use PhpColor\Color\Vision\ColorVisionSimulator;
use PhpColor\Color\Vision\VisionProfile;

$simulator = ColorVisionSimulator::create(VisionProfile::Deuteranomaly);

// Simulate how a single color looks
$simulated = $simulator->simulate($color);

// Check if two colors remain distinguishable under this profile
$ok = $simulator->areDistinguishable($colorA, $colorB); // threshold: 0.06

// Simulate an entire set of colors at once
$simulated = $simulator->simulateAll($palette->all());
```

Supported profiles: `Protanopia`, `Deuteranopia`, `Tritanopia`, `Protanomaly`,
`Deuteranomaly` (most common, affecting ~5% of males), `Tritanomaly`,
`Monochromacy`.

-> [Vision docs](docs/reference/vision.md)

## CSS

PHPColor parses and resolves the full CSS Color Level 4/5 expression layer.
These features are useful for design token pipelines, server-side theme
processing, and any context where colors are expressed as CSS values rather
than raw channels.

### color-mix()

Resolves a `color-mix()` expression to a concrete color, using the interpolation
space declared in the function.

```php
use PhpColor\Color\Css\CssColor;
use PhpColor\Color\Css\CssContext;

$expr     = CssColor::parse('color-mix(in oklab, #ff0000 30%, #0000ff)');
$resolved = $expr->resolve(new CssContext());
```

### light-dark()

Produces a theme-aware color that resolves differently depending on the
context's
color scheme flag.

```php
$adaptive = CssColor::lightDark(
    Color::parse('#1a1a1a'), // light theme value
    Color::parse('#f5f5f5'), // dark theme value
);

$resolved = $adaptive->resolve(new CssContext(scheme: 'dark'));
```

### Custom properties

Wraps a color with a CSS custom property reference and an optional fallback.

```php
$withVar = CssColor::var('--brand-color', Color::parse('#3b82f6'));
```

### Relative color syntax

Allows deriving a new color from an existing one using CSS channel expressions,
following the CSS Color 5 relative color syntax.

```php
$relative = CssColor::relative('oklch', $origin, ['l' => 'calc(l + 0.1)'], null);
```

-> [CSS colors docs](docs/reference/css-colors.md) · [Relative colors docs](docs/guides/relative-colors.md)

## Color Spaces

| Space       | Class             | Description                              |
|-------------|-------------------|------------------------------------------|
| sRGB        | `SrgbColor`       | Standard RGB (0-1 channels)              |
| Oklab       | `OklabColor`      | Perceptually uniform, ideal for mixing   |
| Oklch       | `OklchColor`      | Cylindrical Oklab, intuitive hue control |
| Lab         | `LabColor`        | CIELAB (D50 illuminant)                  |
| LCH         | `LchColor`        | Cylindrical Lab                          |
| HWB         | `HwbColor`        | Hue, Whiteness, Blackness (CSS Color 4)  |
| CMYK        | `CmykColor`       | Subtractive (print)                      |
| Display P3  | `DisplayP3Color`  | Wide-gamut (Apple displays)              |
| Adobe RGB   | `A98RgbColor`     | Adobe RGB 1998                           |
| ProPhoto    | `ProPhotoColor`   | Wide-gamut professional photography      |
| Rec. 2020   | `Rec2020Color`    | HDR and broadcast                        |
| CIE XYZ     | `XyzColor`        | Device-independent reference space       |
| Linear sRGB | `LinearSrgbColor` | sRGB without gamma encoding              |

-> [Color spaces docs](docs/core-concepts/color-spaces.md)

## Contributing

Contributions are welcome. Please open an issue before submitting a pull request
for significant changes.

```bash
git clone https://github.com/phpcolor/phpcolor.git
cd phpcolor
composer install

composer test  # PHPUnit
composer sa    # PHPStan static analysis
composer cs    # PHP-CS-Fixer
```

## License

[PHPColor](https://phpcolor.dev) is released under the [MIT license](LICENSE).

<img src="colors.svg" alt="Colors" />
