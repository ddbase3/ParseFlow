<?php declare(strict_types=1);

namespace ParseFlow\Test\Fixture;

use Base3\Api\ISchemaProvider;

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
