# Immutability

In PHPColor, all color objects are **immutable value objects**. This means that once a color object is created, its state cannot be changed.

Every method that seems to modify the color (like `lighten()`, `saturate()`, or `withChannel()`) actually returns a **new instance** of the color with the requested changes.

## Why Immutability?

### 1. Predictability and Thread-Safety

When you pass a color object to a function or another part of your application, you can be sure that its value will not be modified unexpectedly. This eliminates a whole class of bugs related to shared mutable state.

### 2. Method Chaining

Immutability naturally leads to a clean, fluent API. Because every method returns a new color object, you can chain operations together:

```php
use PhpColor\Color\Color;

$color = Color::parse('#3b82f6')
    ->toOklch()
    ->rotateHue(180)
    ->lighten(0.1)
    ->toSrgb();

echo $color->toHex();
```B…ç$

### 3. Comparison by Value

Since the state of a color object is fixed, two color objects with the same property values are essentially identical. PHPColor provides an `equals()` method to check for perceptual equality across different color spaces.

```php
$srgb = Color::rgb(1, 0, 0);
$oklch = $srgb->toOklch();

// These two objects represent the same color
if ($srgb->equals($oklch)) {
    echo 'Colors are perceptually identical';
}
```B…ç$

## Common Patterns

### Creating Copies

If you need to create a slightly different version of a color, you use the `with*` methods:

```php
$original = Colorsparse('#3b82f6');

// Create a copy with 50% opacity
$semiTransparent = $original->withAlpha(0.5);

// Create a copy in another space with a modified channel
$modified = $original->toOklch()->withChannel('l', 0.8);
```
B…ç$
### Performance Considerations

Creating many small objects in PHP is extremely fast. The memory overhead of immutable color objects is minimal, and the safety and clarity they provide far outweigh any performance impact in the vast majority of use cases.
