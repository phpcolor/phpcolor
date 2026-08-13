# CHANGELOG

## [1.1.0](https://github.com/phpcolor/phpcolor/releases/tag/v1.1.0) - 2026-08-12

### Added

* Added `ScaleFixer` to analyze and repair irregular lightness progression in color palettes
* Added `toOklch()` to the color interface
* Added support for selecting the distance metric used by `ColorPaletteInterface::closest()`
* Added `hwb` and `srgb-linear` support to relative color syntax

### Changed

* Renamed `SrgbColor::toHsl()` to `getHslChannels()`; the previous method remains available as a deprecated alias
* Extended `ColorExceptionInterface` from `Throwable`
* Increased CSS output precision to six significant digits

### Fixed

* Updated APCA contrast calculations to the canonical 0.0.98G algorithm
* Verified gamut mapping results after channel quantization
* Corrected Delta E 94 hue-term calculation
* Corrected Lab and LCH conversions to use the D50 reference white
* Preserved alpha values in HWB conversions and CSS variable output
* Produced valid gradient CSS and preserved explicit stop positions
* Corrected gradient interpolation for premultiplied alpha and encoded sRGB channels
* Preserved palette types and reindexed palette keys after filtering and slicing
* Rejected unknown channels passed to `withChannels()`
* Preserved non-sRGB notation in CSS variable output
* Corrected XYZ channel names in relative color syntax
* Corrected `ScaleFixer` options that disable hue or chroma preservation

### Documentation

* Documented wide-gamut channel clamping
* Replaced the universal color-distance threshold table with metric-specific guidance
* Added missing documentation to public methods and enum cases

## [1.0.0](https://github.com/phpcolor/phpcolor/releases/tag/v1.0.0) - 2026-04-09

* Initial release
