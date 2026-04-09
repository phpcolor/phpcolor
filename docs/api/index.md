# API Reference

PHPColor provides a rich color manipulation API. The main entry point is the `PhpColor\Color\Color` facade class.

## Core Classes

- [ColorInterface](color-interface.md): The interface implemented by all color objects.
- [Color Spaces](color-spaces/index.md): All 15 supported color space classes.

## Namespaces

| Namespace | Purpose |
| :--- | :--- |
| `PhpColor\Color\Space` | Individual color space classes (`SrgbColor`, `OklchColor`, etc.) |
| `PhpColor\Color\Parser` | Parses CSS color strings into color objects |
| `PhpColor\Color\Css` | CSS Color Level 4/5 expressions (`color-mix()`, `light-dark()`, relative syntax) |
| `PhpColor\Color\Palette` | Palette generation and transformation |
| `PhpColor\Color\Gradient` | Gradient builder |
| `PhpColor\Color\Contrast` | WCAG 2.x and APCA contrast calculations |