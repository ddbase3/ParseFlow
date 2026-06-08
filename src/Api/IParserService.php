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

namespace ParseFlow\Api;

use ParseFlow\Dto\ParserCapabilityReport;
use ParseFlow\Dto\ParserExploreRequest;
use ParseFlow\Dto\ParserPlan;
use ParseFlow\Dto\ParserRequest;
use ParseFlow\Dto\ParserResult;
use ParseFlow\Dto\ParserRoute;

/**
 * Central parser service API used by application services through DI.
 */
interface IParserService {

	/**
	 * Parses the request by planning and executing the best available route.
	 */
	public function parse(ParserRequest $request): ParserResult;

	/**
	 * Builds a parser plan without executing it.
	 */
	public function plan(ParserRequest $request): ParserPlan;

	/**
	 * Checks whether at least one executable plan exists.
	 */
	public function supports(ParserRequest $request): bool;

	/**
	 * Returns all registered routes from available parsers.
	 *
	 * @return ParserRoute[]
	 */
	public function listRoutes(): array;

	/**
	 * Explores registered parser capabilities and optionally suggests a plan.
	 */
	public function explore(?ParserExploreRequest $request = null): ParserCapabilityReport;
}
