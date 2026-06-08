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
 * Describes route quality and execution characteristics.
 */
class ParserRouteQuality {

	public function __construct(
		public readonly float $textQuality = 1.0,
		public readonly float $structureQuality = 1.0,
		public readonly float $layoutQuality = 1.0,
		public readonly float $tableQuality = 1.0,
		public readonly float $imageQuality = 1.0,
		public readonly float $semanticQuality = 1.0,
		public readonly float $speed = 1.0,
		public readonly float $stability = 1.0,
		public readonly float $monetaryCost = 0.0,
		public readonly bool $lossy = false,
		public readonly bool $requiresExternalService = false,
		public readonly int $priority = 0
	) {}
}
