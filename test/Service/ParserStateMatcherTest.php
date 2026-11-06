<?php declare(strict_types=1);

namespace ParseFlow\Test\Service;

use ParseFlow\Dto\ParserState;
use ParseFlow\Service\ParserStateMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ParserStateMatcherTest extends TestCase {

	public static function stateMatches(): iterable {
		yield 'identical' => [new ParserState('string', 'text'), true];
		yield 'null fields are wildcards' => [new ParserState('string'), true];
		yield 'star type' => [new ParserState('*', '*'), true];
		yield 'any type' => [new ParserState('any', 'any'), true];
		yield 'other type' => [new ParserState('document', 'text'), false];
		yield 'other format' => [new ParserState('string', 'markdown'), false];
		yield 'other mime' => [new ParserState('string', null, 'application/pdf'), false];
		yield 'other extension' => [new ParserState('string', null, null, 'bin'), false];
		yield 'available feature' => [new ParserState('string', null, null, null, ['local']), true];
		yield 'missing feature' => [new ParserState('string', null, null, null, ['external']), false];
	}

	#[DataProvider('stateMatches')]
	public function testMatchesExpectedState(ParserState $expected, bool $matches): void {
		$actual = new ParserState('string', 'text', 'text/plain', 'txt', ['local']);
		self::assertSame($matches, (new ParserStateMatcher())->matches($actual, $expected));
	}

	public function testIncludesSourceMetadataFeatures(): void {
		$actual = new ParserState('string', 'text');
		$expected = new ParserState('string', 'text', features: ['runtime']);
		self::assertFalse((new ParserStateMatcher())->matches($actual, $expected));
		self::assertTrue((new ParserStateMatcher())->matches($actual, $expected, ['features' => ['runtime']]));
	}
}
