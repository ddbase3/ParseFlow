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


namespace Base3\ParseFlow\Test\Source;

use Base3\ParseFlow\Input\StructuredParserInput;
use Base3\ParseFlow\Service\ParserSourceResolver;
use Base3\ParseFlow\Source\StructuredParserSource;
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
