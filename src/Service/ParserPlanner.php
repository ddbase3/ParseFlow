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

/**
 * Builds the cheapest parser plan with uniform-cost search.
 */
class ParserPlanner {

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
		$bestPlan = null;

		foreach ($payloads as $payload) {
			$plan = $this->planFromPayload($request, $payload, $targetState, $routes, $strategy);
			$bestPlan = $this->chooseBetter($bestPlan, $plan);
		}

		if ($bestPlan === null) {
			throw new UnsupportedParserRequestException('No parser plan found for requested source/output state.');
		}

		return $bestPlan;
	}

	/**
	 * @param ParserRoute[] $routes
	 */
	private function planFromPayload(ParserRequest $request, ParserPayload $payload, ParserState $targetState, array $routes, ParserStrategy $strategy): ?ParserPlan {
		$queue = [[
			'cost' => 0.0,
			'state' => $payload->state,
			'steps' => [],
			'warnings' => [],
		]];
		$bestByStateAndDepth = [];
		$bestPlan = null;

		while ($queue) {
			usort($queue, fn(array $a, array $b) => $a['cost'] <=> $b['cost']);
			$current = array_shift($queue);
			$currentState = $current['state'];
			$stepCount = count($current['steps']);

			if ($stepCount > 0 && $this->matcher->matches($currentState, $targetState)) {
				$bestPlan = $this->chooseBetter($bestPlan, new ParserPlan($payload->state, $targetState, $current['steps'], $current['cost'], $current['warnings']));
				continue;
			}

			if ($stepCount >= $strategy->maxSteps) {
				continue;
			}

			foreach ($routes as $route) {
				if ($request->parserName !== null && $request->parserName !== $route->parserName) {
					continue;
				}

				if (!$this->matcher->matches($currentState, $route->from, $payload->metadata)) {
					continue;
				}

				$parser = $this->registry->getParser($route->parserName);
				if ($parser === null) {
					continue;
				}

				$context = new ParserPlanningContext($request, $payload->withState($currentState), $strategy);
				$evaluation = $parser->evaluate($route, $context);
				if ($this->scoreCalculator->violatesConstraints($route, $evaluation, $strategy)) {
					continue;
				}

				$routeCost = $this->scoreCalculator->calculate($route, $evaluation, $strategy);
				$nextCost = $current['cost'] + $routeCost;
				$nextStep = new ParserPlanStep($route->parserName, $route, $currentState, $route->to, $routeCost, $evaluation);
				$nextSteps = array_merge($current['steps'], [$nextStep]);
				$key = $route->to->getKey() . ':' . count($nextSteps);

				if (isset($bestByStateAndDepth[$key]) && $bestByStateAndDepth[$key] <= $nextCost) {
					continue;
				}

				$bestByStateAndDepth[$key] = $nextCost;
				$queue[] = [
					'cost' => $nextCost,
					'state' => $route->to,
					'steps' => $nextSteps,
					'warnings' => array_merge($current['warnings'], $evaluation->warnings),
				];
			}
		}

		return $bestPlan;
	}

	private function chooseBetter(?ParserPlan $a, ?ParserPlan $b): ?ParserPlan {
		if ($a === null) { return $b; }
		if ($b === null) { return $a; }
		$epsilon = 0.000001;
		if (abs($a->totalCost - $b->totalCost) > $epsilon) {
			return $a->totalCost < $b->totalCost ? $a : $b;
		}
		if (count($a->steps) !== count($b->steps)) {
			return count($a->steps) < count($b->steps) ? $a : $b;
		}
		return $this->planKey($a) <= $this->planKey($b) ? $a : $b;
	}

	private function planKey(ParserPlan $plan): string {
		return implode('|', array_map(fn(ParserPlanStep $step) => $step->route->getKey(), $plan->steps));
	}
}
