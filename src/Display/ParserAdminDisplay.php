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
use Base3\Api\IClassMap;
use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Api\ISchemaProvider;
use Base3\LinkTarget\Api\ILinkTargetService;
use Base3\Settings\Api\ISettingsStore;
use ParseFlow\Api\IParser;
use ParseFlow\ParserSettings;
use RuntimeException;
use Throwable;

/**
 * ModularGrid based administration display for discovered ParseFlow parsers.
 *
 * Parser configuration is an optional capability. Parsers implementing
 * ISchemaProvider expose their schema here and own the interpretation of the
 * persisted values at runtime.
 */
final class ParserAdminDisplay implements IDisplay {

	private const DEFAULT_PAGE_SIZE = 50;
	private const MAX_PAGE_SIZE = 100;

	private array $translations = [];

	public function __construct(
		private readonly IRequest $request,
		private readonly IMvcView $view,
		private readonly IAssetResolver $assetResolver,
		private readonly ILinkTargetService $linkTargetService,
		private readonly IClassMap $classMap,
		private readonly ISettingsStore $settingsStore
	) {}

	public static function getName(): string {
		return 'parseflowparseradmindisplay';
	}

	public function setData($data) {
		// no-op
	}

	public function getHelp(): string {
		$this->loadTranslations();
		return $this->t('help', 'Inspect discovered ParseFlow parsers and configure parsers that expose a schema.');
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		$this->loadTranslations();

		if(strtolower($out) === 'json') {
			return $this->handleJson($final);
		}

		return $this->handleHtml();
	}

	private function handleHtml(): string {
		$this->view->setPath(DIR_PLUGIN . 'ParseFlow');
		$this->view->setTemplate('Display/ParserAdminDisplay.php');
		$this->view->assign('service', $this->linkTargetService->getLink([
			'name' => self::getName(),
			'out' => 'json',
		]));
		$this->view->assign('resolve', fn($src) => $this->assetResolver->resolve((string)$src));
		$this->view->assign('translations', $this->translations);

		return $this->view->loadTemplate();
	}

	private function handleJson(bool $final): string {
		try {
			$request = $this->readRequestPayload();
			$mode = trim((string)($request['mode'] ?? 'page'));

			$response = match($mode) {
				'page' => $this->buildPageResponse($request),
				'detail' => $this->buildDetailResponse($request),
				'save' => $this->saveParserConfig($request),
				'reset' => $this->resetParserConfig($request),
				default => throw new RuntimeException('Unknown parser administration action.'),
			};
		}
		catch(Throwable $e) {
			$response = [
				'success' => false,
				'error' => $e->getMessage(),
			];
		}

		if($final && !headers_sent()) {
			header('Content-Type: application/json; charset=utf-8');
		}

		return json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
	}

	/**
	 * @return array<string,mixed>
	 */
	private function readRequestPayload(): array {
		$payload = $this->request->getJsonBody();
		if(is_array($payload) && $payload !== []) {
			return $payload;
		}

		$payload = $this->request->allPost();
		return is_array($payload) ? $payload : [];
	}

	/**
	 * @param array<string,mixed> $request
	 * @return array<string,mixed>
	 */
	private function buildPageResponse(array $request): array {
		$page = max(1, (int)($request['page'] ?? 1));
		$pageSize = max(1, min(self::MAX_PAGE_SIZE, (int)($request['pageSize'] ?? self::DEFAULT_PAGE_SIZE)));
		$search = strtolower(trim((string)($request['search'] ?? '')));
		$filters = is_array($request['filters'] ?? null) ? $request['filters'] : [];
		$configurationFilter = strtolower(trim((string)($filters['configuration'] ?? '')));
		$statusFilter = strtolower(trim((string)($filters['status'] ?? '')));
		$sort = $this->normalizeSort($request['sort'] ?? []);
		$rows = $this->buildRows();

		if($search !== '') {
			$rows = array_values(array_filter($rows, static function(array $row) use ($search): bool {
				$haystack = strtolower(trim(
					(string)($row['name'] ?? '') . ' ' .
					(string)($row['class'] ?? '') . ' ' .
					(string)($row['status'] ?? '')
				));

				return str_contains($haystack, $search);
			}));
		}

		if(in_array($configurationFilter, ['yes', 'no'], true)) {
			$expected = $configurationFilter === 'yes';
			$rows = array_values(array_filter($rows, static fn(array $row): bool => (bool)($row['configurable'] ?? false) === $expected));
		}

		if(in_array($statusFilter, ['yes', 'no', 'na'], true)) {
			$expectedStatus = match($statusFilter) {
				'yes' => 'enabled',
				'no' => 'disabled',
				default => 'na',
			};
			$rows = array_values(array_filter($rows, static fn(array $row): bool => (string)($row['status'] ?? '') === $expectedStatus));
		}

		$this->sortRows($rows, $sort);
		$total = count($rows);
		$offset = max(0, ($page - 1) * $pageSize);
		$pageRows = array_slice($rows, $offset, $pageSize);

		return [
			'mode' => 'page',
			'success' => true,
			'data' => $pageRows,
			'groups' => [],
			'page' => $page,
			'pageSize' => $pageSize,
			'total' => $total,
			'totalPages' => $pageSize > 0 ? (int)ceil($total / $pageSize) : 0,
			'hasMore' => $offset + count($pageRows) < $total,
			'nextCursor' => null,
			'appliedSearch' => $search,
			'appliedSort' => [$sort],
			'appliedFilters' => [
				'configuration' => $configurationFilter,
				'status' => $statusFilter,
			],
			'appliedGroup' => [],
		];
	}

	/**
	 * @param array<string,mixed> $request
	 * @return array<string,mixed>
	 */
	private function buildDetailResponse(array $request): array {
		$parserName = trim((string)($request['parser'] ?? $request['id'] ?? ''));
		if($parserName === '') {
			throw new RuntimeException('Missing parser name.');
		}

		return [
			'mode' => 'detail',
			'success' => true,
			'found' => true,
			'detail' => $this->buildDetail($parserName),
		];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private function buildRows(): array {
		$instances = $this->classMap->getInstancesByInterface(IParser::class);
		$rows = [];

		foreach($instances as $parser) {
			if(!$parser instanceof IParser) {
				continue;
			}

			$rows[] = $this->buildRow($parser);
		}

		return $rows;
	}

	/**
	 * @return array<string,mixed>
	 */
	private function buildRow(IParser $parser): array {
		$name = $parser::getName();
		$routes = [];
		$routeError = '';

		try {
			$routes = $parser->getRoutes();
		}
		catch(Throwable $e) {
			$routeError = $e->getMessage();
		}

		$configurable = $parser instanceof ISchemaProvider;
		$schema = [];
		$schemaError = '';

		if($configurable) {
			try {
				$schema = $parser->getSchema();
				if(!is_array($schema)) {
					$schema = [];
				}
			}
			catch(Throwable $e) {
				$schemaError = $e->getMessage();
			}
		}

		$storedConfig = $configurable
			? $this->settingsStore->get(ParserSettings::GROUP, $name, [])
			: [];
		$effectiveConfig = $this->applySchemaDefaults($schema, $storedConfig);
		$enabled = $this->readEffectiveEnabled($schema, $effectiveConfig);
		$status = $routeError !== '' || $schemaError !== ''
			? 'error'
			: ($enabled === true ? 'enabled' : ($enabled === false ? 'disabled' : 'na'));

		return [
			'id' => $name,
			'name' => $name,
			'class' => $parser::class,
			'routeCount' => count($routes),
			'configurable' => $configurable && $schema !== [],
			'configured' => $configurable && $this->settingsStore->has(ParserSettings::GROUP, $name),
			'enabled' => $enabled,
			'status' => $status,
			'routeError' => $routeError,
			'schemaError' => $schemaError,
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	private function buildDetail(string $parserName): array {
		$parser = $this->getParser($parserName);
		$routes = [];
		$routeError = '';

		try {
			$routes = $parser->getRoutes();
		}
		catch(Throwable $e) {
			$routeError = $e->getMessage();
		}

		$routeKeys = [];
		foreach($routes as $route) {
			$routeKeys[] = $route->getKey();
		}
		sort($routeKeys);

		$configurable = $parser instanceof ISchemaProvider;
		$schema = [];
		$schemaError = '';

		if($configurable) {
			try {
				$schema = $parser->getSchema();
				if(!is_array($schema)) {
					$schema = [];
				}
			}
			catch(Throwable $e) {
				$schemaError = $e->getMessage();
			}
		}

		$storedConfig = $configurable
			? $this->settingsStore->get(ParserSettings::GROUP, $parserName, [])
			: [];
		$effectiveConfig = $this->applySchemaDefaults($schema, $storedConfig);

		return [
			'name' => $parserName,
			'class' => $parser::class,
			'routeCount' => count($routes),
			'routes' => $routeKeys,
			'routeError' => $routeError,
			'configurable' => $configurable && $schema !== [],
			'schema' => $schema,
			'schemaError' => $schemaError,
			'config' => $storedConfig,
			'effectiveConfig' => $effectiveConfig,
			'configured' => $configurable && $this->settingsStore->has(ParserSettings::GROUP, $parserName),
			'enabled' => $this->readEffectiveEnabled($schema, $effectiveConfig),
			'settingsGroup' => ParserSettings::GROUP,
		];
	}

	/**
	 * @param array<string,mixed> $request
	 * @return array<string,mixed>
	 */
	private function saveParserConfig(array $request): array {
		$parserName = trim((string)($request['parser'] ?? ''));
		if($parserName === '') {
			throw new RuntimeException('Missing parser name.');
		}

		$parser = $this->getConfigurableParser($parserName);
		$schema = $parser->getSchema();
		if(!is_array($schema) || $schema === []) {
			throw new RuntimeException('Parser does not provide a configuration schema: ' . $parserName);
		}

		$config = is_array($request['config'] ?? null) ? $request['config'] : [];
		$config = $this->normalizeConfigBySchema($schema, $config);

		$this->settingsStore->set(ParserSettings::GROUP, $parserName, $config);
		$this->settingsStore->save();

		$updatedParser = $this->getParser($parserName);

		return [
			'success' => true,
			'parser' => $parserName,
			'row' => $this->buildRow($updatedParser),
			'detail' => $this->buildDetail($parserName),
		];
	}

	/**
	 * @param array<string,mixed> $request
	 * @return array<string,mixed>
	 */
	private function resetParserConfig(array $request): array {
		$parserName = trim((string)($request['parser'] ?? ''));
		if($parserName === '') {
			throw new RuntimeException('Missing parser name.');
		}

		$this->getConfigurableParser($parserName);
		$this->settingsStore->remove(ParserSettings::GROUP, $parserName);
		$this->settingsStore->save();

		$updatedParser = $this->getParser($parserName);

		return [
			'success' => true,
			'parser' => $parserName,
			'row' => $this->buildRow($updatedParser),
			'detail' => $this->buildDetail($parserName),
		];
	}

	private function getParser(string $parserName): IParser {
		$parser = $this->classMap->getInstanceByInterfaceName(IParser::class, $parserName);
		if(!$parser instanceof IParser) {
			throw new RuntimeException('Unknown ParseFlow parser: ' . $parserName);
		}

		return $parser;
	}

	private function getConfigurableParser(string $parserName): ISchemaProvider {
		$parser = $this->getParser($parserName);
		if(!$parser instanceof ISchemaProvider) {
			throw new RuntimeException('Parser does not provide a configuration schema: ' . $parserName);
		}

		return $parser;
	}

	/**
	 * @param mixed $sortPayload
	 * @return array{key:string,direction:string}
	 */
	private function normalizeSort(mixed $sortPayload): array {
		$sort = is_array($sortPayload) && isset($sortPayload[0]) && is_array($sortPayload[0])
			? $sortPayload[0]
			: (is_array($sortPayload) ? $sortPayload : []);

		$key = trim((string)($sort['key'] ?? 'name'));
		$direction = strtolower(trim((string)($sort['dir'] ?? $sort['direction'] ?? 'asc')));

		if(!in_array($key, ['name', 'class', 'routeCount', 'configurable', 'status'], true)) {
			$key = 'name';
		}
		if(!in_array($direction, ['asc', 'desc'], true)) {
			$direction = 'asc';
		}

		return [
			'key' => $key,
			'direction' => $direction,
		];
	}

	/**
	 * @param array<int,array<string,mixed>> $rows
	 * @param array{key:string,direction:string} $sort
	 */
	private function sortRows(array &$rows, array $sort): void {
		$key = $sort['key'];
		$direction = $sort['direction'] === 'desc' ? -1 : 1;

		usort($rows, static function(array $left, array $right) use ($key, $direction): int {
			$leftValue = $left[$key] ?? null;
			$rightValue = $right[$key] ?? null;

			if($key === 'routeCount') {
				$result = (int)$leftValue <=> (int)$rightValue;
			}
			elseif($key === 'configurable') {
				$result = (int)(bool)$leftValue <=> (int)(bool)$rightValue;
			}
			else {
				$result = strcasecmp((string)$leftValue, (string)$rightValue);
			}

			if($result === 0) {
				$result = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
			}

			return $result * $direction;
		});
	}

	/**
	 * @param array<string,mixed> $schema
	 * @param array<string,mixed> $config
	 * @return array<string,mixed>
	 */
	private function normalizeConfigBySchema(array $schema, array $config): array {
		$properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
		$required = is_array($schema['required'] ?? null) ? array_map('strval', $schema['required']) : [];
		$normalized = [];

		foreach($config as $key => $value) {
			$key = (string)$key;
			if(!isset($properties[$key]) || !is_array($properties[$key])) {
				throw new RuntimeException('Unknown parser configuration field: ' . $key);
			}

			$normalized[$key] = $this->normalizeSchemaValue($key, $properties[$key], $value);
		}

		foreach($required as $key) {
			if(!array_key_exists($key, $normalized)) {
				throw new RuntimeException('Missing required parser configuration field: ' . $key);
			}
		}

		return $normalized;
	}

	/**
	 * @param array<string,mixed> $definition
	 */
	private function normalizeSchemaValue(string $key, array $definition, mixed $value): mixed {
		$type = strtolower(trim((string)($definition['type'] ?? 'string')));

		if($type === 'boolean') {
			if(is_bool($value)) return $value;
			if(is_int($value) || is_float($value)) return ((int)$value) !== 0;
			$value = strtolower(trim((string)$value));
			if(in_array($value, ['1', 'true', 'yes', 'on'], true)) return true;
			if(in_array($value, ['0', 'false', 'no', 'off', ''], true)) return false;
			throw new RuntimeException('Invalid boolean value for parser configuration field: ' . $key);
		}

		if($type === 'integer') {
			if(!is_numeric($value) || (float)$value !== (float)(int)$value) {
				throw new RuntimeException('Invalid integer value for parser configuration field: ' . $key);
			}
			$number = (int)$value;
			$this->assertNumberRange($key, $definition, (float)$number);
			return $number;
		}

		if($type === 'number') {
			if(!is_numeric($value)) {
				throw new RuntimeException('Invalid numeric value for parser configuration field: ' . $key);
			}
			$number = (float)$value;
			$this->assertNumberRange($key, $definition, $number);
			return $number;
		}

		if($type === 'array') {
			if(!is_array($value)) {
				throw new RuntimeException('Invalid array value for parser configuration field: ' . $key);
			}
			$items = is_array($definition['items'] ?? null) ? $definition['items'] : ['type' => 'string'];
			$result = [];
			foreach($value as $index => $item) {
				$result[] = $this->normalizeSchemaValue($key . '[' . (string)$index . ']', $items, $item);
			}
			return $result;
		}

		if($type === 'object') {
			if(!is_array($value)) {
				throw new RuntimeException('Invalid object value for parser configuration field: ' . $key);
			}
			return $value;
		}

		$string = trim((string)$value);
		$enum = is_array($definition['enum'] ?? null) ? array_map('strval', $definition['enum']) : [];
		if($enum !== [] && !in_array($string, $enum, true)) {
			throw new RuntimeException('Invalid value for parser configuration field: ' . $key);
		}

		return $string;
	}

	/** @param array<string,mixed> $definition */
	private function assertNumberRange(string $key, array $definition, float $value): void {
		if(isset($definition['minimum']) && is_numeric($definition['minimum']) && $value < (float)$definition['minimum']) {
			throw new RuntimeException('Parser configuration field below minimum: ' . $key);
		}
		if(isset($definition['maximum']) && is_numeric($definition['maximum']) && $value > (float)$definition['maximum']) {
			throw new RuntimeException('Parser configuration field above maximum: ' . $key);
		}
	}

	/**
	 * @param array<string,mixed> $schema
	 * @param array<string,mixed> $config
	 * @return array<string,mixed>
	 */
	private function applySchemaDefaults(array $schema, array $config): array {
		$properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
		$result = $config;

		foreach($properties as $key => $definition) {
			if(!is_array($definition) || array_key_exists((string)$key, $result) || !array_key_exists('default', $definition)) {
				continue;
			}
			$result[(string)$key] = $definition['default'];
		}

		return $result;
	}

	/**
	 * @param array<string,mixed> $schema
	 * @param array<string,mixed> $effectiveConfig
	 */
	private function readEffectiveEnabled(array $schema, array $effectiveConfig): ?bool {
		$properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
		if(!isset($properties['enabled']) || !is_array($properties['enabled'])) {
			return null;
		}

		$value = $effectiveConfig['enabled'] ?? ($properties['enabled']['default'] ?? false);
		return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
	}

	private function loadTranslations(): void {
		$this->view->setPath(DIR_PLUGIN . 'ParseFlow');
		$this->view->loadBricks('Display');

		$translations = $this->view->getBricks('parser_admin_display');
		$this->translations = is_array($translations) ? $translations : [];
	}

	private function t(string $key, string $fallback): string {
		$text = trim((string)($this->translations[$key] ?? ''));
		return $text !== '' ? $text : $fallback;
	}
}
