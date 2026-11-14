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

namespace Base3\ParseFlow\Service;

use Base3\ParseFlow\Dto\ParserPayload;
use Base3\ParseFlow\Dto\ParserPlan;
use Base3\ParseFlow\Dto\ParserRequest;
use Base3\ParseFlow\Dto\ParserStepRequest;
use Base3\ParseFlow\Exception\ParserExecutionException;

/**
 * Executes planned parser steps and returns the final payload.
 */
class ParserPlanExecutor {

	public function __construct(
		private readonly ParserRouteRegistry $registry,
		private readonly ParserTempResourceManager $tempResourceManager
	) {}

	/**
	 * @param ParserPayload[] $initialPayloads
	 */
	public function execute(ParserRequest $request, ParserPlan $plan, array $initialPayloads): ParserPayload {
		$payload = $this->findPayloadForPlan($plan, $initialPayloads);

		try {
			foreach ($plan->steps as $step) {
				$parser = $this->registry->getParser($step->parserName);
				if ($parser === null) {
					throw new ParserExecutionException('Parser not found: ' . $step->parserName);
				}

				$result = $parser->execute(new ParserStepRequest($request, $step, $payload, $request->options));
				$payload = $result->payload;
				foreach ($result->temporaryResources as $resource) {
					$this->tempResourceManager->register($resource);
				}
			}

			return $payload;
		} finally {
			if (!($request->options['keepTemporaryResources'] ?? false)) {
				$this->tempResourceManager->cleanup();
			}
		}
	}

	/**
	 * @param ParserPayload[] $payloads
	 */
	private function findPayloadForPlan(ParserPlan $plan, array $payloads): ParserPayload {
		foreach ($payloads as $payload) {
			if ($payload->state->getKey() === $plan->startState->getKey()) {
				return $payload;
			}
		}

		throw new ParserExecutionException('Initial payload for plan start state not found.');
	}
}
