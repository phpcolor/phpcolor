<?php

$licence = <<<'EOF'
This file is part of the PhpColor package.

(c) Simon André & Raphaêl Geffroy

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'declare_strict_types' => true,
        'header_comment' => ['header' => $licence],
    ])
    ->setFinder($finder)
;
