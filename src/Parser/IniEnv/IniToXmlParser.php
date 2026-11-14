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

namespace Base3\ParseFlow\Parser\IniEnv;

use Base3\ParseFlow\Dto\ParserRouteQuality;
use Base3\ParseFlow\Dto\ParserStepRequest;
use Base3\ParseFlow\Parser\AbstractSingleRouteParser;
use Base3\ParseFlow\Parser\IniEnv\IniEnvParserHelper;

class IniToXmlParser extends AbstractSingleRouteParser {

	protected function fromType(): string { return 'structured'; }
	protected function fromFormat(): ?string { return 'ini'; }
	protected function toType(): string { return 'structured'; }
	protected function toFormat(): ?string { return 'xml'; }

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
		return \Base3\ParseFlow\Parser\IniEnv\IniEnvParserHelper::iniToXml($value);
	}
}
