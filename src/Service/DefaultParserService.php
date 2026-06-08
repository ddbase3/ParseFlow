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

use Base3\Api\IClassMap;
use ParseFlow\Api\IParserService;
use ParseFlow\Dto\ParserCapabilityReport;
use ParseFlow\Dto\ParserExploreRequest;
use ParseFlow\Dto\ParserPlan;
use ParseFlow\Dto\ParserRequest;
use ParseFlow\Dto\ParserResult;
use ParseFlow\Dto\ParserStrategy;

/**
 * Default parser service implementation.
 */
class DefaultParserService implements IParserService {

	private readonly ParserRouteRegistry $registry;
	private readonly ParserSourceResolver $sourceResolver;
	private readonly ParserPlanner $planner;
	private readonly ParserPlanExecutor $executor;
	private readonly ParserTargetWriter $targetWriter;
	private readonly ParserCapabilityExplorer $explorer;

	public function __construct(IClassMap $classMap) {
		$this->registry = new ParserRouteRegistry($classMap);
		$this->sourceResolver = new ParserSourceResolver();
		$matcher = new ParserStateMatcher();
		$scoreCalculator = new ParserScoreCalculator();
		$this->planner = new ParserPlanner($this->registry, $matcher, $scoreCalculator);
		$this->executor = new ParserPlanExecutor($this->registry, new ParserTempResourceManager());
		$this->targetWriter = new ParserTargetWriter();
		$this->explorer = new ParserCapabilityExplorer($this->registry, $this->sourceResolver, $this->planner);
	}

	public function parse(ParserRequest $request): ParserResult {
		$strategy = $request->strategy ?? ParserStrategy::balanced();
		$inspection = $this->sourceResolver->resolve($request->source, $request->input);
		$routes = $this->registry->getRoutes();
		$plan = $this->planner->plan($request, $inspection->payloads, $routes, $strategy);
		$payload = $this->executor->execute($request, $plan, $inspection->payloads);
		$targetResult = $this->targetWriter->write($payload, $request->target);

		return new ParserResult(true, $plan, $payload, $targetResult, $plan->warnings, ['source' => $inspection->metadata]);
	}

	public function plan(ParserRequest $request): ParserPlan {
		$strategy = $request->strategy ?? ParserStrategy::balanced();
		$inspection = $this->sourceResolver->resolve($request->source, $request->input);
		return $this->planner->plan($request, $inspection->payloads, $this->registry->getRoutes(), $strategy);
	}

	public function supports(ParserRequest $request): bool {
		try {
			$this->plan($request);
			return true;
		} catch (\Throwable) {
			return false;
		}
	}

	public function listRoutes(): array {
		return $this->registry->getRoutes();
	}

	public function explore(?ParserExploreRequest $request = null): ParserCapabilityReport {
		return $this->explorer->explore($request);
	}
}
