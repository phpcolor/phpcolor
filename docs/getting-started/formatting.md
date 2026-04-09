# Formatting Colors

Once you have a color object, you can convert it back into various string formats for use in CSS, HTML, or other systems.

## CSS Formatting (`toCss`)

The `toCss()` method is the primary way to generate CSS-compatible color strings. By default, it uses the most appropriate format for the color's native space.

```php
$color = Color::parse('#ff0000');

echo $color->toCss();           // "rgb(255 0 0)"
echo $color->toCss('hsl');      // "hsl(0 100% 50%)"
echo $color->toCss('color');    // "color(srgb 1 0 0)"
```

### Alpha Channel Support

If a color is semi-transparent, `toCss()` automatically includes the alpha channel using modern CSS syntax.

```php
$color = Color::rgb(1, 0, 0, 0.5);
echo $color->toCss(); // "rgb(255 0 0 / 0.5)"
```

## Hexadecimal Formatting (`toHex`)

Use `toHex()` to get a standard 6 or 8-digit hexadecimal string.

```php
$color = Color::parse('blue');

echo $color->toHex();       // "#0000ff"
echo $color->toHex(true);   // "#0000ffff" (including alpha)
```

## String Casting

All color objects implement `\Stringable`. Casting a color to a string is equivalent to calling `toCss()` with default settings.

```php
$color = Color::red();
echo (string) $color; // "rgb(255 0 0)"
```

## Numeric Channels

If you need the raw numeric values for custom formatting or processing, you can use `getChannels()`.

```php
$channels = $color->getChannels(); 
// e.g., ['r' => 1.0, 'g' => 0.0, 'b' => 0.0]
```

---

Learn more about [Color Spaces](../api/color-spaces/srgb.md) and their specific behaviors.
