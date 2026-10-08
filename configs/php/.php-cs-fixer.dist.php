<?php

declare(strict_types=1);

// Vorlage: in den Repo-Root kopieren. Pfade an das Addon anpassen.

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/lib', __DIR__ . '/tests'])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        'declare_strict_types' => true,
        'native_function_invocation' => ['include' => ['@compiler_optimized']],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        // Lektion (REDAXO-Projekt, 2026-10): @Symfony erzwingt camelCase-Testnamen.
        // Die Beispiele in harness/testing.md nutzen test_snake_case, daher aus.
        'php_unit_method_casing' => false,
    ])
    ->setFinder($finder);
