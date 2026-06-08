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
 * Provides current request and payload context to route evaluation.
 */
class ParserPlanningContext {

	public function __construct(
		public readonly ParserRequest $request,
		public readonly ParserPayload $payload,
		public readonly ParserStrategy $strategy,
		public readonly array $metadata = []
	) {}
}
