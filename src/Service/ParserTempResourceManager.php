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

/**
 * Tracks temporary resources created during parser execution.
 */
class ParserTempResourceManager {

	private array $resources = [];

	public function register(string $path): void {
		$this->resources[$path] = $path;
	}

	public function createTempFile(string $prefix = 'parseflow_'): string {
		$path = tempnam(sys_get_temp_dir(), $prefix);
		if ($path !== false) {
			$this->register($path);
			return $path;
		}

		throw new \RuntimeException('Could not create temporary file.');
	}

	public function cleanup(): void {
		foreach ($this->resources as $path) {
			if (is_file($path)) {
				@unlink($path);
			}
		}
		$this->resources = [];
	}
}
