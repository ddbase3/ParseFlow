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

namespace ParseFlow\Source;

use ParseFlow\Api\IParserSource;

class FileParserSource implements IParserSource {

	public function __construct(private readonly string $path, private readonly array $metadata = []) {}

	public function getType(): string { return 'file'; }
	public function getPath(): string { return $this->path; }
	public function getMetadata(): array { return $this->metadata + ['path' => $this->path]; }
}
