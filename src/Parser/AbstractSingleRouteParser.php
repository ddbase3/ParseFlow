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

namespace ParseFlow\Parser;

use ParseFlow\Api\IParser;
use ParseFlow\Dto\ParserPayload;
use ParseFlow\Dto\ParserPlanningContext;
use ParseFlow\Dto\ParserRoute;
use ParseFlow\Dto\ParserRouteEvaluation;
use ParseFlow\Dto\ParserRouteQuality;
use ParseFlow\Dto\ParserState;
use ParseFlow\Dto\ParserStepRequest;
use ParseFlow\Dto\ParserStepResult;
use ParseFlow\Exception\ParserExecutionException;

/**
 * Base class for simple local parsers that expose exactly one route.
 */
abstract class AbstractSingleRouteParser implements IParser {

	public static function getName(): string {
		$parts = explode('\\', static::class);
		return strtolower((string)end($parts));
	}

	public function getRoutes(): array {
		return [
			new ParserRoute(
				parserName: static::getName(),
				routeName: static::getName(),
				from: new ParserState($this->fromType(), $this->fromFormat()),
				to: new ParserState($this->toType(), $this->toFormat()),
				quality: $this->quality(),
				features: $this->features(),
				requirements: $this->requirements()
			)
		];
	}

	public function evaluate(ParserRoute $route, ParserPlanningContext $context): ParserRouteEvaluation {
		if (!$this->isAvailable()) {
			return ParserRouteEvaluation::unsupported($route->quality, [$this->unavailableReason()]);
		}

		return ParserRouteEvaluation::supported($route->quality);
	}

	public function execute(ParserStepRequest $request): ParserStepResult {
		if (!$this->isAvailable()) {
			throw new ParserExecutionException($this->unavailableReason());
		}

		$value = $this->transform($request->payload->getValue(), $request);
		$payload = new ParserPayload($request->step->to, $value, $request->payload->metadata);
		return new ParserStepResult($payload);
	}

	abstract protected function fromType(): string;
	abstract protected function fromFormat(): ?string;
	abstract protected function toType(): string;
	abstract protected function toFormat(): ?string;
	abstract protected function transform(mixed $value, ParserStepRequest $request): mixed;

	protected function quality(): ParserRouteQuality {
		return new ParserRouteQuality(priority: 10);
	}

	protected function features(): array {
		return ['local', 'php'];
	}

	protected function requirements(): array {
		return [];
	}

	protected function isAvailable(): bool {
		return true;
	}

	protected function unavailableReason(): string {
		return static::class . ' is not available in this PHP runtime.';
	}
}
