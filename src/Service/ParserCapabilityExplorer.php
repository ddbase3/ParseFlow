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

use ParseFlow\Dto\ParserCapabilityReport;
use ParseFlow\Dto\ParserExploreRequest;
use ParseFlow\Dto\ParserRoute;
use ParseFlow\Dto\ParserState;
use ParseFlow\Dto\ParserStrategy;

/**
 * Builds a discoverable capability report from the active parser graph.
 */
class ParserCapabilityExplorer {

	public function __construct(
		private readonly ParserRouteRegistry $registry,
		private readonly ParserSourceResolver $sourceResolver,
		private readonly ParserPlanner $planner
	) {}

	public function explore(?ParserExploreRequest $request = null): ParserCapabilityReport {
		$request ??= new ParserExploreRequest();
		$routes = $this->registry->getRoutes();
		$parsers = [];
		$states = [];
		$outputs = [];
		$routeRows = [];

		foreach ($this->registry->getParsers() as $name => $parser) {
			$parsers[$name] = [
				'name' => $name,
				'class' => $parser::class,
				'routeCount' => count($parser->getRoutes()),
			];
		}

		foreach ($routes as $route) {
			$states[$route->from->getKey()] = $this->stateRow($route->from);
			$states[$route->to->getKey()] = $this->stateRow($route->to);
			$outputs[$route->to->type . ':' . ($route->to->format ?? '*')] = [
				'type' => $route->to->type,
				'format' => $route->to->format,
			];

			if ($request->includeRoutes) {
				$routeRows[] = $this->routeRow($route);
			}
		}

		$suggestedPlan = null;
		$warnings = [];
		if ($request->request !== null && $request->includePlanSuggestion) {
			try {
				$inspection = $this->sourceResolver->resolve($request->request->source, $request->request->input);
				$suggestedPlan = $this->planner->plan($request->request, $inspection->payloads, $routes, $request->request->strategy ?? ParserStrategy::balanced());
			} catch (\Throwable $e) {
				$warnings[] = $e->getMessage();
			}
		}

		ksort($parsers);
		ksort($states);
		ksort($outputs);
		return new ParserCapabilityReport(
			parsers: $request->includeParserDetails ? array_values($parsers) : array_keys($parsers),
			routes: $routeRows,
			states: $request->includeStates ? array_values($states) : [],
			outputs: array_values($outputs),
			suggestedPlan: $suggestedPlan,
			warnings: $warnings,
			metadata: ['routeCount' => count($routes), 'parserCount' => count($parsers)]
		);
	}

	private function stateRow(ParserState $state): array {
		return [
			'key' => $state->getKey(),
			'type' => $state->type,
			'format' => $state->format,
			'mimeType' => $state->mimeType,
			'extension' => $state->extension,
			'features' => $state->features,
		];
	}

	private function routeRow(ParserRoute $route): array {
		return [
			'parserName' => $route->parserName,
			'routeName' => $route->routeName,
			'from' => $this->stateRow($route->from),
			'to' => $this->stateRow($route->to),
			'quality' => [
				'textQuality' => $route->quality->textQuality,
				'structureQuality' => $route->quality->structureQuality,
				'layoutQuality' => $route->quality->layoutQuality,
				'tableQuality' => $route->quality->tableQuality,
				'imageQuality' => $route->quality->imageQuality,
				'semanticQuality' => $route->quality->semanticQuality,
				'speed' => $route->quality->speed,
				'stability' => $route->quality->stability,
				'monetaryCost' => $route->quality->monetaryCost,
				'lossy' => $route->quality->lossy,
				'requiresExternalService' => $route->quality->requiresExternalService,
				'priority' => $route->quality->priority,
			],
			'features' => $route->features,
			'requirements' => $route->requirements,
		];
	}
}
