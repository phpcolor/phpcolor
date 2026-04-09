# Gradient

![Gradient Illustration](../images/gradient.svg)

Create smooth transitions between colors. PHPColor supports linear, radial, and conic gradients with perceptually uniform interpolation.

## Usage

```php
use PhpColor\Color\Gradient\Gradient;

// Simple linear gradient
$linear = Gradient::linear(180, 'red', 'blue');

// Radial gradient with circle shape
$radial = Gradient::radial()->circle()->from('white')->to('black');

// Conic (angular) gradient
$conic = Gradient::conic(0, '#f00', '#0f0', '#00f', '#f00');
```

## Advanced

### Perceptual Interpolation
By default, PHPColor interpolates colors in **Oklab** space. This avoids the "gray dead zone" often seen in standard CSS RGB gradients, ensuring that intermediate colors maintain consistent perceived brightness and saturation.

```php
// Force standard RGB interpolation if needed
$gradient = Gradient::linear()->in('srgb')->from('red')->to('blue');
```

### Complex Stop Management
Use the builder API for precise control over color stop positions.

```php
$gradient = Gradient::linear()
    ->stop('red', 0.0)
    ->stop('yellow', 0.2)
    ->stop('blue', 1.0)
    ->build();
```

## Examples

### Generating Smooth CSS Gradients
Generate CSS code that uses wide-gamut colors if supported.

```php
// Returns: "linear-gradient(180deg, rgb(255 0 0) 0%, rgb(0 0 255) 100%)"
echo $linear->toCss();

// Returns: "linear-gradient(180deg, color(display-p3 1 0 0) 0%, ...)"
echo $linear->toCss('display-p3');
```

### Sampling a Gradient for Charts
Extract specific color points from a gradient to use in a data visualization.

```php
$colors = [];
for ($i = 0; $i <= 1.0; $i += 0.1) {
    $colors[] = $gradient->interpolate($i);
}
```

## Navigation
- **Next**: [Palette](palette.md)
- **Previous**: [Mixing](mixing.md)
- **Home**: [Documentation Index](../index.md)
