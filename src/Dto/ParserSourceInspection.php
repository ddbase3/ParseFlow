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
 * Result of resolving and inspecting one source.
 */
class ParserSourceInspection {

	/**
	 * @param ParserPayload[] $payloads
	 */
	public function __construct(
		public readonly array $payloads,
		public readonly ?string $mimeType = null,
		public readonly ?string $extension = null,
		public readonly ?int $size = null,
		public readonly array $metadata = []
	) {}
}
