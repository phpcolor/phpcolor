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

namespace PhpColor\Color\Css;

use PhpColor\Color\A98RgbColor;
use PhpColor\Color\ColorInterface;
use PhpColor\Color\DisplayP3Color;
use PhpColor\Color\Exception\ColorExceptionInterface;
use PhpColor\Color\Exception\ParseException;
use PhpColor\Color\HwbColor;
use PhpColor\Color\LabColor;
use PhpColor\Color\LchColor;
use PhpColor\Color\LinearSrgbColor;
use PhpColor\Color\OklabColor;
use PhpColor\Color\OklchColor;
use PhpColor\Color\Parser\ParserUtils;
use PhpColor\Color\ProPhotoColor;
use PhpColor\Color\Rec2020Color;
use PhpColor\Color\Space\ColorSpaces;
use PhpColor\Color\SrgbColor;
use PhpColor\Color\XyzColor;

/**
 * Core parser for CSS color functions and notations.
 *
 * Supports modern CSS Color Level 4 features including functional notations,
 * relative color syntax, and wide-gamut color spaces.
 */
final class CssColorParser
{
    /**
     * Normalize a color space name.
     */
    public static function normalizeSpaceName(string $space): string
    {
        return ParserUtils::normalizeSpaceName($space);
    }

    /**
     * Parse a CSS color string into a ColorInterface instance.
     *
     * @throws ParseException
     */
    public static function parse(string $input): ColorInterface
    {
        $s = trim($input);
        if ('' === $s) {
            throw new ParseException('Empty color string.');
        }

        $c0 = strtolower($s[0]);
        if ('#' === $c0) {
            return SrgbColor::parseHex($s);
        }

        $lower = strtolower($s);

        $inner = self::between($s, '(', ')');
        if (null !== $inner && str_contains(strtolower($inner), 'from')) {
            return self::parseRelative($s);
        }

        if ((str_starts_with($lower, 'rgb(')
                || str_starts_with($lower, 'rgba(')
                || str_starts_with($lower, 'hsl(')
                || str_starts_with($lower, 'hsla(')
                || str_starts_with($lower, 'oklab(')
                || str_starts_with($lower, 'oklch(')
                || str_starts_with($lower, 'lab(')
                || str_starts_with($lower, 'lch(')
                || str_starts_with($lower, 'color('))
            && str_contains($lower, 'from')
            && null === $inner) {
            throw new ParseException(\sprintf('Invalid color() syntax "%s".', $s));
        }

        $parsers = [
            'rgb(' => [SrgbColor::class, 'parse'],
            'rgba(' => [SrgbColor::class, 'parse'],
            'hsl(' => [SrgbColor::class, 'parseHsl'],
            'hsla(' => [SrgbColor::class, 'parseHsl'],
            'oklab(' => [OklabColor::class, 'parse'],
            'oklch(' => [OklchColor::class, 'parse'],
            'lab(' => [LabColor::class, 'parse'],
            'lch(' => [LchColor::class, 'parse'],
            'color(' => [self::class, 'parseColorFunction'],
        ];
        foreach ($parsers as $prefix => $callable) {
            if (str_starts_with($lower, $prefix)) {
                [$class, $method] = $callable;
                /** @var ColorInterface $out */
                $out = $class::$method($s);

                return $out;
            }
        }

        return CssColors::parse($s);
    }

    /**
     * Parse a modern CSS color() function.
     */
    public static function parseColorFunction(string $s): ColorInterface
    {
        try {
            $inner = self::between($s, '(', ')');
            if (null === $inner || '' === trim($inner)) {
                throw new ParseException(\sprintf('Cannot parse color "%s".', $s));
            }

            $parts = preg_split('/\s+/', trim($inner), 2) ?: [];
            $space = $parts[0] ?? '';
            $rest = $parts[1] ?? '';

            $restParts = explode('/', $rest, 2);
            $rgbPart = trim($restParts[0] ?? '');
            $alphaPart = isset($restParts[1]) ? trim($restParts[1]) : null;

            $rgb = preg_split('/\s+/', $rgbPart) ?: [];
            if (3 !== \count($rgb)) {
                throw new ParseException(\sprintf('Cannot parse color "%s".', $s));
            }

            $channels = [];
            foreach (\array_slice($rgb, 0, 3) as $token) {
                $channels[] = self::evaluateChannelExpression($token, [], 1.0);
            }

            if (null !== $alphaPart) {
                if (preg_match('/^\d+(?:\.\d+)?%?$/', $alphaPart)) {
                    $a = self::parseUnitOrPercent($alphaPart);
                } else {
                    $a = self::evaluateChannelExpression($alphaPart, [], 1.0);
                }
            } else {
                $a = 1.0;
            }

            $canonical = ColorSpaces::normalize($space);
            /** @var callable(array<string,float>, float): ColorInterface|null $builder */
            $builder = CssColorSpaces::builderFor($canonical);
            if (null === $builder) {
                throw new ParseException(\sprintf('Unsupported color() space "%s".', $space));
            }

            $channelAssoc = ['r' => $channels[0], 'g' => $channels[1], 'b' => $channels[2]];

            return $builder($channelAssoc, $a);
        } catch (ColorExceptionInterface $e) {
            throw new ParseException($e->getMessage(), previous: $e);
        }
    }

    /**
     * Extract content between parentheses.
     */
    private static function between(string $haystack, string $start, string $end): ?string
    {
        return ParserUtils::between($haystack, $start, $end);
    }

    /**
     * Evaluate a CSS calc() expression within a color function context.
     *
     * @param array<string, float> $availableChannels
     */
    private static function evaluateCalcExpression(
        string $expr,
        array $availableChannels,
        float $originAlpha,
    ): float {
        static $evaluator = null;
        if (!$evaluator instanceof CalcExpressionEvaluator) {
            $evaluator = new CalcExpressionEvaluator();
        }

        $variables = $availableChannels;
        $variables['alpha'] = $originAlpha;

        return $evaluator->evaluate($expr, $variables);
    }

    /**
     * Evaluate a color channel expression (numeric, percentage, or calc()).
     *
     * @param array<string, float> $availableChannels
     */
    private static function evaluateChannelExpression(
        string $expr,
        array $availableChannels,
        float $originAlpha,
    ): float {
        $expr = trim($expr);

        if (isset($availableChannels[$expr])) {
            return $availableChannels[$expr];
        }

        if ('alpha' === $expr) {
            return $originAlpha;
        }

        if (str_ends_with($expr, '%')) {
            return ((float) substr($expr, 0, -1)) / 100.0;
        }

        if (is_numeric($expr)) {
            return (float) $expr;
        }

        if (preg_match('/^calc\((.+)\)$/i', $expr, $matches)) {
            try {
                return self::evaluateCalcExpression($matches[1], $availableChannels, $originAlpha);
            } catch (\Exception) {
                throw new ParseException(\sprintf('Invalid calc() expression: "%s"', $expr));
            }
        }

        throw new ParseException(\sprintf('Invalid channel expression: "%s"', $expr));
    }

    /**
     * Extract channel values from a source color for use in relative color syntax.
     *
     * @return array{array<string, float>, float}
     */
    private static function extractChannelsForSpace(ColorInterface $origin, string $targetFunction): array
    {
        $targetSpace = match ($targetFunction) {
            'hsl' => 'hsl',
            'oklab' => 'oklab',
            'oklch' => 'oklch',
            'lab' => 'lab',
            'lch' => 'lch',
            default => $targetFunction,
        };

        if ('hsl' === $targetSpace) {
            $srgb = $origin->toSrgb();
            $hsl = $srgb->getHslChannels();

            return [
                ['h' => $hsl['h'], 's' => $hsl['s'], 'l' => $hsl['l']],
                $srgb->getAlpha(),
            ];
        }

        $converted = $origin->to($targetSpace);

        /** @var array<string, float> $channels */
        $channels = $converted->getChannels();
        $alpha = $converted->getAlpha();

        return [$channels, $alpha];
    }

    /**
     * Parse the channel and alpha specifications part of a relative color function.
     *
     * @param array<string, float> $availableChannels
     *
     * @return array{channels: array<string, float>, alpha: float}
     */
    private static function parseChannelSpecifications(
        string $channelSpec,
        array $availableChannels,
        float $originAlpha,
        string $functionName,
    ): array {
        $channelSpec = trim($channelSpec);

        [$channelPart, $alphaPart] = self::splitBySlashOutsideParens($channelSpec);

        $outputAlpha = null !== $alphaPart
            ? self::evaluateChannelExpression($alphaPart, $availableChannels, $originAlpha)
            : $originAlpha;

        $channelExpressions = self::splitChannelExpressions($channelPart);

        $expectedChannelNames = ColorSpaces::expectedChannels($functionName);

        $expectedCount = \count($expectedChannelNames);
        if (\count($channelExpressions) !== $expectedCount) {
            throw new ParseException(\sprintf('Expected %d channel values for %s(), got %d', $expectedCount, $functionName, \count($channelExpressions)));
        }

        $outputChannels = [];
        for ($i = 0; $i < $expectedCount; ++$i) {
            $channelName = $expectedChannelNames[$i];
            $expression = $channelExpressions[$i];
            $outputChannels[$channelName] = self::evaluateChannelExpression(
                $expression,
                $availableChannels,
                $originAlpha
            );
        }

        return ['channels' => $outputChannels, 'alpha' => $outputAlpha];
    }

    /**
     * Parse CSS Relative Color Syntax.
     */
    private static function parseRelative(string $s): ColorInterface
    {
        $inner = self::between($s, '(', ')');
        if (null === $inner) {
            throw new ParseException(\sprintf('Invalid color() syntax "%s".', $s));
        }

        $trimmed = trim($inner);
        if (!str_starts_with(strtolower($trimmed), 'from')) {
            throw new ParseException('No relative "from" clause');
        }
        if (!preg_match('/^from\s+(.+)$/i', $trimmed, $matches)) {
            throw new ParseException('No relative "from" clause');
        }

        [$originAndTarget, $channelsAndAlpha] = self::splitColorFunctionRelative(trim($matches[1]));
        [$originStr, $targetSpaceRaw] = $originAndTarget;

        $origin = self::parse($originStr);
        $targetSpace = ColorSpaces::normalize($targetSpaceRaw ?: strtolower(trim(explode('(', $s)[0] ?? '')));

        [$targetChannels, $originAlpha] = self::extractChannelsForSpace($origin, $targetSpace);
        $outputChannels = self::parseChannelSpecifications($channelsAndAlpha, $targetChannels, $originAlpha, $targetSpace);

        return match ($targetSpace) {
            'srgb', 'rgb' => SrgbColor::fromChannels($outputChannels['channels'], $outputChannels['alpha']),
            'hsl' => SrgbColor::fromHsl([
                'h' => $outputChannels['channels']['h'] ?? 0.0,
                's' => $outputChannels['channels']['s'] ?? 0.0,
                'l' => $outputChannels['channels']['l'] ?? 0.0,
            ], $outputChannels['alpha']),
            'hwb' => HwbColor::fromChannels($outputChannels['channels'], $outputChannels['alpha']),
            'oklab' => OklabColor::fromChannels($outputChannels['channels'], $outputChannels['alpha']),
            'oklch' => OklchColor::fromChannels($outputChannels['channels'], $outputChannels['alpha']),
            'lab' => LabColor::fromChannels($outputChannels['channels'], $outputChannels['alpha']),
            'lch' => LchColor::fromChannels($outputChannels['channels'], $outputChannels['alpha']),
            'srgb-linear' => LinearSrgbColor::fromChannels($outputChannels['channels'], $outputChannels['alpha']),
            'display-p3' => DisplayP3Color::fromChannels($outputChannels['channels'], $outputChannels['alpha']),
            'xyz', 'xyz-d65' => XyzColor::fromChannels($outputChannels['channels'], $outputChannels['alpha']),
            'rec2020', 'rec-2020', 'bt2020', 'bt-2020' => Rec2020Color::fromChannels($outputChannels['channels'], $outputChannels['alpha']),
            'prophoto-rgb', 'prophoto', 'romm-rgb' => ProPhotoColor::fromChannels($outputChannels['channels'], $outputChannels['alpha']),
            'a98-rgb', 'a98', 'adobe-rgb' => A98RgbColor::fromChannels($outputChannels['channels'], $outputChannels['alpha']),
            default => throw new ParseException(\sprintf('Unsupported color space "%s".', $targetSpace)),
        };
    }

    /**
     * Parse a numeric value or percentage into a float in range [0, 1].
     */
    private static function parseUnitOrPercent(string $token): float
    {
        return ParserUtils::parseUnitOrPercent($token);
    }

    /**
     * Split a string by slash only if it is outside of parentheses.
     *
     * @return array{string, string|null}
     */
    private static function splitBySlashOutsideParens(string $str): array
    {
        return ParserUtils::splitBySlashOutsideParens($str);
    }

    /**
     * Split channel expressions by whitespace, respecting parentheses.
     *
     * @return array<int, string>
     */
    private static function splitChannelExpressions(string $channelPart): array
    {
        return ParserUtils::splitChannelExpressions($channelPart);
    }

    /**
     * Split the components of a relative color function into origin and target space.
     *
     * @return array{array{string, string}, string}
     */
    private static function splitColorFunctionRelative(string $rest): array
    {
        $colorSpaces = [
            'srgb', 'srgb-linear', 'hsl', 'oklab', 'oklch', 'lab', 'lch', 'display-p3', 'displayp3',
            'xyz-d65', 'xyz', 'rec2020', 'rec-2020', 'bt2020', 'bt-2020',
            'prophoto-rgb', 'prophoto', 'romm-rgb', 'a98-rgb', 'a98', 'adobe-rgb',
        ];

        if (preg_match('/^(rgb|rgba|hsl|hsla|oklab|oklch|lab|lch|color)\s*\(/', $rest, $funcMatch)) {
            $depth = 0;
            $inFunc = false;
            $len = \strlen($rest);
            for ($i = 0; $i < $len; ++$i) {
                if ('(' === $rest[$i]) {
                    ++$depth;
                    $inFunc = true;
                } elseif (')' === $rest[$i]) {
                    --$depth;
                    if (0 === $depth && $inFunc) {
                        $originStr = substr($rest, 0, $i + 1);
                        $afterOrigin = trim(substr($rest, $i + 1));

                        $tokens = preg_split('/\s+/', $afterOrigin, 2) ?: [];

                        $first = ColorSpaces::normalize($tokens[0] ?? '');
                        if (\in_array($first, $colorSpaces, true)) {
                            $targetSpace = $first;
                            $channels = isset($tokens[1]) ? trim($tokens[1]) : '';
                        } else {
                            $targetSpace = '';
                            $channels = $afterOrigin;
                        }

                        return [[$originStr, $targetSpace], $channels];
                    }
                }
            }
        }

        $tokens = preg_split('/\s+/', $rest) ?: [];

        if (\count($tokens) > 0 && str_starts_with($tokens[0], '#')) {
            $originStr = $tokens[0];
            $maybe = isset($tokens[1]) ? ColorSpaces::normalize($tokens[1]) : '';
            if ('' !== $maybe && \in_array($maybe, $colorSpaces, true)) {
                $targetSpace = $maybe;
                $channelTokens = \array_slice($tokens, 2);
            } else {
                $targetSpace = '';
                $channelTokens = \array_slice($tokens, 1);
            }

            return [[$originStr, $targetSpace], implode(' ', $channelTokens)];
        }

        if (\count($tokens) > 0) {
            $hex = CssColors::tryHex($tokens[0]);
            if (null !== $hex) {
                $originStr = $tokens[0];
                $maybe = isset($tokens[1]) ? ColorSpaces::normalize($tokens[1]) : '';
                if ('' !== $maybe && \in_array($maybe, $colorSpaces, true)) {
                    $targetSpace = $maybe;
                    $channelTokens = \array_slice($tokens, 2);
                } else {
                    $targetSpace = '';
                    $channelTokens = \array_slice($tokens, 1);
                }

                return [[$originStr, $targetSpace], implode(' ', $channelTokens)];
            }
        }

        if (\count($tokens) >= 4 && \in_array(ColorSpaces::normalize($tokens[0]), $colorSpaces, true)) {
            $originSpace = ColorSpaces::normalize($tokens[0]);
            $originValues = \array_slice($tokens, 1, 3);

            $tokenCount = \count($tokens);
            for ($i = 4; $i < $tokenCount; ++$i) {
                $token = ColorSpaces::normalize($tokens[$i]);
                if (\in_array($token, $colorSpaces, true)) {
                    $targetSpace = $token;
                    $channelTokens = \array_slice($tokens, $i + 1);

                    $originStr = \sprintf('color(%s %s)', $originSpace, implode(' ', $originValues));

                    return [[$originStr, $targetSpace], implode(' ', $channelTokens)];
                }
            }
        }

        throw new ParseException(\sprintf('Could not parse color() relative color syntax: "%s"', $rest));
    }
}
