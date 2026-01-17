<?php

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/app'])
    ->notPath('vendor')
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => false, 
        'native_function_invocation' => ['include' => ['@all'], 'scope' => 'namespaced'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'single_quote' => true,
        'ternary_to_null_coalescing' => true,
        'strict_param' => true
    ])
    ->setFinder($finder);