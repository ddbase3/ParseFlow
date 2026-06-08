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
 * Carries one concrete intermediate value through the parser graph.
 */
class ParserPayload {

	public function __construct(
		public readonly ParserState $state,
		private readonly mixed $value,
		public readonly array $metadata = [],
		public readonly array $temporaryResources = []
	) {}

	public function getValue(): mixed {
		return $this->value;
	}

	public function withState(ParserState $state): self {
		return new self($state, $this->value, $this->metadata, $this->temporaryResources);
	}
}
