<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of ParseFlow for BASE3 Framework.
 *
 * ParseFlow supplies graph-based parser services.
 * It discovers parser capabilities and plans conversion routes.
 * The parser workflow runs through existing module contracts.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 ***********************************************************************/


namespace Base3\ParseFlow\Test\Fixture;

use Base3\ParseFlow\Api\IParser;
use Base3\ParseFlow\Dto\ParserPayload;
use Base3\ParseFlow\Dto\ParserPlanningContext;
use Base3\ParseFlow\Dto\ParserRoute;
use Base3\ParseFlow\Dto\ParserRouteEvaluation;
use Base3\ParseFlow\Dto\ParserStepRequest;
use Base3\ParseFlow\Dto\ParserStepResult;
use Base3\ParseFlow\Exception\ParserExecutionException;

class ParserProbeFixture implements IParser {

	public function __construct(
		private readonly array $routes,
		public bool $supported = true,
		public bool $fail = false,
		public ?string $temporaryResource = null
	) {}

	public static function getName(): string { return 'parserprobefixture'; }
	public function getRoutes(): array { return $this->routes; }
	public function evaluate(ParserRoute $route, ParserPlanningContext $context): ParserRouteEvaluation {
		return $this->supported ? ParserRouteEvaluation::supported($route->quality) : ParserRouteEvaluation::unsupported($route->quality, ['not available']);
	}
	public function execute(ParserStepRequest $request): ParserStepResult {
		if ($this->fail) { throw new ParserExecutionException('fixture failed'); }
		return new ParserStepResult(
			new ParserPayload($request->step->to, 'parsed:' . (string)$request->payload->getValue(), $request->payload->metadata),
			temporaryResources: $this->temporaryResource === null ? [] : [$this->temporaryResource]
		);
	}
}
