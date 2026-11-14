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


namespace Base3\ParseFlow\Test\Dto;

use Base3\ParseFlow\Dto\ParserCapabilityReport;
use Base3\ParseFlow\Dto\ParserExploreRequest;
use Base3\ParseFlow\Dto\ParserPayload;
use Base3\ParseFlow\Dto\ParserPlan;
use Base3\ParseFlow\Dto\ParserPlanStep;
use Base3\ParseFlow\Dto\ParserRequest;
use Base3\ParseFlow\Dto\ParserResult;
use Base3\ParseFlow\Dto\ParserRoute;
use Base3\ParseFlow\Dto\ParserRouteEvaluation;
use Base3\ParseFlow\Dto\ParserRouteQuality;
use Base3\ParseFlow\Dto\ParserState;
use Base3\ParseFlow\Dto\ParserStrategy;
use Base3\ParseFlow\Input\PlainTextParserInput;
use Base3\ParseFlow\Output\StringParserOutput;
use Base3\ParseFlow\Source\StringParserSource;
use Base3\ParseFlow\Target\ReturnParserTarget;
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
