<?php

$finder = PhpCsFixer\Finder::create()
    ->in('src')
    ->in('tests');

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP80Migration' => true,
        '@PHP80Migration:risky' => true,
        '@PSR12' => true,
        '@PSR12:risky' => true,
        'align_multiline_comment' => true,
        'array_indentation' => true,
        'class_attributes_separation' => ['elements' => ['method' => 'one', 'property' => 'only_if_meta']],
        'concat_space' => ['spacing' => 'one'],
        'function_declaration' => ['closure_function_spacing' => 'none'],
        'increment_style' => ['style' => 'post'],
        'method_chaining_indentation' => true,
        'multiline_comment_opening_closing' => true,
        'native_function_invocation' => false,
        'no_empty_comment' => true,
        'no_null_property_initialization' => true,
        'no_superfluous_phpdoc_tags' => true,
        'no_unset_on_property' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_public_static',
                'property_protected',
                'property_protected_static',
                'property_private',
                'property_private_static',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public',
                'method_public_abstract',
                'method_public_static',
                'method_protected',
                'method_protected_abstract',
                'method_protected_static',
                'method_private',
            ],
        ],
        'operator_linebreak' => ['only_booleans' => true],
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_order' => true,
        'phpdoc_summary' => false,
        'phpdoc_tag_casing' => true,
        'phpdoc_to_comment' => false,
        'regular_callable_call' => true,
        'return_assignment' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'yoda_style' => false,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setUsingCache(true);
