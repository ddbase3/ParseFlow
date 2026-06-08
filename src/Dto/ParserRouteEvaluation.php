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
 * Describes whether and how well a route supports one planning context.
 */
class ParserRouteEvaluation {

	public function __construct(
		public readonly bool $supported,
		public readonly ParserRouteQuality $quality,
		public readonly float $confidence = 1.0,
		public readonly array $metadata = [],
		public readonly array $warnings = []
	) {}

	public static function supported(ParserRouteQuality $quality, float $confidence = 1.0, array $metadata = [], array $warnings = []): self {
		return new self(true, $quality, $confidence, $metadata, $warnings);
	}

	public static function unsupported(?ParserRouteQuality $quality = null, array $warnings = []): self {
		return new self(false, $quality ?? new ParserRouteQuality(), 0.0, [], $warnings);
	}
}
