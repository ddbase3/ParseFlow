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

namespace ParseFlow\Parser\Html;

use ParseFlow\Dto\ParserRouteQuality;
use ParseFlow\Dto\ParserStepRequest;
use ParseFlow\Parser\AbstractSingleRouteParser;
use ParseFlow\Parser\Html\HtmlParserHelper;

class HtmlCodeBlocksToFilesParser extends AbstractSingleRouteParser {

	protected function fromType(): string { return 'document'; }
	protected function fromFormat(): ?string { return 'html'; }
	protected function toType(): string { return 'files'; }
	protected function toFormat(): ?string { return 'text'; }

	protected function quality(): ParserRouteQuality {
		return new ParserRouteQuality(priority: 20);
	}

	protected function requirements(): array {
		return ['dom'];
	}

	protected function isAvailable(): bool {
		return class_exists('DOMDocument');
	}

	protected function unavailableReason(): string {
		return static::class . ' requires: ' . implode(', ', $this->requirements());
	}

	protected function transform(mixed $value, ParserStepRequest $request): mixed {
		return \ParseFlow\Parser\Html\HtmlParserHelper::codeBlocksToFiles($value);
	}
}
