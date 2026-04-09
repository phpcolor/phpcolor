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

use PhpColor\Color\Exception\ParseException;

/**
 * Evaluator for CSS calc() expressions within color functions.
 *
 * Supports basic arithmetic operations (+, -, *, /), parentheses,
 * percentage values, and variable substitution.
 */
final class CalcExpressionEvaluator
{
    /**
     * Evaluate a calc() expression and return the numeric result.
     *
     * @param array<string, float> $variables Variables to substitute in the expression
     */
    public function evaluate(string $expression, array $variables = []): float
    {
        $expression = $this->prepareExpression($expression, $variables);
        $expression = $this->normalizeExpression($expression);
        $this->validateExpression($expression);

        $result = $this->parseExpression($expression);

        if ('' !== $expression) {
            throw new ParseException(\sprintf('Unexpected token in calc() expression: "%s"', $expression[0]));
        }

        return $result;
    }

    /**
     * Normalize the expression by removing whitespace.
     */
    private function normalizeExpression(string $expression): string
    {
        return (string) preg_replace('/\s+/', '', $expression);
    }

    /**
     * Parse an addition/subtraction expression.
     */
    private function parseExpression(string &$expression): float
    {
        $result = $this->parseTerm($expression);

        while ('' !== $expression && ('+' === $expression[0] || '-' === $expression[0])) {
            $operator = $expression[0];
            $expression = substr($expression, 1);
            $right = $this->parseTerm($expression);

            $result = '+' === $operator ? $result + $right : $result - $right;
        }

        return $result;
    }

    /**
     * Parse a single numeric factor, signed value, or parenthesized expression.
     */
    private function parseFactor(string &$expression): float
    {
        if ('' !== $expression && ('-' === $expression[0] || '+' === $expression[0])) {
            $sign = $expression[0];
            $expression = substr($expression, 1);
            $value = $this->parseFactor($expression);

            return '-' === $sign ? -$value : $value;
        }

        if ('' !== $expression && '(' === $expression[0]) {
            $expression = substr($expression, 1);
            $result = $this->parseExpression($expression);

            if ('' === $expression || ')' !== $expression[0]) {
                throw new ParseException('Mismatched parentheses in calc() expression');
            }

            $expression = substr($expression, 1);

            return $result;
        }

        if (preg_match('/^(\d+(?:\.\d+)?)/', $expression, $matches)) {
            $expression = substr($expression, \strlen($matches[0]));

            return (float) $matches[0];
        }

        throw new ParseException(\sprintf('Unexpected token in calc() expression: "%s"', '' !== $expression ? $expression[0] : 'EOF'));
    }

    /**
     * Parse a multiplication/division term.
     */
    private function parseTerm(string &$expression): float
    {
        $result = $this->parseFactor($expression);

        while ('' !== $expression && ('*' === $expression[0] || '/' === $expression[0])) {
            $operator = $expression[0];
            $expression = substr($expression, 1);
            $right = $this->parseFactor($expression);

            if ('*' === $operator) {
                $result *= $right;
            } else {
                if (0.0 === $right) {
                    throw new ParseException('Division by zero in calc() expression');
                }
                $result /= $right;
            }
        }

        return $result;
    }

    /**
     * Prepare the expression by substituting variables and converting percentages.
     *
     * @param array<string, float> $variables
     */
    private function prepareExpression(string $expression, array $variables): string
    {
        $expression = trim($expression);

        $variableNames = array_keys($variables);
        usort($variableNames, static fn ($a, $b): int => \strlen($b) <=> \strlen($a));

        foreach ($variableNames as $name) {
            $pattern = '/\b'.preg_quote($name, '/').'\b/';
            $expression = (string) preg_replace($pattern, (string) $variables[$name], $expression);
        }

        return (string) preg_replace_callback(
            '/(\d+(?:\.\d+)?)\s*%/',
            static fn ($matches): string => (string) ((float) $matches[1] / 100.0),
            $expression
        );
    }

    /**
     * Validate that the expression only contains allowed characters.
     */
    private function validateExpression(string $expression): void
    {
        if ('' === $expression) {
            throw new ParseException('Empty calc() expression');
        }

        if (!preg_match('/^[0-9+\-*\/.()]+$/', $expression)) {
            throw new ParseException(\sprintf('Invalid characters in calc() expression: "%s"', $expression));
        }
    }
}
