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

use Base3\ParseFlow\Dto\ParserPayload;
use Base3\ParseFlow\Dto\ParserRequest;
use Base3\ParseFlow\Dto\ParserRoute;
use Base3\ParseFlow\Dto\ParserRouteQuality;
use Base3\ParseFlow\Dto\ParserState;
use Base3\ParseFlow\Dto\ParserStrategy;
use Base3\ParseFlow\Exception\UnsupportedParserRequestException;
use Base3\ParseFlow\Input\PlainTextParserInput;
use Base3\ParseFlow\Output\StructuredParserOutput;
use Base3\ParseFlow\Service\ParserPlanner;
use Base3\ParseFlow\Service\ParserRouteRegistry;
use Base3\ParseFlow\Service\ParserScoreCalculator;
use Base3\ParseFlow\Service\ParserStateMatcher;
use Base3\ParseFlow\Source\StringParserSource;
use Base3\ParseFlow\Target\ReturnParserTarget;
use Base3\ParseFlow\Test\Fixture\ParserClassMapFixture;
use Base3\ParseFlow\Test\Fixture\ParserProbeFixture;
use Base3\ParseFlow\Test\Fixture\ParserSecondProbeFixture;
use PHPUnit\Framework\TestCase;

final class ParserPlannerTest extends TestCase {

	private function route(string $name, string $route, ParserState $from, ParserState $to, ?ParserRouteQuality $quality = null, array $features = ['local']): ParserRoute {
		return new ParserRoute($name, $route, $from, $to, $quality ?? new ParserRouteQuality(), $features);
	}

	private function request(?string $parserName = null): ParserRequest {
		return new ParserRequest(new StringParserSource('input'), new PlainTextParserInput(), new StructuredParserOutput('json'), new ReturnParserTarget(), parserName: $parserName);
	}

	private function planner(ParserClassMapFixture $classMap): ParserPlanner {
		return new ParserPlanner(new ParserRouteRegistry($classMap), new ParserStateMatcher(), new ParserScoreCalculator());
	}

	public function testPlansTwoStepRouteAndAvoidsCycle(): void {
		$a = new ParserState('string', 'text');
		$b = new ParserState('string', 'intermediate');
		$c = new ParserState('structured', 'json');
		$ab = $this->route(ParserProbeFixture::getName(), 'ab', $a, $b);
		$ba = $this->route(ParserProbeFixture::getName(), 'ba', $b, $a);
		$bc = $this->route(ParserSecondProbeFixture::getName(), 'bc', $b, $c);
		$registry = new ParserClassMapFixture(new ParserProbeFixture([$ab, $ba]), new ParserSecondProbeFixture([$bc]));
		$plan = $this->planner($registry)->plan($this->request(), [new ParserPayload($a, 'test')], [$ab, $ba, $bc], ParserStrategy::balanced());
		self::assertCount(2, $plan->steps);
		self::assertSame(['ab', 'bc'], array_map(static fn($step): string => $step->route->routeName, $plan->steps));
		self::assertGreaterThan(0, $plan->totalCost);
		self::assertSame('state-and-route-path-guard', $plan->metadata['cycleGuard']);
	}

	public function testChoosesLessExpensiveRouteAndFiltersParserName(): void {
		$a = new ParserState('string', 'text');
		$b = new ParserState('structured', 'json');
		$expensive = $this->route(ParserProbeFixture::getName(), 'expensive', $a, $b, new ParserRouteQuality(textQuality: 0.1, structureQuality: 0.1));
		$cheaper = $this->route(ParserSecondProbeFixture::getName(), 'cheap', $a, $b);
		$planner = $this->planner(new ParserClassMapFixture(new ParserProbeFixture([$expensive]), new ParserSecondProbeFixture([$cheaper])));
		$payloads = [new ParserPayload($a, 'test')];
		$chosen = $planner->plan($this->request(), $payloads, [$expensive, $cheaper], ParserStrategy::balanced());
		self::assertSame('cheap', $chosen->steps[0]->route->routeName);
		$filtered = $planner->plan($this->request(ParserProbeFixture::getName()), $payloads, [$expensive, $cheaper], ParserStrategy::balanced());
		self::assertSame('expensive', $filtered->steps[0]->route->routeName);
	}

	public function testSelectsMatchingSourceAmongSeveralPayloads(): void {
		$a = new ParserState('string', 'text');
		$b = new ParserState('structured', 'json');
		$route = $this->route(ParserProbeFixture::getName(), 'direct', $a, $b);
		$planner = $this->planner(new ParserClassMapFixture(new ParserProbeFixture([$route])));
		$plan = $planner->plan($this->request(), [new ParserPayload(new ParserState('image', 'png'), 'missing'), new ParserPayload($a, 'works')], [$route], ParserStrategy::balanced());
		self::assertSame($a->getKey(), $plan->startState->getKey());
	}

	public function testRejectsWhenNoPlanOrRequiredFeaturesOrMaxStepsExcludeRoute(): void {
		$a = new ParserState('string', 'text');
		$b = new ParserState('structured', 'json');
		$route = $this->route(ParserProbeFixture::getName(), 'direct', $a, $b);
		$planner = $this->planner(new ParserClassMapFixture(new ParserProbeFixture([$route])));
		foreach ([new ParserStrategy(maxSteps: 0), new ParserStrategy(requiredFeatures: ['external']), new ParserStrategy(disabledParsers: [ParserProbeFixture::getName()])] as $strategy) {
			try {
				$planner->plan($this->request(), [new ParserPayload($a, 'input')], [$route], $strategy);
				self::fail('Expected an unsupported plan for route constraints.');
			} catch (UnsupportedParserRequestException $e) {
				self::assertStringContainsString('No parser plan found', $e->getMessage());
			}
		}
	}

	public function testRejectsParserEvaluationWhenUnavailable(): void {
		$a = new ParserState('string', 'text');
		$b = new ParserState('structured', 'json');
		$route = $this->route(ParserProbeFixture::getName(), 'unavailable', $a, $b);
		$probe = new ParserProbeFixture([$route], supported: false);
		$this->expectException(UnsupportedParserRequestException::class);
		$this->planner(new ParserClassMapFixture($probe))->plan($this->request(), [new ParserPayload($a, 'test')], [$route], ParserStrategy::balanced());
	}
}
