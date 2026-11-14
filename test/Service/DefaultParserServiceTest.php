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


namespace Base3\ParseFlow\Test\Service;

use Base3\ParseFlow\Dto\ParserExploreRequest;
use Base3\ParseFlow\Dto\ParserRequest;
use Base3\ParseFlow\Dto\ParserStrategy;
use Base3\ParseFlow\Exception\UnsupportedParserRequestException;
use Base3\ParseFlow\Input\PlainTextParserInput;
use Base3\ParseFlow\Output\StringParserOutput;
use Base3\ParseFlow\Parser\Markdown\MarkdownToTextParser;
use Base3\ParseFlow\Service\DefaultParserService;
use Base3\ParseFlow\Source\StringParserSource;
use Base3\ParseFlow\Target\ReturnParserTarget;
use Base3\ParseFlow\Test\Fixture\ParserClassMapFixture;
use PHPUnit\Framework\TestCase;

final class DefaultParserServiceTest extends TestCase {

	private function request(string $input = '# Heading', string $output = 'text', ?ParserStrategy $strategy = null): ParserRequest {
		return new ParserRequest(new StringParserSource($input), new PlainTextParserInput('markdown'), new StringParserOutput($output), new ReturnParserTarget(), $strategy);
	}

	public function testEndToEndPlanParseAndRouteDiscovery(): void {
		$service = new DefaultParserService(new ParserClassMapFixture(new MarkdownToTextParser()));
		$request = $this->request('# Heading');
		self::assertTrue($service->supports($request));
		$plan = $service->plan($request);
		self::assertCount(1, $plan->steps);
		self::assertSame(MarkdownToTextParser::getName(), $plan->steps[0]->parserName);
		$result = $service->parse($request);
		self::assertTrue($result->isSuccess());
		self::assertSame('Heading', $result->getPayload()->getValue());
		self::assertSame('string', $result->getPayload()->state->type);
		self::assertSame('text', $result->getPayload()->state->format);
		self::assertSame($result->getPayload(), $result->targetResult);
		self::assertCount(1, $service->listRoutes());
	}

	public function testRejectsUnsupportedPlanAndReportsNoSupport(): void {
		$service = new DefaultParserService(new ParserClassMapFixture(new MarkdownToTextParser()));
		self::assertFalse($service->supports($this->request(output: 'csv')));
		$this->expectException(UnsupportedParserRequestException::class);
		$service->parse($this->request(output: 'csv'));
	}

	public function testExplorerProvidesRoutesStatesAndSuggestions(): void {
		$service = new DefaultParserService(new ParserClassMapFixture(new MarkdownToTextParser()));
		$report = $service->explore(new ParserExploreRequest($this->request()));
		self::assertSame(1, $report->metadata['routeCount']);
		self::assertSame(1, $report->metadata['parserCount']);
		self::assertCount(1, $report->parsers);
		self::assertCount(1, $report->routes);
		self::assertNotEmpty($report->states);
		self::assertNotEmpty($report->outputs);
		self::assertNotNull($report->suggestedPlan);
		self::assertSame([], $report->warnings);
		$minimal = $service->explore(new ParserExploreRequest(includeRoutes: false, includeStates: false, includeParserDetails: false, includePlanSuggestion: false));
		self::assertSame([], $minimal->routes);
		self::assertSame([], $minimal->states);
		self::assertSame([MarkdownToTextParser::getName()], $minimal->parsers);
		self::assertNull($minimal->suggestedPlan);
		$invalid = $service->explore(new ParserExploreRequest($this->request(output: 'csv')));
		self::assertNull($invalid->suggestedPlan);
		self::assertNotEmpty($invalid->warnings);
		self::assertSame(1, $service->explore()->metadata['routeCount']);
	}
}
