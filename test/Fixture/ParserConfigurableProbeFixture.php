<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of ParseFlow for BASE3 Framework.
 *
 * ParseFlow supplies graph-based parser services.
 * It discovers parser capabilities and plans conversion routes.
 * The parser workflow runs through existing module contracts.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 ***********************************************************************/


namespace Base3\ParseFlow\Test\Fixture;

use Base3\CoreFoundation\Api\ISchemaProvider;

final class ParserConfigurableProbeFixture extends ParserProbeFixture implements ISchemaProvider {

	public static function getName(): string { return 'parserconfigurableprobefixture'; }

	public function getSchema(): array {
		return [
			'properties' => [
				'enabled' => ['type' => 'boolean', 'default' => true],
				'limit' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 10, 'default' => 2],
				'ratio' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
				'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
				'object' => ['type' => 'object'],
				'mode' => ['type' => 'string', 'enum' => ['one', 'two']]
			],
			'required' => ['limit']
		];
	}
}
