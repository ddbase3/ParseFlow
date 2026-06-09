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

namespace ParseFlow\Display;

use Base3\Api\IAssetResolver;
use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\LinkTarget\Api\ILinkTargetService;
use ParseFlow\Api\IParserService;
use ParseFlow\Dto\ParserCapabilityReport;
use ParseFlow\Dto\ParserRoute;
use ParseFlow\Dto\ParserRouteEvaluation;
use ParseFlow\Dto\ParserState;
use ParseFlow\Dto\ParserStrategy;
use ParseFlow\Service\ParserScoreCalculator;
use ParseFlow\Service\ParserStateMatcher;
use Throwable;

/**
 * ModularGrid based administration display for exploring selected parser conversions.
 */
final class ParserExplorerAdminDisplay implements IDisplay {

	private const DEFAULT_PAGE_SIZE = 40;
	private const MAX_PAGE_SIZE = 100;
	private const DEFAULT_MAX_STEPS = 4;
	private const MAX_TOP_PLANS = 60;
	private const SORTED_TOP_PLAN_LIMIT = 80;
	private const MAX_NODE_EXPANSIONS = 450;
	private const MAX_PATHS_PER_STATE = 2;
	private const DEFAULT_SEARCH_DEBOUNCE_MS = 700;
	private const MAX_OUTGOING_ROUTES_PER_STATE = 10;
	private const MAX_START_BRANCH_ROUTES = 12;

	private ?ParserCapabilityReport $capabilityReport = null;
	private ?array $routes = null;
	private ?array $routeMap = null;
	private ?array $routeIndex = null;
	private ?array $defaultStatePair = null;
	private readonly ParserStateMatcher $matcher;
	private readonly ParserScoreCalculator $scoreCalculator;

	public function __construct(
		private readonly IRequest $request,
		private readonly IMvcView $view,
		private readonly IAssetResolver $assetResolver,
		private readonly ILinkTargetService $linkTargetService,
		private readonly IParserService $parserService
	) {
		$this->matcher = new ParserStateMatcher();
		$this->scoreCalculator = new ParserScoreCalculator();
	}

	public static function getName(): string {
		return 'parserexploreradmindisplay';
	}

	public function setData($data) {
		// no-op
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		$out = strtolower((string) $out);

		if($out === 'json') {
			return $this->handleJson($final);
		}

		return $this->handleHtml();
	}

	private function handleHtml(): string {
		$this->view->setPath(DIR_PLUGIN . 'ParseFlow');
		$this->view->setTemplate('Display/ParserExplorerAdminDisplay.php');

		$this->view->assign(
			'service',
			$this->linkTargetService->getLink(
				[
					'name' => self::getName(),
					'out' => 'json'
				]
			)
		);

		$initialRequest = $this->normalizeRequest([
			'mode' => 'page',
			'page' => 1,
			'pageSize' => self::DEFAULT_PAGE_SIZE,
			'filters' => $this->getDefaultFilters()
		]);

		$this->view->assign('resolve', fn($src) => $this->assetResolver->resolve((string) $src));
		$this->view->assign('summary', $this->buildSummaryPayload());
		$this->view->assign('options', $this->buildOptionsPayload());
		$this->view->assign('initialPage', $this->buildPageResponse($initialRequest));
		$this->view->assign('searchDebounceMs', self::DEFAULT_SEARCH_DEBOUNCE_MS);

		return $this->view->loadTemplate();
	}

	private function handleJson(bool $final = false): string {
		$response = $this->buildJsonResponse();

		if($final && !headers_sent()) {
			header('Content-Type: application/json; charset=utf-8');
		}

		return (string) json_encode(
			$response,
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private function buildJsonResponse(): array {
		$request = $this->normalizeRequest($this->readRequestPayload());

		return match ($request['mode']) {
			'options' => $this->buildOptionsPayload(),
			'summary' => $this->buildSummaryResponse(),
			'detail' => $this->buildDetailResponse($request),
			default => $this->buildPageResponse($request)
		};
	}

	/**
	 * @return array<string,mixed>
	 */
	private function readRequestPayload(): array {
		$payload = $this->request->getJsonBody();

		if(is_array($payload) && count($payload) > 0) {
			return $payload;
		}

		$rawPayload = $this->decodeJsonPayload((string) file_get_contents('php://input'));

		if(count($rawPayload) > 0) {
			return $rawPayload;
		}

		if(isset($_POST['payload'])) {
			$postPayload = $this->decodeJsonPayload((string) $_POST['payload']);

			if(count($postPayload) > 0) {
				return $postPayload;
			}
		}

		if(count($_POST) > 0) {
			return $_POST;
		}

		return [];
	}

	/**
	 * @return array<string,mixed>
	 */
	private function decodeJsonPayload(string $json): array {
		$json = trim($json);

		if($json === '') {
			return [];
		}

		$payload = json_decode($json, true);
		return is_array($payload) ? $payload : [];
	}

	/**
	 * @param array<string,mixed> $payload
	 * @return array<string,mixed>
	 */
	private function normalizeRequest(array $payload): array {
		$filters = $payload['filters'] ?? [];

		if(!is_array($filters)) {
			$filters = [];
		}

		$filters = $this->normalizeFilters($filters);
		$pageSize = max(1, min(self::MAX_PAGE_SIZE, (int) ($payload['pageSize'] ?? self::DEFAULT_PAGE_SIZE)));
		$page = max(1, (int) ($payload['page'] ?? 1));
		$sort = $this->normalizeSort($payload['sort'] ?? []);
		$row = isset($payload['row']) && is_array($payload['row']) ? $payload['row'] : [];
		$search = (string) ($payload['search'] ?? $payload['query'] ?? '');

		if($search === '' && isset($payload['request']) && is_array($payload['request'])) {
			$search = (string) ($payload['request']['search'] ?? $payload['request']['query'] ?? '');
		}

		return [
			'mode' => (string) ($payload['mode'] ?? 'page'),
			'id' => (string) ($payload['id'] ?? ''),
			'row' => $row,
			'page' => $page,
			'pageSize' => $pageSize,
			'search' => trim($search),
			'sort' => $sort,
			'filters' => $filters
		];
	}

	/**
	 * @param array<string,mixed> $filters
	 * @return array<string,string>
	 */
	private function normalizeFilters(array $filters): array {
		if(!isset($filters['input_state'])) {
			$legacyInputType = trim((string) ($filters['input_type'] ?? ''));
			$legacyInputFormat = trim((string) ($filters['input_format'] ?? ''));

			if($legacyInputType !== '') {
				$filters['input_state'] = $legacyInputType . '/' . ($legacyInputFormat !== '' ? $legacyInputFormat : '*');
			}
		}

		if(!isset($filters['output_state'])) {
			$legacyOutputType = trim((string) ($filters['output_type'] ?? ''));
			$legacyOutputFormat = trim((string) ($filters['output_format'] ?? ''));

			if($legacyOutputType !== '') {
				$filters['output_state'] = $legacyOutputType . '/' . ($legacyOutputFormat !== '' ? $legacyOutputFormat : '*');
			}
		}

		$defaults = $this->getDefaultFilters();
		$normalized = [];

		foreach($defaults as $key => $default) {
			$value = $filters[$key] ?? $default;

			if(is_array($value)) {
				$value = reset($value) ?: '';
			}

			$normalized[$key] = trim((string) $value);
		}

		return $normalized;
	}

	/**
	 * @return array<string,string>
	 */
	private function getDefaultFilters(): array {
		$pair = $this->getDefaultStatePair();
		$sourceType = $this->sourceTypeForInputState($pair['input_state']);

		return [
			'source_type' => $sourceType,
			'input_state' => $pair['input_state'],
			'output_state' => $pair['output_state'],
			'target_type' => 'return',
			'strategy' => 'balanced',
			'allow_external' => 'no',
			'allow_lossy' => 'yes',
			'max_steps' => (string) self::DEFAULT_MAX_STEPS,
		];
	}

	/**
	 * @return array{input_state:string,output_state:string}
	 */
	private function getDefaultStatePair(): array {
		if($this->defaultStatePair !== null) {
			return $this->defaultStatePair;
		}

		$routes = $this->getRoutes();
		$inputStates = [];
		$outputStates = [];

		foreach($routes as $route) {
			$inputStates[$this->stateLabel($route->from)] = true;
			$outputStates[$this->stateLabel($route->to)] = true;
		}

		$candidates = [
			['document/pdf', 'string/text'],
			['document/pdf', 'string/markdown'],
			['document/pdf', 'structured/json'],
			['document/docx', 'string/markdown'],
			['string/text', 'string/text'],
			['structured/json', 'structured/json'],
		];

		foreach($candidates as [$inputState, $outputState]) {
			if(!isset($inputStates[$inputState]) || !isset($outputStates[$outputState])) {
				continue;
			}

			if($this->canReachStatePair($inputState, $outputState, self::DEFAULT_MAX_STEPS)) {
				$this->defaultStatePair = [
					'input_state' => $inputState,
					'output_state' => $outputState
				];
				return $this->defaultStatePair;
			}
		}

		$firstRoute = $routes[0] ?? null;

		$this->defaultStatePair = [
			'input_state' => $firstRoute instanceof ParserRoute ? $this->stateLabel($firstRoute->from) : '',
			'output_state' => $firstRoute instanceof ParserRoute ? $this->stateLabel($firstRoute->to) : ''
		];

		return $this->defaultStatePair;
	}

	private function sourceTypeForInputState(string $inputState): string {
		$type = $this->splitState($inputState)['type'] ?? 'string';
		return in_array($type, ['document', 'image', 'files', 'binary'], true) ? 'file' : 'string';
	}

	private function canReachStatePair(string $inputState, string $outputState, int $maxSteps): bool {
		$start = $this->stateFromLabel($inputState);
		$target = $this->stateFromLabel($outputState);

		if($start === null || $target === null) {
			return false;
		}

		$queue = [[$start, 0, [$start->getKey() => true]]];
		$index = $this->getRouteIndex();
		$strategy = new ParserStrategy(maxSteps: $maxSteps, allowLossy: true, allowExternalServices: false);
		$visitedCount = 0;

		while(count($queue) > 0 && $visitedCount < self::MAX_NODE_EXPANSIONS) {
			[$state, $depth, $visited] = array_shift($queue);
			$visitedCount++;

			if($depth > 0 && $this->matcher->matches($state, $target)) {
				return true;
			}

			if($depth >= $maxSteps) {
				continue;
			}

			foreach($this->getOutgoingRoutes($state, $index) as $route) {
				$evaluation = ParserRouteEvaluation::supported($route->quality);

				if($this->scoreCalculator->violatesConstraints($route, $evaluation, $strategy)) {
					continue;
				}

				$toKey = $route->to->getKey();

				if(isset($visited[$toKey])) {
					continue;
				}

				$nextVisited = $visited;
				$nextVisited[$toKey] = true;
				$queue[] = [$route->to, $depth + 1, $nextVisited];
			}
		}

		return false;
	}

	/**
	 * @param mixed $sortPayload
	 * @return array<string,string>
	 */
	private function normalizeSort(mixed $sortPayload): array {
		$sort = is_array($sortPayload) && isset($sortPayload[0]) && is_array($sortPayload[0])
			? $sortPayload[0]
			: (is_array($sortPayload) ? $sortPayload : []);

		$key = (string) ($sort['key'] ?? 'totalCost');
		$direction = strtolower((string) ($sort['dir'] ?? $sort['direction'] ?? 'asc'));

		if(!in_array($direction, ['asc', 'desc'], true)) {
			$direction = 'asc';
		}

		$allowed = [
			'totalCost', 'steps', 'qualityPercent', 'speedPercent', 'stabilityPercent',
			'textQualityPercent', 'structureQualityPercent', 'layoutQualityPercent', 'tableQualityPercent',
			'priority', 'monetaryCost', 'parserChain', 'routeChain', 'inputState', 'outputState',
			'lossyCount', 'externalCount'
		];

		if(!in_array($key, $allowed, true)) {
			$key = 'totalCost';
		}

		return [
			'key' => $key,
			'direction' => $direction
		];
	}

	/**
	 * @param array<string,mixed> $request
	 * @return array<string,mixed>
	 */
	private function buildPageResponse(array $request): array {
		$page = $request['page'];
		$pageSize = $request['pageSize'];
		$offset = max(0, ($page - 1) * $pageSize);
		$sortKey = (string) ($request['sort']['key'] ?? 'totalCost');
		$limit = $sortKey === 'totalCost'
			? self::MAX_TOP_PLANS
			: self::SORTED_TOP_PLAN_LIMIT;

		$result = $this->findTopPlanRows($request['filters'], $request['search'], $limit);
		$rows = $this->sortRows($result['rows'], $request['sort']);
		$pageRows = array_map(fn(array $row): array => $this->withInlineDetail($row), array_slice($rows, $offset, $pageSize));

		// The explorer intentionally returns a bounded top-k result set for one selected conversion.
		// Infinite scrolling would force the server to recompute larger k-shortest path sets for every page.
		$hasMore = false;
		$total = count($rows);
		$totalPages = $pageSize > 0 ? (int) ceil($total / $pageSize) : 0;

		return [
			'mode' => 'page',
			'data' => $pageRows,
			'groups' => [],
			'page' => $page,
			'pageSize' => $pageSize,
			'total' => $total,
			'totalPages' => $totalPages,
			'hasMore' => $hasMore,
			'nextCursor' => null,
			'appliedSearch' => $request['search'],
			'appliedSort' => [$request['sort']],
			'appliedFilters' => $request['filters'],
			'appliedGroup' => [],
			'metadata' => [
				'explorationMode' => 'selected-state-pair-top-k',
				'inputState' => $request['filters']['input_state'],
				'outputState' => $request['filters']['output_state'],
				'expansions' => $result['expansions'],
				'truncated' => $result['truncated'],
				'limit' => $limit,
			]
		];
	}

	/**
	 * @param array<string,mixed> $request
	 * @return array<string,mixed>
	 */
	/**
	 * @param array<string,mixed> $row
	 * @return array<string,mixed>
	 */
	private function withInlineDetail(array $row): array {
		$row = $this->hydrateRow($row);
		$row['detail'] = $this->buildDetailPayload($row);
		return $row;
	}

	private function buildDetailResponse(array $request): array {
		$row = $request['row'];

		if(!is_array($row) || count($row) === 0 || (string) ($row['id'] ?? '') !== $request['id']) {
			foreach($this->findTopPlanRows($request['filters'], '', self::MAX_TOP_PLANS)['rows'] as $candidate) {
				if((string) ($candidate['id'] ?? '') === $request['id']) {
					$row = $candidate;
					break;
				}
			}
		}

		if(!is_array($row) || count($row) === 0) {
			return [
				'mode' => 'detail',
				'found' => false,
				'detail' => null
			];
		}

		$row = $this->hydrateRow($row);

		return [
			'mode' => 'detail',
			'found' => true,
			'detail' => $this->buildDetailPayload($row)
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	private function buildSummaryResponse(): array {
		return [
			'mode' => 'summary',
			'summary' => $this->buildSummaryPayload()
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	private function buildSummaryPayload(): array {
		$report = $this->getCapabilityReport();
		$routes = $this->getRoutes();
		$states = [];
		$outputs = [];
		$externalCount = 0;
		$lossyCount = 0;

		foreach($routes as $route) {
			$states[$route->from->getKey()] = true;
			$states[$route->to->getKey()] = true;
			$outputs[$route->to->type . ':' . ($route->to->format ?? '*')] = true;

			if($route->quality->requiresExternalService) {
				$externalCount++;
			}

			if($route->quality->lossy) {
				$lossyCount++;
			}
		}

		return [
			'parserCount' => (int) ($report->metadata['parserCount'] ?? count($report->parsers)),
			'routeCount' => count($routes),
			'stateCount' => count($states),
			'outputCount' => count($outputs),
			'externalRouteCount' => $externalCount,
			'lossyRouteCount' => $lossyCount,
			'warnings' => $report->warnings
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	private function buildOptionsPayload(): array {
		$routes = $this->getRoutes();
		$inputStates = [];
		$outputStates = [];
		$parserOptions = [];

		foreach($routes as $route) {
			$inputStates[$this->stateLabel($route->from)] = true;
			$outputStates[$this->stateLabel($route->to)] = true;
			$parserOptions[$route->parserName] = true;
		}

		return [
			'defaults' => $this->getDefaultFilters(),
			'sourceTypes' => $this->optionRows(['string', 'file', 'binary', 'stream'], false),
			'inputStates' => $this->optionRows(array_keys($inputStates), false),
			'outputStates' => $this->optionRows(array_keys($outputStates), false),
			'targetTypes' => $this->optionRows(['return', 'file', 'directory', 'stream'], false),
			'strategies' => [
				['value' => 'balanced', 'label' => 'Balanced'],
				['value' => 'fastest', 'label' => 'Fastest'],
				['value' => 'best_quality', 'label' => 'Best quality'],
				['value' => 'best_text', 'label' => 'Best text'],
				['value' => 'best_structure', 'label' => 'Best structure'],
				['value' => 'local_only', 'label' => 'Local only'],
			],
			'boolean' => [
				['value' => 'yes', 'label' => 'Yes'],
				['value' => 'no', 'label' => 'No'],
			],
			'maxSteps' => $this->optionRows(['1', '2', '3', '4', '5', '6'], false),
			'parsers' => $this->optionRows(array_keys($parserOptions), true)
		];
	}

	/**
	 * @param string[] $values
	 * @param bool $includeEmpty
	 * @param string[] $prepend
	 * @return array<int,array<string,string>>
	 */
	private function optionRows(array $values, bool $includeEmpty = false, array $prepend = []): array {
		$values = array_values(array_unique(array_filter(array_map('strval', array_merge($prepend, $values)), fn(string $value) => $value !== '')));
		sort($values, SORT_NATURAL | SORT_FLAG_CASE);
		$options = [];

		if($includeEmpty) {
			$options[] = ['value' => '', 'label' => 'Any'];
		}

		foreach($values as $value) {
			$options[] = ['value' => $value, 'label' => $value];
		}

		return $options;
	}

	/**
	 * @param array<string,string> $filters
	 * @return array{rows:array<int,array<string,mixed>>,hasMore:bool,expansions:int,truncated:bool}
	 */
	private function findTopPlanRows(array $filters, string $search, int $limit): array {
		$startState = $this->stateFromLabel($filters['input_state'] ?? '');
		$targetState = $this->stateFromLabel($filters['output_state'] ?? '');

		if($startState === null || $targetState === null) {
			return [
				'rows' => [],
				'hasMore' => false,
				'expansions' => 0,
				'truncated' => false
			];
		}

		$strategy = $this->strategyFromFilters($filters);
		$index = $this->getRouteIndex();
		$queue = new \SplPriorityQueue();
		$queue->setExtractFlags(\SplPriorityQueue::EXTR_DATA);
		$queue->insert([
			'currentState' => $startState,
			'path' => [],
			'cost' => 0.0,
			'visited' => [$startState->getKey() => true]
		], 0.0);

		$rows = [];
		$seenPaths = [];
		$stateCandidateCosts = [$startState->getKey() => [0.0]];
		$expansions = 0;
		$search = strtolower(trim($search));

		while(!$queue->isEmpty() && count($rows) < $limit && $expansions < self::MAX_NODE_EXPANSIONS) {
			$item = $queue->extract();
			$currentState = $item['currentState'];
			$path = $item['path'];
			$cost = (float) $item['cost'];
			$visited = $item['visited'];
			$stepCount = count($path);

			if($stepCount > 0 && $this->matcher->matches($currentState, $targetState)) {
				$row = $this->planRow($path, $cost, $filters, $strategy);
				$signature = (string) ($row['pathSignature'] ?? $row['id']);

				if(!isset($seenPaths[$signature]) && $this->rowMatchesSearch($row, $search)) {
					$seenPaths[$signature] = true;
					$rows[] = $row;
				}

				continue;
			}

			if($stepCount >= $strategy->maxSteps) {
				continue;
			}

			$expansions++;
			$outgoingRoutes = $this->getBoundedOutgoingRoutes($currentState, $targetState, $strategy, $index, $stepCount);

			foreach($outgoingRoutes as $route) {
				$evaluation = ParserRouteEvaluation::supported($route->quality);

				if($this->scoreCalculator->violatesConstraints($route, $evaluation, $strategy)) {
					continue;
				}

				$toKey = $route->to->getKey();

				if(isset($visited[$toKey])) {
					continue;
				}

				$routeCost = $this->scoreCalculator->calculate($route, $evaluation, $strategy);
				$nextCost = $cost + $routeCost;

				if(!$this->acceptExplorerStateCandidate($toKey, $nextCost, $stateCandidateCosts)) {
					continue;
				}

				$nextVisited = $visited;
				$nextVisited[$toKey] = true;
				$nextPath = $path;
				$nextPath[] = $route;

				$queue->insert([
					'currentState' => $route->to,
					'path' => $nextPath,
					'cost' => $nextCost,
					'visited' => $nextVisited
				], -$nextCost);
			}
		}

		return [
			'rows' => array_values($rows),
			'hasMore' => !$queue->isEmpty(),
			'expansions' => $expansions,
			'truncated' => $expansions >= self::MAX_NODE_EXPANSIONS
		];
	}

	/**
	 * Returns a small, ordered route set for one expansion step.
	 *
	 * If an intermediate state can already reach the selected target directly,
	 * the explorer treats that direct route as terminal and does not branch away
	 * into format detours such as html -> xml -> csv -> markdown. The start
	 * state may still branch, so direct routes and simple alternatives remain
	 * visible.
	 *
	 * @param array<string,ParserRoute[]> $index
	 * @return ParserRoute[]
	 */
	private function getBoundedOutgoingRoutes(ParserState $currentState, ParserState $targetState, ParserStrategy $strategy, array $index, int $stepCount): array {
		$routes = $this->getOutgoingRoutes($currentState, $index);
		$directRoutes = [];
		$indirectRoutes = [];

		foreach($routes as $route) {
			if($this->matcher->matches($route->to, $targetState)) {
				$directRoutes[] = $route;
			} else {
				$indirectRoutes[] = $route;
			}
		}

		$directRoutes = $this->sortRoutesForExpansion($directRoutes, $strategy);

		if($stepCount > 0 && count($directRoutes) > 0) {
			return array_slice($directRoutes, 0, self::MAX_OUTGOING_ROUTES_PER_STATE);
		}

		$indirectRoutes = $this->sortRoutesForExpansion($indirectRoutes, $strategy);

		if($stepCount === 0 && count($directRoutes) > 0) {
			return array_slice(
				array_merge($directRoutes, $indirectRoutes),
				0,
				self::MAX_START_BRANCH_ROUTES
			);
		}

		return array_slice($indirectRoutes, 0, self::MAX_OUTGOING_ROUTES_PER_STATE);
	}

	/**
	 * @param ParserRoute[] $routes
	 * @return ParserRoute[]
	 */
	private function sortRoutesForExpansion(array $routes, ParserStrategy $strategy): array {
		usort($routes, function(ParserRoute $a, ParserRoute $b) use ($strategy): int {
			$aCost = $this->scoreCalculator->calculate($a, ParserRouteEvaluation::supported($a->quality), $strategy);
			$bCost = $this->scoreCalculator->calculate($b, ParserRouteEvaluation::supported($b->quality), $strategy);

			return ($aCost <=> $bCost)
				?: strnatcasecmp($a->parserName . ':' . $a->routeName, $b->parserName . ':' . $b->routeName);
		});

		return $routes;
	}


	/**
	 * @param array<string,float[]> $stateCandidateCosts
	 */
	private function acceptExplorerStateCandidate(string $stateKey, float $cost, array &$stateCandidateCosts): bool {
		$stateCandidateCosts[$stateKey] ??= [];

		foreach($stateCandidateCosts[$stateKey] as $existingCost) {
			if(abs($existingCost - $cost) < 0.000001) {
				return false;
			}
		}

		$stateCandidateCosts[$stateKey][] = $cost;
		sort($stateCandidateCosts[$stateKey], SORT_NUMERIC);

		if(count($stateCandidateCosts[$stateKey]) > self::MAX_PATHS_PER_STATE) {
			$stateCandidateCosts[$stateKey] = array_slice($stateCandidateCosts[$stateKey], 0, self::MAX_PATHS_PER_STATE);
			return in_array($cost, $stateCandidateCosts[$stateKey], true);
		}

		return true;
	}

	/**
	 * @param array<string,ParserRoute[]> $index
	 * @return ParserRoute[]
	 */
	private function getOutgoingRoutes(ParserState $state, array $index): array {
		$candidates = [];

		foreach([$state->type, '*', 'any'] as $type) {
			foreach($index[$type] ?? [] as $route) {
				$candidates[$route->getKey()] = $route;
			}
		}

		return array_values(array_filter($candidates, fn(ParserRoute $route): bool => $this->matcher->matches($state, $route->from)));
	}

	/**
	 * @return array<string,ParserRoute[]>
	 */
	private function getRouteIndex(): array {
		if($this->routeIndex !== null) {
			return $this->routeIndex;
		}

		$this->routeIndex = [];

		foreach($this->getRoutes() as $route) {
			$this->routeIndex[$route->from->type] ??= [];
			$this->routeIndex[$route->from->type][] = $route;
		}

		return $this->routeIndex;
	}

	/**
	 * @param ParserRoute[] $path
	 * @param array<string,string> $filters
	 * @return array<string,mixed>
	 */
	private function planRow(array $path, float $totalCost, array $filters, ParserStrategy $strategy): array {
		$first = $path[0];
		$last = $path[count($path) - 1];
		$qualityRows = [];
		$parserNames = [];
		$routeNames = [];
		$routeKeys = [];
		$externalCount = 0;
		$lossyCount = 0;
		$monetaryCost = 0.0;
		$priority = 0;

		foreach($path as $route) {
			$parserNames[] = $route->parserName;
			$routeNames[] = $route->routeName;
			$routeKeys[] = $route->getKey();
			$qualityRows[] = $route->quality;
			$monetaryCost += $route->quality->monetaryCost;
			$priority += $route->quality->priority;

			if($route->quality->requiresExternalService) {
				$externalCount++;
			}

			if($route->quality->lossy) {
				$lossyCount++;
			}
		}

		$parserChain = implode(' → ', $parserNames);
		$routeChain = implode(' → ', $routeNames);
		$pathSignature = implode('|', $routeKeys);
		$id = substr(sha1($this->stateLabel($first->from) . '|' . $this->stateLabel($last->to) . '|' . $pathSignature . '|' . json_encode($filters)), 0, 16);

		return [
			'id' => $id,
			'pathSignature' => $pathSignature,
			'routeKeys' => $routeKeys,
			'sourceType' => $filters['source_type'] ?: $this->sourceTypeForInputState($this->stateLabel($first->from)),
			'targetType' => $filters['target_type'] ?: 'return',
			'strategy' => $filters['strategy'] ?: 'balanced',
			'inputState' => $this->stateLabel($first->from),
			'outputState' => $this->stateLabel($last->to),
			'parserChain' => $parserChain,
			'routeChain' => $routeChain,
			'steps' => count($path),
			'totalCost' => round($totalCost, 6),
			'qualityPercent' => $this->averageQualityPercent($qualityRows, $strategy),
			'textQualityPercent' => $this->averagePercent($qualityRows, 'textQuality'),
			'structureQualityPercent' => $this->averagePercent($qualityRows, 'structureQuality'),
			'layoutQualityPercent' => $this->averagePercent($qualityRows, 'layoutQuality'),
			'tableQualityPercent' => $this->averagePercent($qualityRows, 'tableQuality'),
			'speedPercent' => $this->averagePercent($qualityRows, 'speed'),
			'stabilityPercent' => $this->averagePercent($qualityRows, 'stability'),
			'lossyCount' => $lossyCount,
			'externalCount' => $externalCount,
			'monetaryCost' => round($monetaryCost, 6),
			'priority' => $priority,
			'filters' => $filters,
		];
	}

	/**
	 * @param array<string,mixed> $row
	 * @return array<string,mixed>
	 */
	private function hydrateRow(array $row): array {
		if(isset($row['stepsData']) && is_array($row['stepsData'])) {
			return $row;
		}

		$routeKeys = is_array($row['routeKeys'] ?? null) ? $row['routeKeys'] : [];
		$routeMap = $this->getRouteMap();
		$routes = [];

		foreach($routeKeys as $routeKey) {
			$routeKey = (string) $routeKey;

			if(isset($routeMap[$routeKey])) {
				$routes[] = $routeMap[$routeKey];
			}
		}

		$strategy = $this->strategyFromFilters(is_array($row['filters'] ?? null) ? $row['filters'] : $this->getDefaultFilters());
		$stepsData = [];

		foreach($routes as $index => $route) {
			$stepsData[] = [
				'index' => $index + 1,
				'parserName' => $route->parserName,
				'routeName' => $route->routeName,
				'from' => $this->stateLabel($route->from),
				'to' => $this->stateLabel($route->to),
				'cost' => round($this->scoreCalculator->calculate($route, ParserRouteEvaluation::supported($route->quality), $strategy), 6),
				'quality' => $this->routeQualityPayload($route),
			];
		}

		$row['stepsData'] = $stepsData;
		return $row;
	}

	/**
	 * @return array<string,ParserRoute>
	 */
	private function getRouteMap(): array {
		if($this->routeMap !== null) {
			return $this->routeMap;
		}

		$this->routeMap = [];

		foreach($this->getRoutes() as $route) {
			$this->routeMap[$route->getKey()] = $route;
		}

		return $this->routeMap;
	}

	/**
	 * @return array<string,mixed>
	 */
	private function routeQualityPayload(ParserRoute $route): array {
		return [
			'textQuality' => $route->quality->textQuality,
			'structureQuality' => $route->quality->structureQuality,
			'layoutQuality' => $route->quality->layoutQuality,
			'tableQuality' => $route->quality->tableQuality,
			'imageQuality' => $route->quality->imageQuality,
			'semanticQuality' => $route->quality->semanticQuality,
			'speed' => $route->quality->speed,
			'stability' => $route->quality->stability,
			'monetaryCost' => $route->quality->monetaryCost,
			'lossy' => $route->quality->lossy,
			'requiresExternalService' => $route->quality->requiresExternalService,
			'priority' => $route->quality->priority,
		];
	}

	/**
	 * @param array<int,mixed> $qualities
	 */
	private function averageQualityPercent(array $qualities, ParserStrategy $strategy): int {
		if(count($qualities) === 0) {
			return 0;
		}

		$total = 0.0;

		foreach($qualities as $quality) {
			$total += $this->scoreCalculator->combinedQuality($quality, $strategy);
		}

		return (int) round(($total / count($qualities)) * 100);
	}

	/**
	 * @param array<int,mixed> $qualities
	 */
	private function averagePercent(array $qualities, string $property): int {
		if(count($qualities) === 0) {
			return 0;
		}

		$total = 0.0;

		foreach($qualities as $quality) {
			$total += (float) ($quality->{$property} ?? 0.0);
		}

		return (int) round(($total / count($qualities)) * 100);
	}

	private function stateLabel(ParserState $state): string {
		return $state->type . '/' . ($state->format ?? '*');
	}

	private function stateFromLabel(string $label): ?ParserState {
		$label = trim($label);

		if($label === '' || $label === 'any') {
			return null;
		}

		[$type, $format] = array_pad(explode('/', $label, 2), 2, '*');
		$type = trim((string) $type);
		$format = trim((string) $format);

		if($type === '' || $type === '*') {
			return null;
		}

		return new ParserState($type, $format === '' || $format === '*' ? null : $format);
	}

	/**
	 * @param array<string,mixed> $row
	 */
	private function rowMatchesSearch(array $row, string $search): bool {
		if($search === '') {
			return true;
		}

		$haystack = strtolower(implode(' ', [
			$row['inputState'] ?? '',
			$row['outputState'] ?? '',
			$row['parserChain'] ?? '',
			$row['routeChain'] ?? '',
			$row['sourceType'] ?? '',
			$row['targetType'] ?? '',
			$row['strategy'] ?? '',
			$row['pathSignature'] ?? '',
		]));

		return str_contains($haystack, $search);
	}

	/**
	 * @param array<int,array<string,mixed>> $rows
	 * @param array<string,string> $sort
	 * @return array<int,array<string,mixed>>
	 */
	private function sortRows(array $rows, array $sort): array {
		$key = $sort['key'];
		$direction = $sort['direction'];

		usort($rows, function(array $a, array $b) use ($key, $direction): int {
			$av = $a[$key] ?? null;
			$bv = $b[$key] ?? null;

			if(is_numeric($av) && is_numeric($bv)) {
				$result = (float) $av <=> (float) $bv;
			} else {
				$result = strnatcasecmp((string) $av, (string) $bv);
			}

			if($result === 0) {
				$result = ((int) ($a['steps'] ?? 0) <=> (int) ($b['steps'] ?? 0))
					?: ((float) ($a['totalCost'] ?? 0.0) <=> (float) ($b['totalCost'] ?? 0.0))
					?: strnatcasecmp((string) ($a['parserChain'] ?? ''), (string) ($b['parserChain'] ?? ''));
			}

			return $direction === 'desc' ? -$result : $result;
		});

		return $rows;
	}

	/**
	 * @param array<string,string> $filters
	 */
	private function strategyFromFilters(array $filters): ParserStrategy {
		$mode = (string) ($filters['strategy'] ?? 'balanced');
		$allowLossy = (($filters['allow_lossy'] ?? 'yes') !== 'no');
		$allowExternal = (($filters['allow_external'] ?? 'no') === 'yes');
		$maxSteps = max(1, min(6, (int) ($filters['max_steps'] ?? self::DEFAULT_MAX_STEPS)));

		return match($mode) {
			'fastest' => new ParserStrategy(mode: 'fastest', maxSteps: $maxSteps, allowLossy: $allowLossy, allowExternalServices: $allowExternal, speedWeight: 1.2, stepPenalty: 0.15),
			'best_quality' => new ParserStrategy(mode: 'best_quality', maxSteps: $maxSteps, allowLossy: $allowLossy, allowExternalServices: $allowExternal, speedWeight: 0.1, stepPenalty: 0.04),
			'best_text' => new ParserStrategy(mode: 'best_text', maxSteps: $maxSteps, allowLossy: $allowLossy, allowExternalServices: $allowExternal, textWeight: 3.0, structureWeight: 0.7, layoutWeight: 0.5, tableWeight: 0.5, speedWeight: 0.1, stepPenalty: 0.04),
			'best_structure' => new ParserStrategy(mode: 'best_structure', maxSteps: $maxSteps, allowLossy: $allowLossy, allowExternalServices: $allowExternal, textWeight: 0.7, structureWeight: 3.0, layoutWeight: 1.6, tableWeight: 1.8, speedWeight: 0.1, stepPenalty: 0.04),
			'local_only' => new ParserStrategy(mode: 'local_only', maxSteps: $maxSteps, allowLossy: $allowLossy, allowExternalServices: false),
			default => new ParserStrategy(mode: 'balanced', maxSteps: $maxSteps, allowLossy: $allowLossy, allowExternalServices: $allowExternal),
		};
	}

	/**
	 * @param array<string,mixed> $row
	 * @return array<string,mixed>
	 */
	private function buildDetailPayload(array $row): array {
		$row = $this->hydrateRow($row);

		return [
			'headline' => ($row['inputState'] ?? '') . ' → ' . ($row['outputState'] ?? ''),
			'summary' => ($row['parserChain'] ?? '') . ' | cost ' . ($row['totalCost'] ?? ''),
			'badges' => $this->buildDetailBadges($row),
			'sections' => $this->buildDetailSections($row),
			'steps' => $row['stepsData'] ?? [],
			'phpCode' => $this->buildPhpCode($row),
			'record' => $row,
		];
	}

	/**
	 * @param array<string,mixed> $row
	 * @return array<int,array<string,string>>
	 */
	private function buildDetailBadges(array $row): array {
		$badges = [
			['label' => (string) ($row['strategy'] ?? 'balanced'), 'className' => 'parser-explorer-pill-strong'],
			['label' => 'steps: ' . (string) ($row['steps'] ?? '0'), 'className' => ''],
			['label' => 'quality: ' . (string) ($row['qualityPercent'] ?? '0') . '%', 'className' => ''],
			['label' => 'speed: ' . (string) ($row['speedPercent'] ?? '0') . '%', 'className' => ''],
		];

		if(((int) ($row['externalCount'] ?? 0)) > 0) {
			$badges[] = ['label' => 'external', 'className' => 'parser-explorer-pill-warning'];
		}

		if(((int) ($row['lossyCount'] ?? 0)) > 0) {
			$badges[] = ['label' => 'lossy', 'className' => 'parser-explorer-pill-warning'];
		}

		return $badges;
	}

	/**
	 * @param array<string,mixed> $row
	 * @return array<int,array<string,string>>
	 */
	private function buildDetailSections(array $row): array {
		return [
			['label' => 'Source', 'value' => (string) ($row['sourceType'] ?? '')],
			['label' => 'Input state', 'value' => (string) ($row['inputState'] ?? '')],
			['label' => 'Output state', 'value' => (string) ($row['outputState'] ?? '')],
			['label' => 'Target', 'value' => (string) ($row['targetType'] ?? '')],
			['label' => 'Parser chain', 'value' => (string) ($row['parserChain'] ?? '')],
			['label' => 'Route chain', 'value' => (string) ($row['routeChain'] ?? '')],
			['label' => 'Total cost', 'value' => (string) ($row['totalCost'] ?? '')],
			['label' => 'Monetary cost', 'value' => (string) ($row['monetaryCost'] ?? '')],
			['label' => 'Path signature', 'value' => (string) ($row['pathSignature'] ?? '')],
		];
	}

	/**
	 * @param array<string,mixed> $row
	 */
	private function buildPhpCode(array $row): string {
		$row = $this->hydrateRow($row);
		$filters = is_array($row['filters'] ?? null) ? $row['filters'] : $this->getDefaultFilters();
		$sourceType = (string) ($row['sourceType'] ?? $filters['source_type'] ?? 'string');
		$targetType = (string) ($row['targetType'] ?? $filters['target_type'] ?? 'return');
		$inputState = $this->splitState((string) ($row['inputState'] ?? 'string/text'));
		$outputState = $this->splitState((string) ($row['outputState'] ?? 'string/text'));
		$steps = is_array($row['stepsData'] ?? null) ? $row['stepsData'] : [];
		$parserNames = [];

		foreach($steps as $step) {
			$parserName = (string) ($step['parserName'] ?? '');

			if($parserName !== '' && !in_array($parserName, $parserNames, true)) {
				$parserNames[] = $parserName;
			}
		}

		$imports = [
			'use ParseFlow\\Api\\IParserService;',
			'use ParseFlow\\Dto\\ParserRequest;',
			'use ParseFlow\\Dto\\ParserStrategy;',
			'use ParseFlow\\Input\\' . $this->inputClass($inputState['type']) . ';',
			'use ParseFlow\\Output\\' . $this->outputClass($outputState['type']) . ';',
			'use ParseFlow\\Source\\' . $this->sourceClass($sourceType) . ';',
			'use ParseFlow\\Target\\' . $this->targetClass($targetType) . ';',
		];

		$imports = array_values(array_unique($imports));
		$chainComment = [];

		foreach($steps as $step) {
			$chainComment[] = ' * ' . (string) ($step['index'] ?? '?') . '. ' . (string) ($step['from'] ?? '') . ' -- ' . (string) ($step['parserName'] ?? '') . ':' . (string) ($step['routeName'] ?? '') . ' --> ' . (string) ($step['to'] ?? '');
		}

		$strategy = $this->buildPhpStrategy((string) ($row['strategy'] ?? 'balanced'), $filters, $parserNames);
		$code = "<?php declare(strict_types=1);\n\n";
		$code .= implode("\n", $imports) . "\n\n";
		$code .= "/** @var IParserService \$parserService */\n";
		$code .= "/*\n * Expected parser combination for the current registry and strategy:\n";
		$code .= count($chainComment) > 0 ? implode("\n", $chainComment) . "\n" : " * No parser steps available.\n";
		$code .= " */\n";
		$code .= $this->buildPhpSourceSetup($sourceType, $inputState['format']);
		$code .= $this->buildPhpTargetSetup($targetType, $outputState['format']);
		$code .= "\$request = new ParserRequest(\n";
		$code .= "\tsource: " . $this->buildPhpSourceExpression($sourceType) . ",\n";
		$code .= "\tinput: " . $this->buildPhpInputExpression($inputState) . ",\n";
		$code .= "\toutput: " . $this->buildPhpOutputExpression($outputState) . ",\n";
		$code .= "\ttarget: " . $this->buildPhpTargetExpression($targetType) . ",\n";
		$code .= "\tstrategy: " . $strategy . "\n";
		$code .= ");\n\n";
		$code .= "\$result = \$parserService->parse(\$request);\n";

		return $code;
	}

	/**
	 * @return array<string,string|null>
	 */
	private function splitState(string $state): array {
		[$type, $format] = array_pad(explode('/', $state, 2), 2, null);
		return [
			'type' => $type ?: 'string',
			'format' => $format === '*' ? null : $format
		];
	}

	/**
	 * @param array<string,string|null> $state
	 */
	private function buildPhpInputExpression(array $state): string {
		$class = $this->inputClass((string) $state['type']);
		$format = $state['format'];

		if($class === 'AutoDetectParserInput') {
			return 'new AutoDetectParserInput()';
		}

		return 'new ' . $class . '(' . $this->phpStringOrNull($format) . ')';
	}

	/**
	 * @param array<string,string|null> $state
	 */
	private function buildPhpOutputExpression(array $state): string {
		return 'new ' . $this->outputClass((string) $state['type']) . '(' . $this->phpStringOrNull($state['format']) . ')';
	}

	private function buildPhpSourceExpression(string $sourceType): string {
		return match($sourceType) {
			'file' => 'new FileParserSource($inputPath)',
			'binary' => 'new BinaryParserSource($binary, [\'filename\' => $inputFilename])',
			'stream' => 'new StreamParserSource($inputStream, [\'filename\' => $inputFilename])',
			default => 'new StringParserSource($inputText)'
		};
	}

	private function buildPhpTargetExpression(string $targetType): string {
		return match($targetType) {
			'file' => 'new FileParserTarget($outputPath)',
			'directory' => 'new DirectoryParserTarget($outputDirectory)',
			'stream' => 'new StreamParserTarget($outputStream)',
			default => 'new ReturnParserTarget()'
		};
	}

	private function buildPhpSourceSetup(string $sourceType, ?string $format): string {
		$extension = $format ?: 'txt';

		return match($sourceType) {
			'file' => "\$inputPath = '/tmp/input." . $extension . "';\n\n",
			'binary' => "\$binary = file_get_contents('/tmp/input." . $extension . "');\n\$inputFilename = 'input." . $extension . "';\n\n",
			'stream' => "\$inputStream = fopen('/tmp/input." . $extension . "', 'rb');\n\$inputFilename = 'input." . $extension . "';\n\n",
			default => "\$inputText = '...';\n\n"
		};
	}

	private function buildPhpTargetSetup(string $targetType, ?string $format): string {
		$extension = $format ?: 'txt';

		return match($targetType) {
			'file' => "\$outputPath = '/tmp/output." . $extension . "';\n\n",
			'directory' => "\$outputDirectory = '/tmp/parseflow-output';\n\n",
			'stream' => "\$outputStream = fopen('php://temp', 'w+b');\n\n",
			default => ''
		};
	}

	/**
	 * @param array<string,string> $filters
	 * @param string[] $parserNames
	 */
	private function buildPhpStrategy(string $mode, array $filters, array $parserNames): string {
		$allowLossy = (($filters['allow_lossy'] ?? 'yes') !== 'no') ? 'true' : 'false';
		$allowExternal = (($filters['allow_external'] ?? 'no') === 'yes') ? 'true' : 'false';
		$maxSteps = max(1, min(6, (int) ($filters['max_steps'] ?? self::DEFAULT_MAX_STEPS)));
		$parserList = '[' . implode(', ', array_map(fn(string $name) => "'" . addslashes($name) . "'", $parserNames)) . ']';

		return "new ParserStrategy(\n" .
			"\t\tmode: '" . addslashes($mode) . "',\n" .
			"\t\tmaxSteps: " . $maxSteps . ",\n" .
			"\t\tallowLossy: " . $allowLossy . ",\n" .
			"\t\tallowExternalServices: " . $allowExternal . ",\n" .
			"\t\tallowedParsers: " . $parserList . "\n" .
			"\t)";
	}

	private function sourceClass(string $type): string {
		return match($type) {
			'file' => 'FileParserSource',
			'binary' => 'BinaryParserSource',
			'stream' => 'StreamParserSource',
			default => 'StringParserSource'
		};
	}

	private function inputClass(string $type): string {
		return match($type) {
			'auto' => 'AutoDetectParserInput',
			'document' => 'DocumentParserInput',
			'image' => 'ImageParserInput',
			'structured' => 'StructuredParserInput',
			default => 'PlainTextParserInput'
		};
	}

	private function outputClass(string $type): string {
		return match($type) {
			'structured' => 'StructuredParserOutput',
			'files' => 'FilesParserOutput',
			'chunks' => 'ChunksParserOutput',
			'binary' => 'BinaryParserOutput',
			'stream' => 'StreamParserOutput',
			'image' => 'ImageParserOutput',
			default => 'StringParserOutput'
		};
	}

	private function targetClass(string $type): string {
		return match($type) {
			'file' => 'FileParserTarget',
			'directory' => 'DirectoryParserTarget',
			'stream' => 'StreamParserTarget',
			default => 'ReturnParserTarget'
		};
	}

	private function phpStringOrNull(?string $value): string {
		return $value === null || $value === '' ? 'null' : "'" . addslashes($value) . "'";
	}

	private function getCapabilityReport(): ParserCapabilityReport {
		if($this->capabilityReport !== null) {
			return $this->capabilityReport;
		}

		try {
			$this->capabilityReport = $this->parserService->explore();
		} catch(Throwable $e) {
			$this->capabilityReport = new ParserCapabilityReport(warnings: [$e->getMessage()]);
		}

		return $this->capabilityReport;
	}

	/**
	 * @return ParserRoute[]
	 */
	private function getRoutes(): array {
		if($this->routes !== null) {
			return $this->routes;
		}

		try {
			$this->routes = $this->parserService->listRoutes();
		} catch(Throwable) {
			$this->routes = [];
		}

		return $this->routes;
	}
}
