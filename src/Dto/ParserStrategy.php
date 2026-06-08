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
 * Defines how parser plans are selected.
 */
class ParserStrategy {

	public function __construct(
		public readonly string $mode = 'balanced',
		public readonly int $maxSteps = 6,
		public readonly bool $allowLossy = true,
		public readonly bool $allowExternalServices = false,
		public readonly array $requiredFeatures = [],
		public readonly array $allowedParsers = [],
		public readonly array $disabledParsers = [],
		public readonly float $textWeight = 1.0,
		public readonly float $structureWeight = 1.0,
		public readonly float $layoutWeight = 1.0,
		public readonly float $tableWeight = 1.0,
		public readonly float $semanticWeight = 1.0,
		public readonly float $stabilityWeight = 1.0,
		public readonly float $speedWeight = 0.3,
		public readonly float $monetaryCostWeight = 1.0,
		public readonly float $stepPenalty = 0.08,
		public readonly float $lossyPenalty = 0.5,
		public readonly float $externalServicePenalty = 0.7
	) {}

	public static function balanced(): self { return new self(); }
	public static function bestQuality(): self { return new self(mode: 'best_quality', speedWeight: 0.1, stepPenalty: 0.04); }
	public static function fastest(): self { return new self(mode: 'fastest', speedWeight: 1.2, stepPenalty: 0.15); }
	public static function localOnly(): self { return new self(mode: 'local_only', allowExternalServices: false); }
}
