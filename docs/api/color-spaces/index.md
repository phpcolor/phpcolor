# Color Spaces

PHPColor supports 15 different color spaces, each represented by a typed immutable value object or available via CSS parsing.

## Supported Spaces

### 1. [sRGB](srgb.md)
- **Class**: `SrgbColor`
- **Description**: The standard RGB color space used on the web.
- **Channels**: `r`, `g`, `b` (0–1 range).

### 2. [Oklab](oklab.md)
- **Class**: `OklabColor`
- **Description**: A perceptually uniform color space, ideal for mixing and interpolation.
- **Channels**: `l` (0–1), `a` (−0.5–0.5), `b` (−0.5–0.5).

### 3. [Oklch](oklch.md)
- **Class**: `OklchColor`
- **Description**: The cylindrical variant of Oklab, providing intuitive control over hue.
- **Channels**: `l` (0–1), `c` (0–0.4), `h` (0–360).

### 4. [Lab](lab.md)
- **Class**: `LabColor`
- **Description**: CIELAB color space (D50 reference illuminant).
- **Channels**: `l` (0–100), `a` (−128–127), `b` (−128–127).

### 5. [LCH](lch.md)
- **Class**: `LchColor`
- **Description**: The cylindrical form of CIELAB.
- **Channels**: `l` (0–100), `c` (0–150), `h` (0–360).

### 6. [HWB](hwb.md)
- **Class**: `HwbColor`
- **Description**: Hue, Whiteness, Blackness, as defined in CSS Color Level 4.
- **Channels**: `h` (0–360), `w` (0–1), `b` (0–1).

### 7. [CMYK](cmyk.md)
- **Class**: `CmykColor`
- **Description**: Subtractive color model used in printing.
- **Channels**: `c`, `m`, `y`, `k` (0–1 range).

### 8. [Display P3](display-p3.md)
- **Class**: `DisplayP3Color`
- **Description**: A wide-gamut color space used on modern Apple and other displays.
- **Channels**: `r`, `g`, `b` (0–1 range, wider than sRGB).

### 9. [Adobe RGB (1998)](a98-rgb.md)
- **Class**: `A98RgbColor`
- **Description**: Covers roughly 50% of visible colors; used in photography and print workflows.
- **Channels**: `r`, `g`, `b` (0–1 range).

### 10. [ProPhoto RGB](prophoto-rgb.md)
- **Class**: `ProPhotoColor`
- **Description**: Extremely wide gamut (D50); used in high-end photography.
- **Channels**: `r`, `g`, `b` (0–1 range).

### 11. [Rec. 2020](rec2020.md)
- **Class**: `Rec2020Color`
- **Description**: Ultra-wide gamut standard for HDR video and broadcast.
- **Channels**: `r`, `g`, `b` (0–1 range).

### 12. [CIE XYZ](xyz.md)
- **Class**: `XyzColor`
- **Description**: The device-independent reference space used internally for all conversions.
- **Channels**: `x`, `y`, `z`.

### 13. [Linear sRGB](linear-srgb.md)
- **Class**: `LinearSrgbColor`
- **Description**: sRGB without gamma encoding; used internally for accurate blending calculations.
- **Channels**: `r`, `g`, `b` (0–1 range).

### 14. HSL
- **Description**: Available via CSS parsing and export (`hsl()` / `hsla()`). PHPColor converts to/from sRGB internally; no dedicated `HslColor` class is exposed.

### 15. Named & Hex
- **Description**: CSS named colors and hex notation (`#rrggbb`, `#rgb`) are parsed and normalized to sRGB.
