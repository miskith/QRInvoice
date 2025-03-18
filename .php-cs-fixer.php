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
		'@PHP83Migration' => true,
		'array_syntax' => ['syntax' => 'short'],
		'no_unused_imports' => true,
		'short_scalar_cast' => true,
		'trim_array_spaces' => true,
		'single_quote' => true,
		'array_indentation' => true,
		'no_extra_blank_lines' => true,
		'use_arrow_functions' => true,
		'ordered_imports' => [
			'sort_algorithm' => 'none',
		],
		'binary_operator_spaces' => [
			'operators' => [
				'=>' => 'single_space',
			],
		],
	])
	->setIndent("\t")
	->setLineEnding("\n")
	->setFinder($finder);
