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
 * Describes one directed parser transformation route.
 */
class ParserRoute {

	public function __construct(
		public readonly string $parserName,
		public readonly string $routeName,
		public readonly ParserState $from,
		public readonly ParserState $to,
		public readonly ParserRouteQuality $quality,
		public readonly array $features = [],
		public readonly array $requirements = [],
		public readonly array $options = []
	) {}

	public function getKey(): string {
		return $this->parserName . ':' . $this->routeName;
	}
}
