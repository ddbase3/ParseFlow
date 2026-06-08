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

namespace ParseFlow\Target;

use ParseFlow\Api\IParserTarget;

class StreamParserTarget implements IParserTarget {

	public function __construct(private readonly mixed $stream, private readonly array $options = []) {}

	public function getType(): string { return 'stream'; }
	public function getStream(): mixed { return $this->stream; }
	public function getOptions(): array { return $this->options; }
}
