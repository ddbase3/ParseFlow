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
 * Describes one semantic representation state in the parser graph.
 */
class ParserState {

	public function __construct(
		public readonly string $type,
		public readonly ?string $format = null,
		public readonly ?string $mimeType = null,
		public readonly ?string $extension = null,
		public readonly array $features = [],
		public readonly array $metadata = []
	) {}

	/**
	 * Returns a stable key for graph indexing.
	 */
	public function getKey(): string {
		return implode('|', [
			$this->type,
			$this->format ?? '*',
			$this->mimeType ?? '*',
			$this->extension ?? '*'
		]);
	}
}
