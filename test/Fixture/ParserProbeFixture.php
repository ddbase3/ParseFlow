<?php declare(strict_types=1);

namespace ParseFlow\Test\Fixture;

use ParseFlow\Api\IParser;
use ParseFlow\Dto\ParserPayload;
use ParseFlow\Dto\ParserPlanningContext;
use ParseFlow\Dto\ParserRoute;
use ParseFlow\Dto\ParserRouteEvaluation;
use ParseFlow\Dto\ParserStepRequest;
use ParseFlow\Dto\ParserStepResult;
use ParseFlow\Exception\ParserExecutionException;

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
