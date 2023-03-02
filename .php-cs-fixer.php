<?php

// $header = <<<EOF
// This file is part of the ACSEO/AIErrorExplainedBundle package.
// (c) ACSEO <https://github.com/acseo/AIErrorExplainedBundle>
// For the full copyright and license information, please view the LICENSE
// file that was distributed with this source code.
// EOF;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PHP71Migration' => true,
        '@PSR2' => true,
        '@PhpCsFixer' => true,
        '@Symfony' => true,
        //'header_comment' => ['header' => $header],
        'is_null' => true,
        'align_multiline_comment' => [
            'comment_type' => 'all_multiline',
        ],
	'array_indentation' => true,
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'method_chaining_indentation' => false,
        'native_constant_invocation' => true,
        'native_function_invocation' => [
            'include' => ['@all'],
        ],
        'no_alias_functions' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'ordered_imports' => true,
        'php_unit_test_class_requires_covers' => false,
        'phpdoc_no_empty_return' => false,
        'visibility_required' => ['elements' => ['property', 'method', 'const']],
    ])
    ->setUsingCache(true)
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in([
                __DIR__.'/src',
                __DIR__.'/tests',
            ])
    )
;
