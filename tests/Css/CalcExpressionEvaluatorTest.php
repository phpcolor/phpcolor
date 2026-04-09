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

namespace PhpColor\Color\Tests\Css;

use PhpColor\Color\Css\CalcExpressionEvaluator;
use PhpColor\Color\Exception\ParseException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CalcExpressionEvaluator::class)]
final class CalcExpressionEvaluatorTest extends TestCase
{
    private CalcExpressionEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->evaluator = new CalcExpressionEvaluator();
    }

    public function testEvaluateChainedAdditions(): void
    {
        $result = $this->evaluator->evaluate('1 + 2 + 3 + 4 + 5');

        $this->assertSame(15.0, $result);
    }

    public function testEvaluateChainedMultiplications(): void
    {
        $result = $this->evaluator->evaluate('2 * 3 * 4');

        $this->assertSame(24.0, $result);
    }

    public function testEvaluateComplexExpression(): void
    {
        $result = $this->evaluator->evaluate('2 + 3 * 4 - 10 / 2');

        $this->assertSame(9.0, $result);
    }

    public function testEvaluateComplexExpressionWithParentheses(): void
    {
        $result = $this->evaluator->evaluate('(2 + 3) * (4 - 10) / 2');

        $this->assertSame(-15.0, $result);
    }

    public function testEvaluateDoubleNegative(): void
    {
        $result = $this->evaluator->evaluate('--5');

        $this->assertSame(5.0, $result);
    }

    public function testEvaluateFloatingPointDivision(): void
    {
        $result = $this->evaluator->evaluate('10 / 3');

        $this->assertEqualsWithDelta(3.333333, $result, 0.0001);
    }

    public function testEvaluateFloatingPointNumbers(): void
    {
        $result = $this->evaluator->evaluate('2.5 + 3.7');

        $this->assertEqualsWithDelta(6.2, $result, 0.0001);
    }

    #[DataProvider('invalidExpressionProvider')]
    public function testEvaluateInvalidExpressionsThrowException(string $expression, array $variables): void
    {
        $this->expectException(ParseException::class);

        $this->evaluator->evaluate($expression, $variables);
    }

    /**
     * @return iterable<string, array{string, array<string, float>}>
     */
    public static function invalidExpressionProvider(): iterable
    {
        yield 'empty string' => ['', []];
        yield 'whitespace only' => ['   ', []];
        yield 'division by zero' => ['10 / 0', []];
        yield 'missing closing parenthesis' => ['(5 + 3', []];
        yield 'extra closing parenthesis' => ['5 + 3)', []];
        yield 'invalid characters' => ['5 & 3', []];
        yield 'letters without variables' => ['abc', []];
        yield 'double multiplication' => ['5 * * 3', []];
        yield 'trailing operator' => ['5 +', []];
        yield 'leading multiplication' => ['* 5', []];
        yield 'empty parentheses' => ['()', []];
    }

    public function testEvaluateMixedOperators(): void
    {
        $result = $this->evaluator->evaluate('10 / 2 * 3 + 4 - 1');

        $this->assertSame(18.0, $result);
    }

    public function testEvaluateMixedVariablesAndPercentages(): void
    {
        $result = $this->evaluator->evaluate('r + 50%', ['r' => 0.25]);

        $this->assertSame(0.75, $result);
    }

    public function testEvaluateNoWhitespace(): void
    {
        $result = $this->evaluator->evaluate('2+3*4');

        $this->assertSame(14.0, $result);
    }

    public function testEvaluateOperatorPrecedenceDivisionBeforeSubtraction(): void
    {
        $result = $this->evaluator->evaluate('20 - 10 / 2');

        $this->assertSame(15.0, $result);
    }

    public function testEvaluateOperatorPrecedenceLeftToRight(): void
    {
        $result = $this->evaluator->evaluate('10 - 3 - 2');

        $this->assertSame(5.0, $result);
    }

    public function testEvaluateOperatorPrecedenceMultiplicationBeforeAddition(): void
    {
        $result = $this->evaluator->evaluate('2 + 3 * 4');

        $this->assertSame(14.0, $result);
    }

    public function testEvaluatePercentageConversion(): void
    {
        $result = $this->evaluator->evaluate('50%');

        $this->assertSame(0.5, $result);
    }

    public function testEvaluatePercentageInExpression(): void
    {
        $result = $this->evaluator->evaluate('50% + 25%');

        $this->assertSame(0.75, $result);
    }

    public function testEvaluatePercentageWithDecimal(): void
    {
        $result = $this->evaluator->evaluate('33.33%');

        $this->assertEqualsWithDelta(0.3333, $result, 0.0001);
    }

    public function testEvaluatePercentageWithMultiplication(): void
    {
        $result = $this->evaluator->evaluate('50% * 2');

        $this->assertSame(1.0, $result);
    }

    public function testEvaluateSimpleAddition(): void
    {
        $result = $this->evaluator->evaluate('2 + 3');

        $this->assertSame(5.0, $result);
    }

    public function testEvaluateSimpleDivision(): void
    {
        $result = $this->evaluator->evaluate('20 / 4');

        $this->assertSame(5.0, $result);
    }

    public function testEvaluateSimpleMultiplication(): void
    {
        $result = $this->evaluator->evaluate('3 * 4');

        $this->assertSame(12.0, $result);
    }

    public function testEvaluateSimpleSubtraction(): void
    {
        $result = $this->evaluator->evaluate('10 - 4');

        $this->assertSame(6.0, $result);
    }

    public function testEvaluateSingleNumber(): void
    {
        $result = $this->evaluator->evaluate('42');

        $this->assertSame(42.0, $result);
    }

    public function testEvaluateSingleVariable(): void
    {
        $result = $this->evaluator->evaluate('r', ['r' => 0.5]);

        $this->assertSame(0.5, $result);
    }

    public function testEvaluateUnaryMinus(): void
    {
        $result = $this->evaluator->evaluate('-5');

        $this->assertSame(-5.0, $result);
    }

    public function testEvaluateUnaryOperatorsWithParentheses(): void
    {
        $result = $this->evaluator->evaluate('-(2 + 3)');

        $this->assertSame(-5.0, $result);
    }

    public function testEvaluateUnaryPlus(): void
    {
        $result = $this->evaluator->evaluate('+5');

        $this->assertSame(5.0, $result);
    }

    public function testEvaluateUnaryPlusAfterOperator(): void
    {
        $result = $this->evaluator->evaluate('2 + + 3');

        $this->assertSame(5.0, $result);
    }

    public function testEvaluateUnaryPlusInExpression(): void
    {
        $result = $this->evaluator->evaluate('10 + -5');

        $this->assertSame(5.0, $result);
    }

    /**
     * @param array<string, float> $variables
     */
    #[DataProvider('validExpressionProvider')]
    public function testEvaluateValidExpressions(string $expression, array $variables, float $expected): void
    {
        $result = $this->evaluator->evaluate($expression, $variables);

        $this->assertEqualsWithDelta($expected, $result, 0.0001);
    }

    /**
     * @return iterable<string, array{string, array<string, float>, float}>
     */
    public static function validExpressionProvider(): iterable
    {
        yield 'simple literal' => ['5', [], 5.0];
        yield 'negative literal' => ['-10', [], -10.0];
        yield 'addition' => ['1 + 1', [], 2.0];
        yield 'subtraction' => ['5 - 3', [], 2.0];
        yield 'multiplication' => ['4 * 3', [], 12.0];
        yield 'division' => ['15 / 3', [], 5.0];
        yield 'mixed operations' => ['2 + 3 * 4', [], 14.0];
        yield 'parentheses change precedence' => ['(2 + 3) * 4', [], 20.0];
        yield 'nested parentheses' => ['((1 + 2) * (3 + 4))', [], 21.0];
        yield 'percentage' => ['75%', [], 0.75];
        yield 'percentage in calculation' => ['50% * 2', [], 1.0];
        yield 'variable substitution' => ['r * 2', ['r' => 0.5], 1.0];
        yield 'multiple variables' => ['r + g', ['r' => 0.3, 'g' => 0.7], 1.0];
        yield 'complex with variables' => ['(r + g) * b', ['r' => 0.2, 'g' => 0.3, 'b' => 2.0], 1.0];
        yield 'negative result' => ['5 - 10', [], -5.0];
        yield 'float result' => ['1 / 3', [], 0.333333];
        yield 'unary plus' => ['2 + + 3', [], 5.0];
        yield 'unary operators' => ['-(5 + 5)', [], -10.0];
        yield 'chained operations' => ['1 + 2 + 3 + 4', [], 10.0];
    }

    public function testEvaluateVariablesInComplexExpression(): void
    {
        $result = $this->evaluator->evaluate('(r + g) * 2', ['r' => 0.25, 'g' => 0.25]);

        $this->assertSame(1.0, $result);
    }

    public function testEvaluateVariablesReplacedByLengthDescending(): void
    {
        $result = $this->evaluator->evaluate('a + ab + abc', ['a' => 1.0, 'ab' => 10.0, 'abc' => 100.0]);

        $this->assertSame(111.0, $result);
    }

    public function testEvaluateVariableWithSimilarNames(): void
    {
        $result = $this->evaluator->evaluate('r + rr', ['r' => 1.0, 'rr' => 2.0]);

        $this->assertSame(3.0, $result);
    }

    public function testEvaluateWhitespaceHandling(): void
    {
        $result = $this->evaluator->evaluate('  2   +   3  ');

        $this->assertSame(5.0, $result);
    }

    public function testEvaluateWithAlphaVariable(): void
    {
        $result = $this->evaluator->evaluate('alpha * 0.5', ['alpha' => 0.8]);

        $this->assertEqualsWithDelta(0.4, $result, 0.0001);
    }

    public function testEvaluateWithDeeplyNestedParentheses(): void
    {
        $result = $this->evaluator->evaluate('(((2 + 3)))');

        $this->assertSame(5.0, $result);
    }

    public function testEvaluateWithMultipleVariables(): void
    {
        $result = $this->evaluator->evaluate('r + g + b', ['r' => 0.2, 'g' => 0.3, 'b' => 0.5]);

        $this->assertSame(1.0, $result);
    }

    public function testEvaluateWithNestedParentheses(): void
    {
        $result = $this->evaluator->evaluate('((2 + 3) * (4 + 1))');

        $this->assertSame(25.0, $result);
    }

    public function testEvaluateWithParentheses(): void
    {
        $result = $this->evaluator->evaluate('(2 + 3) * 4');

        $this->assertSame(20.0, $result);
    }

    public function testEvaluateWithSingleVariable(): void
    {
        $result = $this->evaluator->evaluate('r + 0.5', ['r' => 0.3]);

        $this->assertEqualsWithDelta(0.8, $result, 0.0001);
    }

    public function testEvaluateZeroValue(): void
    {
        $result = $this->evaluator->evaluate('0');

        $this->assertSame(0.0, $result);
    }

    public function testThrowsExceptionOnDivisionByZero(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Division by zero');

        $this->evaluator->evaluate('10 / 0');
    }

    public function testThrowsExceptionOnDivisionByZeroInComplexExpression(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Division by zero');

        $this->evaluator->evaluate('(10 + 5) / (3 - 3)');
    }

    public function testThrowsExceptionOnEmptyExpression(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Empty calc() expression');

        $this->evaluator->evaluate('');
    }

    public function testThrowsExceptionOnEmptyParentheses(): void
    {
        $this->expectException(ParseException::class);

        $this->evaluator->evaluate('2 + ()');
    }

    public function testThrowsExceptionOnInvalidCharacters(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid characters');

        $this->evaluator->evaluate('2 + $3');
    }

    public function testThrowsExceptionOnLeadingOperator(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unexpected token');

        $this->evaluator->evaluate('* 2 + 3');
    }

    public function testThrowsExceptionOnLettersWithoutVariables(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid characters');

        $this->evaluator->evaluate('abc + 5');
    }

    public function testThrowsExceptionOnMismatchedParenthesesExtraClosing(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unexpected token');

        $this->evaluator->evaluate('2 + 3)');
    }

    public function testThrowsExceptionOnMismatchedParenthesesMissingClosing(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Mismatched parentheses');

        $this->evaluator->evaluate('(2 + 3');
    }

    public function testThrowsExceptionOnTrailingOperator(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unexpected token');

        $this->evaluator->evaluate('2 + 3 +');
    }

    public function testThrowsExceptionOnUnexpectedOperator(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unexpected token');

        $this->evaluator->evaluate('2 + * 3');
    }

    public function testThrowsExceptionOnWhitespaceOnlyExpression(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Empty calc() expression');

        $this->evaluator->evaluate('   ');
    }
}
