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

namespace ParseFlow\Service;

use ParseFlow\Dto\ParserRoute;
use ParseFlow\Dto\ParserRouteEvaluation;
use ParseFlow\Dto\ParserRouteQuality;
use ParseFlow\Dto\ParserStrategy;

/**
 * Converts route quality into non-negative additive route costs.
 */
class ParserScoreCalculator {

	public function calculate(ParserRoute $route, ParserRouteEvaluation $evaluation, ParserStrategy $strategy): float {
		$quality = $evaluation->quality;
		$combinedQuality = $this->combinedQuality($quality, $strategy) * max(0.01, $evaluation->confidence);
		$qualityCost = -log(max(0.01, $combinedQuality));
		$speedCost = (1.0 - max(0.0, min(1.0, $quality->speed))) * $strategy->speedWeight;
		$moneyCost = $quality->monetaryCost * $strategy->monetaryCostWeight;
		$lossyCost = $quality->lossy ? $strategy->lossyPenalty : 0.0;
		$externalCost = $quality->requiresExternalService ? $strategy->externalServicePenalty : 0.0;
		$priorityBonus = min(0.2, max(0, $quality->priority) / 1000);

		return max(0.0, $qualityCost + $speedCost + $moneyCost + $strategy->stepPenalty + $lossyCost + $externalCost - $priorityBonus);
	}

	public function combinedQuality(ParserRouteQuality $quality, ParserStrategy $strategy): float {
		$weighted = [
			[$quality->textQuality, $strategy->textWeight],
			[$quality->structureQuality, $strategy->structureWeight],
			[$quality->layoutQuality, $strategy->layoutWeight],
			[$quality->tableQuality, $strategy->tableWeight],
			[$quality->semanticQuality, $strategy->semanticWeight],
			[$quality->stability, $strategy->stabilityWeight],
		];

		$total = 0.0;
		$weightSum = 0.0;
		foreach ($weighted as [$value, $weight]) {
			$total += max(0.01, min(1.0, $value)) * $weight;
			$weightSum += $weight;
		}

		return $weightSum > 0 ? $total / $weightSum : 1.0;
	}

	public function violatesConstraints(ParserRoute $route, ParserRouteEvaluation $evaluation, ParserStrategy $strategy): bool {
		$quality = $evaluation->quality;
		if (!$evaluation->supported) { return true; }
		if (!$strategy->allowLossy && $quality->lossy) { return true; }
		if (!$strategy->allowExternalServices && $quality->requiresExternalService) { return true; }
		if ($strategy->allowedParsers && !in_array($route->parserName, $strategy->allowedParsers, true)) { return true; }
		if (in_array($route->parserName, $strategy->disabledParsers, true)) { return true; }

		foreach ($strategy->requiredFeatures as $feature) {
			if (!in_array($feature, $route->features, true)) {
				return true;
			}
		}

		return false;
	}
}
