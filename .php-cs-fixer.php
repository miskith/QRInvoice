<?php

$finder = Symfony\Component\Finder\Finder::create()
	->notPath('bin')
	->notPath('vendor')
	->in(__DIR__)
	->name('*.php');

return (new PhpCsFixer\Config)
	->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
	->setRules([
		'@PSR12' => true,
		'@PHP84Migration' => true,
		'array_syntax' => ['syntax' => 'short'],
		'no_unused_imports' => true,
		'short_scalar_cast' => true,
		'trim_array_spaces' => true,
		'single_quote' => true,
		'array_indentation' => true,
		'no_extra_blank_lines' => true,
		'use_arrow_functions' => true,
		'ordered_imports' => [
			'sort_algorithm' => 'alpha',
			'imports_order' => ['class', 'function', 'const'],
		],
		'binary_operator_spaces' => [
			'operators' => [
				'=>' => 'single_space',
			],
		],
		'trailing_comma_in_multiline' => [
			'elements' => ['arrays', 'arguments', 'parameters'],
		],
		'nullable_type_declaration_for_default_null_value' => true,
		'void_return' => true,
		'ternary_to_null_coalescing' => true,
		'assign_null_coalescing_to_coalesce_equal' => true,
		'no_useless_else' => true,
		'no_useless_return' => true,
		'combine_consecutive_issets' => true,
		'combine_consecutive_unsets' => true,
		'native_function_casing' => true,
		'native_type_declaration_casing' => true,
		'modernize_types_casting' => true,
		'fully_qualified_strict_types' => true,
		'no_leading_import_slash' => true,
		'no_empty_phpdoc' => true,
		'phpdoc_scalar' => true,
		'phpdoc_types' => true,
		'phpdoc_trim' => true,
		'phpdoc_single_line_var_spacing' => true,
		'no_superfluous_phpdoc_tags' => [
			'allow_mixed' => true,
			'remove_inheritdoc' => true,
		],
		'blank_line_before_statement' => [
			'statements' => ['return', 'throw', 'try'],
		],
	])
	->setIndent("\t")
	->setLineEnding("\n")
	->setFinder($finder);
