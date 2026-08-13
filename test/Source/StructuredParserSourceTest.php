<?php declare(strict_types=1);

namespace ParseFlow\Test\Source;

use ParseFlow\Input\StructuredParserInput;
use ParseFlow\Service\ParserSourceResolver;
use ParseFlow\Source\StructuredParserSource;
use PHPUnit\Framework\TestCase;

final class StructuredParserSourceTest extends TestCase {

	public function testStructuredValueEntersGraphWithoutSerialization(): void {
		$value = ['type' => 'example', 'nested' => ['value' => 42]];
		$source = new StructuredParserSource($value, ['source' => 'test']);
		$inspection = (new ParserSourceResolver())->resolve($source, new StructuredParserInput('example'));

		self::assertCount(1, $inspection->payloads);
		self::assertSame('structured', $inspection->payloads[0]->state->type);
		self::assertSame('example', $inspection->payloads[0]->state->format);
		self::assertSame($value, $inspection->payloads[0]->getValue());
		self::assertSame('test', $inspection->payloads[0]->metadata['source'] ?? null);
	}
}
