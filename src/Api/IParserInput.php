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
 * Describes how a source should be interpreted.
 */
interface IParserInput {

	/**
	 * Returns the input type, e.g. auto, document, image, string or structured.
	 */
	public function getType(): string;

	/**
	 * Returns the expected format or null for auto detection.
	 */
	public function getFormat(): ?string;

	/**
	 * Returns input options.
	 */
	public function getOptions(): array;
}
