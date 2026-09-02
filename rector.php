<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
	->withPaths([
		__DIR__ . '/src',
		__DIR__ . '/Tests',
	])
	->withPhpSets(php84: true)
	->withPreparedSets(
		deadCode: true,
		codeQuality: true,
		codingStyle: true,
		typeDeclarations: true,
		typeDeclarationDocblocks: true,
		privatization: true,
		phpunitCodeQuality: true,
		phpunitNarrowAsserts: true,
		phpunitMockToStub: true,
	);
