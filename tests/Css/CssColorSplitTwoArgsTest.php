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

use PhpColor\Color\Css\CssColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CssColor::class)]
final class CssColorSplitTwoArgsTest extends TestCase
{
    public function testSplitTwoArgsContinuesAfterClosingParenAtNestedLevel(): void
    {
        // Craft an inner string that contains a nested closing parenthesis before the top-level comma
        // to exercise the ")" branch with level>0 and the subsequent continue (L216)
        $inner = 'foo(bar(baz)), #000';

        $ref = new \ReflectionClass(CssColor::class);
        $m = $ref->getMethod('splitTwoArgs');

        [$left, $right] = $m->invoke(null, $inner);

        $this->assertSame('foo(bar(baz))', $left);
        $this->assertSame('#000', $right);
    }
}
