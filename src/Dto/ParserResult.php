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
 * Final parser result including plan, payload and target information.
 */
class ParserResult {

	public function __construct(
		public readonly bool $success,
		public readonly ParserPlan $plan,
		private readonly ParserPayload $payload,
		public readonly mixed $targetResult = null,
		public readonly array $warnings = [],
		public readonly array $metadata = []
	) {}

	public function isSuccess(): bool {
		return $this->success;
	}

	public function getPayload(): ParserPayload {
		return $this->payload;
	}

	public function getPlan(): ParserPlan {
		return $this->plan;
	}
}
