<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of ParseFlow for BASE3 Framework.
 *
 * ParseFlow provides a graph-based parser service with discoverable
 * parser capabilities, deterministic planning and modular execution.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de/v/parseflow
 * https://github.com/ddbase3/ParseFlow
 **********************************************************************/

namespace ParseFlow\Service;

use ParseFlow\Dto\ParserPayload;
use ParseFlow\Dto\ParserPlan;
use ParseFlow\Dto\ParserPlanningContext;
use ParseFlow\Dto\ParserPlanStep;
use ParseFlow\Dto\ParserRequest;
use ParseFlow\Dto\ParserRoute;
use ParseFlow\Dto\ParserState;
use ParseFlow\Dto\ParserStrategy;
use ParseFlow\Exception\UnsupportedParserRequestException;
use SplPriorityQueue;

/**
 * Builds the cheapest parser plan with uniform-cost search.
 */
class ParserPlanner {

	private const MAX_NODE_EXPANSIONS = 5000;

	public function __construct(
		private readonly ParserRouteRegistry $registry,
		private readonly ParserStateMatcher $matcher,
		private readonly ParserScoreCalculator $scoreCalculator
	) {}

	/**
	 * @param ParserPayload[] $payloads
	 * @param ParserRoute[] $routes
	 */
	public function plan(ParserRequest $request, array $payloads, array $routes, ParserStrategy $strategy): ParserPlan {
		$targetState = new ParserState($request->output->getType(), $request->output->getFormat());
		$routeIndex = $this->buildRouteIndex($routes);
		$bestPlan = null;
		$warnings = [];

		foreach($payloads as $payload) {
			$plan = $this->planFromPayload($request, $payload, $targetState, $routeIndex, $strategy, $warnings);
			$bestPlan = $this->chooseBetter($bestPlan, $plan);
		}

		if($bestPlan === null) {
			throw new UnsupportedParserRequestException('No parser plan found for requested source/output state.');
		}

		return $bestPlan;
	}

	/**
	 * @param array<string,ParserRoute[]> $routeIndex
	 * @param string[] $warnings
	 */
	private function planFromPayload(ParserRequest $request, ParserPayload $payload, ParserState $targetState, array $routeIndex, ParserStrategy $strategy, array &$warnings): ?ParserPlan {
		$queue = new SplPriorityQueue();
		$queue->setExtractFlags(SplPriorityQueue::EXTR_DATA);
		$queue->insert([
			'cost' => 0.0,
			'state' => $payload->state,
			'steps' => [],
			'warnings' => [],
			'visitedStates' => [$payload->state->getKey() => true],
			'visitedRoutes' => [],
		], 0.0);

		$bestCostByState = [$payload->state->getKey() => 0.0];
		$bestPlan = null;
		$expansions = 0;

		while(!$queue->isEmpty()) {
			$current = $queue->extract();
			$currentState = $current['state'];
			$currentCost = (float) $current['cost'];
			$steps = $current['steps'];
			$stepCount = count($steps);

			if($bestPlan !== null && $currentCost > ($bestPlan->totalCost + 0.000001)) {
				continue;
			}

			if($stepCount > 0 && $this->matcher->matches($currentState, $targetState)) {
				$bestPlan = $this->chooseBetter(
					$bestPlan,
					new ParserPlan($payload->state, $targetState, $steps, $currentCost, $current['warnings'], [
						'nodeExpansions' => $expansions,
						'cycleGuard' => 'state-and-route-path-guard'
					])
				);
				continue;
			}

			if($stepCount >= $strategy->maxSteps) {
				continue;
			}

			$expansions++;

			if($expansions > self::MAX_NODE_EXPANSIONS) {
				$warnings[] = 'Parser planning stopped because the maximum number of graph expansions was reached.';
				break;
			}

			foreach($this->getOutgoingRoutes($currentState, $routeIndex, $payload->metadata) as $route) {
				if($request->parserName !== null && $request->parserName !== $route->parserName) {
					continue;
				}

				$routeKey = $route->getKey();
				$toKey = $route->to->getKey();

				if(isset($current['visitedRoutes'][$routeKey]) || isset($current['visitedStates'][$toKey])) {
					continue;
				}

				$parser = $this->registry->getParser($route->parserName);
				if($parser === null) {
					continue;
				}

				$context = new ParserPlanningContext($request, $payload->withState($currentState), $strategy);
				$evaluation = $parser->evaluate($route, $context);
				if($this->scoreCalculator->violatesConstraints($route, $evaluation, $strategy)) {
					continue;
				}

				$routeCost = $this->scoreCalculator->calculate($route, $evaluation, $strategy);
				$nextCost = $currentCost + $routeCost;

				if(isset($bestCostByState[$toKey]) && $bestCostByState[$toKey] <= $nextCost) {
					continue;
				}

				$bestCostByState[$toKey] = $nextCost;
				$nextStep = new ParserPlanStep($route->parserName, $route, $currentState, $route->to, $routeCost, $evaluation);
				$nextVisitedStates = $current['visitedStates'];
				$nextVisitedRoutes = $current['visitedRoutes'];
				$nextVisitedStates[$toKey] = true;
				$nextVisitedRoutes[$routeKey] = true;

				$queue->insert([
					'cost' => $nextCost,
					'state' => $route->to,
					'steps' => array_merge($steps, [$nextStep]),
					'warnings' => array_merge($current['warnings'], $evaluation->warnings),
					'visitedStates' => $nextVisitedStates,
					'visitedRoutes' => $nextVisitedRoutes,
				], -$nextCost);
			}
		}

		if($bestPlan !== null && count($warnings) > 0) {
			return new ParserPlan(
				$bestPlan->startState,
				$bestPlan->targetState,
				$bestPlan->steps,
				$bestPlan->totalCost,
				array_values(array_unique(array_merge($bestPlan->warnings, $warnings))),
				$bestPlan->metadata
			);
		}

		return $bestPlan;
	}

	/**
	 * @param ParserRoute[] $routes
	 * @return array<string,ParserRoute[]>
	 */
	private function buildRouteIndex(array $routes): array {
		$index = [];

		foreach($routes as $route) {
			$index[$route->from->type] ??= [];
			$index[$route->from->type][] = $route;
		}

		return $index;
	}

	/**
	 * @param array<string,ParserRoute[]> $routeIndex
	 * @return ParserRoute[]
	 */
	private function getOutgoingRoutes(ParserState $state, array $routeIndex, array $metadata): array {
		$candidates = [];

		foreach([$state->type, '*', 'any'] as $type) {
			foreach($routeIndex[$type] ?? [] as $route) {
				$candidates[$route->getKey()] = $route;
			}
		}

		return array_values(array_filter(
			$candidates,
			fn(ParserRoute $route): bool => $this->matcher->matches($state, $route->from, $metadata)
		));
	}

	private function chooseBetter(?ParserPlan $a, ?ParserPlan $b): ?ParserPlan {
		if($a === null) { return $b; }
		if($b === null) { return $a; }
		$epsilon = 0.000001;
		if(abs($a->totalCost - $b->totalCost) > $epsilon) {
			return $a->totalCost < $b->totalCost ? $a : $b;
		}
		if(count($a->steps) !== count($b->steps)) {
			return count($a->steps) < count($b->steps) ? $a : $b;
		}
		return $this->planKey($a) <= $this->planKey($b) ? $a : $b;
	}

	private function planKey(ParserPlan $plan): string {
		return implode('|', array_map(fn(ParserPlanStep $step) => $step->route->getKey(), $plan->steps));
	}
}
