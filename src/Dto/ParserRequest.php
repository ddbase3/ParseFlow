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

use ParseFlow\Api\IParserInput;
use ParseFlow\Api\IParserOutput;
use ParseFlow\Api\IParserSource;
use ParseFlow\Api\IParserTarget;

/**
 * Describes a complete parser request from source to target.
 */
class ParserRequest {

	public function __construct(
		public readonly IParserSource $source,
		public readonly IParserInput $input,
		public readonly IParserOutput $output,
		public readonly IParserTarget $target,
		public readonly ?ParserStrategy $strategy = null,
		public readonly array $options = [],
		public readonly array $metadata = [],
		public readonly ?string $parserName = null
	) {}
}
