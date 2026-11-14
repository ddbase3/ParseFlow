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

namespace Base3\ParseFlow\Parser\Core;

use Base3\ParseFlow\Dto\ParserStepRequest;
use Base3\ParseFlow\Parser\AbstractSingleRouteParser;

class PlainTextParser extends AbstractSingleRouteParser {

	protected function fromType(): string { return 'string'; }
	protected function fromFormat(): ?string { return 'text'; }
	protected function toType(): string { return 'string'; }
	protected function toFormat(): ?string { return 'text'; }

	protected function transform(mixed $value, ParserStepRequest $request): mixed {
		return $value;
	}
}
