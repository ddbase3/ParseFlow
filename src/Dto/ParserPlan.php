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
 * Describes the selected parser path.
 */
class ParserPlan {

	/**
	 * @param ParserPlanStep[] $steps
	 */
	public function __construct(
		public readonly ParserState $startState,
		public readonly ParserState $targetState,
		public readonly array $steps,
		public readonly float $totalCost = 0.0,
		public readonly array $warnings = [],
		public readonly array $metadata = []
	) {}

	/**
	 * @return ParserPlanStep[]
	 */
	public function getSteps(): array {
		return $this->steps;
	}

	public function isEmpty(): bool {
		return count($this->steps) === 0;
	}
}
