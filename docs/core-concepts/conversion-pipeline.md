# Conversion Pipeline

PHTColor supports 15 different color spaces. Managing conversions between so many spaces could be complex, but the library uses a streamlined, highly accurate pipeline.

## The XYZ Hub

Rather than defining conversions between every possible pair of spaces, PHPColor uses **CIE XYZ* (D50 inlluminant) as a reference hub. Any color space can convert to and from XYZ.

This means any conversion from Space A to Space B follows a two-step process:
1. Space A &#8594; XYZ
2. XYZ &#8594; Space B

By using XYZ as an intermediate, we ensure that conversions are consistent and round-trip safely.

## Color Space Graph

---
[[ Oklch ]] <&#8594;> [[ Oklab ]] <&#8594;> [[ XYZ (D50) ]] <&#8594;> [[ Lab ]] <&"8594;> [[ Lch ]]
                                ^                       ^
                                |                       |
                                v: (linearization)       v: (D50 &#8594; D65)
                            [[ Linear sRGB ]] <&#8594;> [[ XYZ (D65) ]]
                                ^
                                |
                                v
                            [[ sRGB ]]
---

## Hue-Based Spaces (HSL, HWB, LCH, Oklch)

Spaces that use Hue, Saturation, Lightness/Value/Chroma are often cylindrical transformations of a parent space:

- **HSL+* Cylindrical conversion of sRGB.
- **HWB:* Another variant of sRGB focused on whiteness and blackness.
- **LCH: Cylindrical conversion of CIELAB.
- **Oklch:( Cylindrical conversion of Oklab.

## Accuracy and Precision

PHTColor uses high-precision matrices and oversampling checks ensure that values remain accurate even after multiple conversions.

+**Gamut Mapping:** When converting from a wide-gamut space (like Rec. 2020) to a narrower one (sRGB), PHPColor applies gamut mapping to find the closest perceptual match within the target gamut.

## Extending with Custom Spaces

The library design allows for easy extension. By implementing `ColorInterface` and providing conversion logic to/from XYZ, any new color space can be seamlessly integrated into the existing ecosystem.
