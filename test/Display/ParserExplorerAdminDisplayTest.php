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


namespace Base3\ParseFlow\Test\Display;

use Base3\ParseFlow\Display\ParserExplorerAdminDisplay;
use Base3\ParseFlow\Parser\Markdown\MarkdownToTextParser;
use Base3\ParseFlow\Service\DefaultParserService;
use Base3\ParseFlow\Service\ParserScoreCalculator;
use Base3\ParseFlow\Service\ParserStateMatcher;
use Base3\ParseFlow\Test\Fixture\ParserClassMapFixture;
use PHPUnit\Framework\TestCase;

final class ParserExplorerAdminDisplayTest extends TestCase {

	private function display(): ParserExplorerAdminDisplay {
		$display = (new \ReflectionClass(ParserExplorerAdminDisplay::class))->newInstanceWithoutConstructor();
		(new \ReflectionProperty(ParserExplorerAdminDisplay::class, 'matcher'))->setValue($display, new ParserStateMatcher());
		(new \ReflectionProperty(ParserExplorerAdminDisplay::class, 'scoreCalculator'))->setValue($display, new ParserScoreCalculator());
		(new \ReflectionProperty(ParserExplorerAdminDisplay::class, 'parserService'))->setValue($display, new DefaultParserService(new ParserClassMapFixture(new MarkdownToTextParser())));
		return $display;
	}

	private function invoke(ParserExplorerAdminDisplay $display, string $method, mixed ...$args): mixed {
		return (new \ReflectionMethod(ParserExplorerAdminDisplay::class, $method))->invoke($display, ...$args);
	}

	public function testExplorerNamesAndNormalizesPaginationSortAndFilters(): void {
		$display = $this->display();
		self::assertSame('parserexploreradmindisplay', ParserExplorerAdminDisplay::getName());
		self::assertNull($display->setData(['ignored' => true]));
		$request = $this->invoke($display, 'normalizeRequest', [
			'page' => -7, 'pageSize' => 500, 'sort' => ['key' => 'unsupported', 'dir' => 'sideways'],
			'filters' => ['input_type' => 'string', 'input_format' => 'markdown', 'output_type' => 'string', 'output_format' => 'text'],
			'request' => ['search' => 'markdown']
		]);
		self::assertSame(1, $request['page']);
		self::assertSame(100, $request['pageSize']);
		self::assertSame(['key' => 'totalCost', 'direction' => 'asc'], $request['sort']);
		self::assertSame('string/markdown', $request['filters']['input_state']);
		self::assertSame('string/text', $request['filters']['output_state']);
		self::assertSame('markdown', $request['search']);
	}

	public function testExplorerBuildsOptionsSummaryAndDirectConversionRows(): void {
		$display = $this->display();
		$options = $this->invoke($display, 'buildOptionsPayload');
		self::assertSame('string/markdown', $options['defaults']['input_state']);
		self::assertSame('string/text', $options['defaults']['output_state']);
		self::assertNotEmpty($options['sourceTypes']);
		self::assertNotEmpty($options['targetTypes']);
		self::assertNotEmpty($options['parsers']);
		$summary = $this->invoke($display, 'buildSummaryPayload');
		self::assertSame(1, $summary['parserCount']);
		self::assertSame(1, $summary['routeCount']);
		self::assertSame(2, $summary['stateCount']);
		$req = $this->invoke($display, 'normalizeRequest', ['mode' => 'page', 'page' => 1, 'pageSize' => 10]);
		$page = $this->invoke($display, 'buildPageResponse', $req);
		self::assertSame('page', $page['mode']);
		self::assertGreaterThan(0, $page['total']);
		self::assertSame('selected-state-pair-top-k', $page['metadata']['explorationMode']);
		self::assertArrayHasKey('detail', $page['data'][0]);
		$id = $page['data'][0]['id'];
		$detail = $this->invoke($display, 'buildDetailResponse', $this->invoke($display, 'normalizeRequest', ['mode' => 'detail', 'id' => $id, 'row' => $page['data'][0]]));
		self::assertTrue($detail['found']);
		self::assertNotNull($detail['detail']);
	}

	public function testNoMatchingRouteAndMissingDetailAreReported(): void {
		$display = $this->display();
		$req = $this->invoke($display, 'normalizeRequest', [
			'filters' => ['input_state' => 'document/unknown', 'output_state' => 'structured/unknown']
		]);
		$page = $this->invoke($display, 'buildPageResponse', $req);
		self::assertSame(0, $page['total']);
		self::assertSame([], $page['data']);
		self::assertSame(1, $page['metadata']['expansions']);
		$detail = $this->invoke($display, 'buildDetailResponse', $this->invoke($display, 'normalizeRequest', [
			'mode' => 'detail', 'id' => 'missing',
			'filters' => ['input_state' => 'document/unknown', 'output_state' => 'structured/unknown']
		]));
		self::assertFalse($detail['found']);
		self::assertNull($detail['detail']);
	}
}
