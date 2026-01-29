<?php

$licence = <<<'EOF'
This file is part of the PHPColor library.

(c) 2025-present Simon André & Raphaêl Geffroy

For the full copyright and license information, please view
the LICENSE file that was distributed with this source code.
EOF;

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'declare_strict_types' => true,
        'header_comment' => ['header' => $licence],
    ])
    ->setFinder($finder)
;
