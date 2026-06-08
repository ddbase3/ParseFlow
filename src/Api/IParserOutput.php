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
 * Describes the desired semantic output representation.
 */
interface IParserOutput {

	/**
	 * Returns the output type, e.g. string, structured, files, chunks, image or binary.
	 */
	public function getType(): string;

	/**
	 * Returns the output format, e.g. text, markdown, json, xml, csv or png.
	 */
	public function getFormat(): ?string;

	/**
	 * Returns output options.
	 */
	public function getOptions(): array;
}
