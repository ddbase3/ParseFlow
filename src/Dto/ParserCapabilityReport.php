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
 * Human and machine readable description of registered parse capabilities.
 */
class ParserCapabilityReport {

	public function __construct(
		public readonly array $parsers = [],
		public readonly array $routes = [],
		public readonly array $states = [],
		public readonly array $outputs = [],
		public readonly ?ParserPlan $suggestedPlan = null,
		public readonly array $warnings = [],
		public readonly array $metadata = []
	) {}
}
