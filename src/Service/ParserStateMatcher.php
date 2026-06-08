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

use ParseFlow\Dto\ParserState;

/**
 * Matches parser states conservatively and deterministically.
 */
class ParserStateMatcher {

	public function matches(ParserState $actual, ParserState $expected, array $metadata = []): bool {
		return $this->matchesValue($actual->type, $expected->type, false)
			&& $this->matchesValue($actual->format, $expected->format, true)
			&& $this->matchesValue($actual->mimeType, $expected->mimeType, true)
			&& $this->matchesValue($actual->extension, $expected->extension, true)
			&& $this->matchesFeatures($actual, $expected, $metadata);
	}

	private function matchesValue(?string $actual, ?string $expected, bool $nullIsWildcard): bool {
		if ($expected === '*' || $expected === 'any') {
			return true;
		}

		if ($expected === null) {
			return $nullIsWildcard;
		}

		return $actual === $expected;
	}

	private function matchesFeatures(ParserState $actual, ParserState $expected, array $metadata): bool {
		$available = array_fill_keys($actual->features, true);
		foreach (($metadata['features'] ?? []) as $feature) {
			$available[$feature] = true;
		}

		foreach ($expected->features as $feature) {
			if (!isset($available[$feature])) {
				return false;
			}
		}

		return true;
	}
}
