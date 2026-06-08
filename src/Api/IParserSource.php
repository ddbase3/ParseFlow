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

namespace ParseFlow\Api;

/**
 * Describes where original parser data comes from.
 */
interface IParserSource {

	/**
	 * Returns the source type, e.g. file, stream, string or binary.
	 */
	public function getType(): string;

	/**
	 * Returns source metadata, e.g. filename, mime type or size.
	 */
	public function getMetadata(): array;
}
