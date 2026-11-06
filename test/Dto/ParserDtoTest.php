<?php declare(strict_types=1);

namespace ParseFlow\Test\Dto;

use ParseFlow\Dto\ParserCapabilityReport;
use ParseFlow\Dto\ParserExploreRequest;
use ParseFlow\Dto\ParserPayload;
use ParseFlow\Dto\ParserPlan;
use ParseFlow\Dto\ParserPlanStep;
use ParseFlow\Dto\ParserRequest;
use ParseFlow\Dto\ParserResult;
use ParseFlow\Dto\ParserRoute;
use ParseFlow\Dto\ParserRouteEvaluation;
use ParseFlow\Dto\ParserRouteQuality;
use ParseFlow\Dto\ParserState;
use ParseFlow\Dto\ParserStrategy;
use ParseFlow\Input\PlainTextParserInput;
use ParseFlow\Output\StringParserOutput;
use ParseFlow\Source\StringParserSource;
use ParseFlow\Target\ReturnParserTarget;
use PHPUnit\Framework\TestCase;

final class ParserDtoTest extends TestCase {

	public function testPayloadPreservesValueAndResourcesWhenChangingState(): void {
		$start = new ParserState('string', 'text', 'text/plain', 'txt', ['local'], ['a' => 1]);
		$next = new ParserState('structured', 'json');
		self::assertSame('string|text|text/plain|txt', $start->getKey());
		self::assertSame('structured|json|*|*', $next->getKey());
		$payload = new ParserPayload($start, ['value' => 7], ['origin' => 'test'], ['/tmp/example']);
		$updated = $payload->withState($next);
		self::assertSame($next, $updated->state);
		self::assertSame(['value' => 7], $updated->getValue());
		self::assertSame(['origin' => 'test'], $updated->metadata);
		self::assertSame(['/tmp/example'], $updated->temporaryResources);
		self::assertSame($start, $payload->state);
	}

	public function testRoutePlanResultAndCapabilityDataAreStable(): void {
		$start = new ParserState('string', 'markdown');
		$end = new ParserState('document', 'html');
		$quality = new ParserRouteQuality(priority: 22, lossy: true);
		$route = new ParserRoute('markdown', 'markdown-html', $start, $end, $quality, ['local'], ['dom'], ['x' => true]);
		$evaluation = ParserRouteEvaluation::supported($quality, 0.75, ['why' => 'test'], ['warning']);
		self::assertSame('markdown:markdown-html', $route->getKey());
		self::assertTrue($evaluation->supported);
		self::assertSame(0.75, $evaluation->confidence);
		self::assertSame(['warning'], $evaluation->warnings);
		$unsupported = ParserRouteEvaluation::unsupported(null, ['missing']);
		self::assertFalse($unsupported->supported);
		self::assertSame(0.0, $unsupported->confidence);
		self::assertInstanceOf(ParserRouteQuality::class, $unsupported->quality);
		$step = new ParserPlanStep('markdown', $route, $start, $end, 0.5, $evaluation);
		$plan = new ParserPlan($start, $end, [$step], 0.5, ['warning']);
		self::assertFalse($plan->isEmpty());
		self::assertSame([$step], $plan->getSteps());
		self::assertTrue((new ParserPlan($start, $end, []))->isEmpty());
		$payload = new ParserPayload($end, '<p>ok</p>');
		$result = new ParserResult(true, $plan, $payload, ['path' => '/tmp/out']);
		self::assertTrue($result->isSuccess());
		self::assertSame($payload, $result->getPayload());
		self::assertSame($plan, $result->getPlan());
		$report = new ParserCapabilityReport(['markdown'], [['route' => 1]], [['state' => 1]], [['type' => 'document']], $plan);
		self::assertSame($plan, $report->suggestedPlan);
		self::assertSame(['markdown'], $report->parsers);
	}

	public function testStrategiesAndRequestsKeepExplicitConstraints(): void {
		self::assertSame('balanced', ParserStrategy::balanced()->mode);
		self::assertSame('best_quality', ParserStrategy::bestQuality()->mode);
		self::assertSame('fastest', ParserStrategy::fastest()->mode);
		self::assertFalse(ParserStrategy::localOnly()->allowExternalServices);
		$request = new ParserRequest(new StringParserSource('text'), new PlainTextParserInput(), new StringParserOutput(), new ReturnParserTarget(), ParserStrategy::fastest(), ['a' => 1], ['b' => 2], 'parser');
		self::assertSame('parser', $request->parserName);
		self::assertSame(['a' => 1], $request->options);
		$explore = new ParserExploreRequest($request, includeRoutes: false, includeStates: false, includeParserDetails: false, includePlanSuggestion: false);
		self::assertSame($request, $explore->request);
		self::assertFalse($explore->includeRoutes);
		self::assertFalse($explore->includeStates);
		self::assertFalse($explore->includeParserDetails);
		self::assertFalse($explore->includePlanSuggestion);
	}
}
