<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('vendor')
    ->exclude('migrations')
;

return (new PhpCsFixer\Config())
    ->setRules([
        // Use strict PSR12 + Symfony + PhpCsFixer rulesets
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@PHP83Migration' => true,

        // Override a few rules for DDD/clean architecture
        'declare_strict_types' => true,
        'final_class' => false, // DDD aggregates may need to be extended
        'yoda_style' => false, // More readable without yoda
        'concat_space' => ['spacing' => 'one'], // $foo . ' bar' instead of $foo.' bar'
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache')
;
