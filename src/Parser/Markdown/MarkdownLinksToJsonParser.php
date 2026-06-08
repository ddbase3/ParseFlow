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

namespace ParseFlow\Parser\Markdown;

use ParseFlow\Dto\ParserRouteQuality;
use ParseFlow\Dto\ParserStepRequest;
use ParseFlow\Parser\AbstractSingleRouteParser;
use ParseFlow\Parser\Markdown\MarkdownParserHelper;

class MarkdownLinksToJsonParser extends AbstractSingleRouteParser {

	protected function fromType(): string { return 'string'; }
	protected function fromFormat(): ?string { return 'markdown'; }
	protected function toType(): string { return 'structured'; }
	protected function toFormat(): ?string { return 'json'; }

	protected function quality(): ParserRouteQuality {
		return new ParserRouteQuality(priority: 20);
	}

	protected function transform(mixed $value, ParserStepRequest $request): mixed {
		return \ParseFlow\Parser\Markdown\MarkdownParserHelper::linksToJson($value);
	}
}
