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

namespace ParseFlow\Dto;

/**
 * One selected parser route inside a parser plan.
 */
class ParserPlanStep {

	public function __construct(
		public readonly string $parserName,
		public readonly ParserRoute $route,
		public readonly ParserState $from,
		public readonly ParserState $to,
		public readonly float $cost,
		public readonly ParserRouteEvaluation $evaluation
	) {}
}
