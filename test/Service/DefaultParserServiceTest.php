<?php declare(strict_types=1);

namespace ParseFlow\Test\Service;

use ParseFlow\Dto\ParserExploreRequest;
use ParseFlow\Dto\ParserRequest;
use ParseFlow\Dto\ParserStrategy;
use ParseFlow\Exception\UnsupportedParserRequestException;
use ParseFlow\Input\PlainTextParserInput;
use ParseFlow\Output\StringParserOutput;
use ParseFlow\Parser\Markdown\MarkdownToTextParser;
use ParseFlow\Service\DefaultParserService;
use ParseFlow\Source\StringParserSource;
use ParseFlow\Target\ReturnParserTarget;
use ParseFlow\Test\Fixture\ParserClassMapFixture;
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
