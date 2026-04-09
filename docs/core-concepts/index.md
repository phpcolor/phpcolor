# Core Concepts

PHPColor is built on a few core principles that ensure accuracy, flexibility, and ease of use. Understanding these concepts will help you get the most out of the library.

## Perceptual Uniformity

Most legacy color manipulation libraries work in **sRGB**, which is not perceptually uniform. Changes in channel values do not correspond linearly to changes in how we perceive color. For example, a 10% increase in lightness in sRGB might appear much brighter for some hues than others.

PHPColor defaults to **Oklab** and **Oklch** for all manipulations. These spaces are designed to be perceptually uniform, meaning equal mathematical changes result in equal perceived changes. This makes gradients smoother and color adjustments more predictable.

## Immutability

Every color object in PHPColor is an **immutable value object**. When you call a method like `lighten()` or `rotateHue()`, the original object is not modified. Instead, a new color object is returned.

This approach prevents side effects and makes it easy to chain operations.

## Multi-Space Architecture

PHPColor supports **15 different color spaces**, including wide-gamut spaces like Display P3 and Rec. 2020. The library uses a sophisticated conversion pipeline centered around the **CIE XYZ** space, ensuring that conversions between any two spaces are mathematically precise.

## CSS Standard Compliance

The library is designed to be fully compliant with **CSS Color Module Level 4 and 5**. This means you can parse, resolve, and generate color strings that use the latest web standards, including:

- `oklab()` and `oklch()` functions.
- `color()` function for specific color spaces.
- `color-mix()` and `light-dark()` functions.
- Relative color syntax (`from color`).

---

## Dive Deeper

- [Immutability](immutability.md): Why value objects matter.
- [Conversion Pipeline](conversion-pipeline.md): How we handle 15 color spaces accurately.