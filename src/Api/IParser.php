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

use Base3\Api\IBase;
use ParseFlow\Dto\ParserPlanningContext;
use ParseFlow\Dto\ParserRoute;
use ParseFlow\Dto\ParserRouteEvaluation;
use ParseFlow\Dto\ParserStepRequest;
use ParseFlow\Dto\ParserStepResult;

/**
 * Parser implementations provide one or more directed transformation routes.
 */
interface IParser extends IBase {

	/**
	 * Returns all transformation routes provided by this parser.
	 *
	 * @return ParserRoute[]
	 */
	public function getRoutes(): array;

	/**
	 * Evaluates a route for the current planning context.
	 */
	public function evaluate(ParserRoute $route, ParserPlanningContext $context): ParserRouteEvaluation;

	/**
	 * Executes one planned route step.
	 */
	public function execute(ParserStepRequest $request): ParserStepResult;
}
