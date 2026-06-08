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

namespace ParseFlow\Parser\Image;

use ParseFlow\Dto\ParserRouteQuality;
use ParseFlow\Dto\ParserStepRequest;
use ParseFlow\Parser\AbstractSingleRouteParser;
use ParseFlow\Parser\Image\ImageParserHelper;

class PngToWebpParser extends AbstractSingleRouteParser {

	protected function fromType(): string { return 'image'; }
	protected function fromFormat(): ?string { return 'png'; }
	protected function toType(): string { return 'image'; }
	protected function toFormat(): ?string { return 'webp'; }

	protected function quality(): ParserRouteQuality {
		return new ParserRouteQuality(priority: 20);
	}

	protected function requirements(): array {
		return ['gd', 'webp'];
	}

	protected function isAvailable(): bool {
		return function_exists('imagecreatefromstring') && function_exists('imagewebp');
	}

	protected function unavailableReason(): string {
		return static::class . ' requires: ' . implode(', ', $this->requirements());
	}

	protected function transform(mixed $value, ParserStepRequest $request): mixed {
		return \ParseFlow\Parser\Image\ImageParserHelper::pngToWebp($value);
	}
}
