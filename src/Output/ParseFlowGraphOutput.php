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

namespace ParseFlow\Output;

use Base3\Api\IAssetResolver;
use Base3\Api\IOutput;
use Base3\LinkTarget\Api\ILinkTargetService;
use ParseFlow\Api\IParserService;

/**
 * Displays the discovered ParseFlow parser graph as HTML or JSON.
 */
class ParseFlowGraphOutput implements IOutput {

	public function __construct(
		private readonly IParserService $parserService,
		private readonly IAssetResolver $assetResolver,
		private readonly ILinkTargetService $linkTargetService
	) {}

	public static function getName(): string {
		return 'parseflowgraphoutput';
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		if ($out === 'json') {
			return $this->getJsonOutput();
		}

		return $this->getHtmlOutput();
	}

	private function getHtmlOutput(): string {
		$cssUrl = $this->assetResolver->resolve('plugin/ParseFlow/assets/parseflowgraph/parseflowgraph.css');
		$jsUrl = $this->assetResolver->resolve('plugin/ParseFlow/assets/parseflowgraph/parseflowgraph.js');
		$jsonUrl = $this->linkTargetService->getLink([
			'name' => self::getName(),
			'out' => 'json'
		]);

		return '\n<link rel="stylesheet" href="' . $this->escape($cssUrl) . '">\n'
			. '<div class="parseflow-graph" data-parseflow-graph data-graph-url="' . $this->escape($jsonUrl) . '">\n'
			. '\t<div class="parseflow-graph__header">\n'
			. '\t\t<div>\n'
			. '\t\t\t<h2>ParseFlow Graph</h2>\n'
			. '\t\t\t<p>Discovered parser routes, states and parser capabilities.</p>\n'
			. '\t\t</div>\n'
			. '\t\t<div class="parseflow-graph__stats" data-role="stats"></div>\n'
			. '\t</div>\n'
			. '\t<div class="parseflow-graph__toolbar">\n'
			. '\t\t<input type="search" data-role="search" placeholder="Filter parser, state or format">\n'
			. '\t\t<select data-role="parser-filter"><option value="">All parsers</option></select>\n'
			. '\t\t<select data-role="type-filter"><option value="">All state types</option></select>\n'
			. '\t</div>\n'
			. '\t<div class="parseflow-graph__message" data-role="message">Loading ParseFlow graph...</div>\n'
			. '\t<div class="parseflow-graph__canvas" data-role="canvas"></div>\n'
			. '\t<div class="parseflow-graph__grid" data-role="grid"></div>\n'
			. '</div>\n'
			. '<script src="' . $this->escape($jsUrl) . '" defer></script>\n';
	}

	private function getJsonOutput(): string {
		return json_encode($this->buildGraphData(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
	}

	private function buildGraphData(): array {
		$report = $this->parserService->explore();
		$nodes = [];
		$edges = [];
		$routes = [];

		foreach ($report->states as $state) {
			$key = (string)($state['key'] ?? $this->stateKey($state));
			$nodes[$key] = $this->nodeRow($key, $state);
		}

		foreach ($report->routes as $index => $route) {
			$from = $route['from'] ?? [];
			$to = $route['to'] ?? [];
			$fromKey = (string)($from['key'] ?? $this->stateKey($from));
			$toKey = (string)($to['key'] ?? $this->stateKey($to));

			$nodes[$fromKey] ??= $this->nodeRow($fromKey, $from);
			$nodes[$toKey] ??= $this->nodeRow($toKey, $to);

			$edge = [
				'id' => 'edge_' . $index,
				'from' => $fromKey,
				'to' => $toKey,
				'parserName' => (string)($route['parserName'] ?? ''),
				'routeName' => (string)($route['routeName'] ?? ''),
				'label' => trim((string)($route['parserName'] ?? '') . ' / ' . (string)($route['routeName'] ?? ''), ' /'),
				'quality' => $route['quality'] ?? [],
			];

			$edges[] = $edge;
			$routes[] = [
				'parserName' => $edge['parserName'],
				'routeName' => $edge['routeName'],
				'from' => $this->stateLabel($from),
				'to' => $this->stateLabel($to),
				'textQuality' => $route['quality']['textQuality'] ?? null,
				'structureQuality' => $route['quality']['structureQuality'] ?? null,
				'imageQuality' => $route['quality']['imageQuality'] ?? null,
				'speed' => $route['quality']['speed'] ?? null,
				'stability' => $route['quality']['stability'] ?? null,
			];
		}

		$nodes = array_values($nodes);
		usort($nodes, fn(array $a, array $b) => strcmp($a['label'], $b['label']));
		usort($routes, fn(array $a, array $b) => strcmp($a['parserName'] . ':' . $a['routeName'], $b['parserName'] . ':' . $b['routeName']));

		return [
			'metadata' => $report->metadata + [
				'nodeCount' => count($nodes),
				'edgeCount' => count($edges),
			],
			'warnings' => $report->warnings,
			'parsers' => $report->parsers,
			'outputs' => $report->outputs,
			'nodes' => $nodes,
			'edges' => $edges,
			'routes' => $routes,
		];
	}

	private function nodeRow(string $key, array $state): array {
		return [
			'id' => $key,
			'key' => $key,
			'label' => $this->stateLabel($state),
			'type' => (string)($state['type'] ?? ''),
			'format' => $state['format'] ?? null,
			'mimeType' => $state['mimeType'] ?? null,
			'extension' => $state['extension'] ?? null,
		];
	}

	private function stateKey(array $state): string {
		return implode('|', [
			(string)($state['type'] ?? '*'),
			(string)($state['format'] ?? '*'),
			(string)($state['mimeType'] ?? '*'),
			(string)($state['extension'] ?? '*'),
		]);
	}

	private function stateLabel(array $state): string {
		$type = (string)($state['type'] ?? '*');
		$format = $state['format'] ?? null;
		return $format === null || $format === '' ? $type : $type . '/' . $format;
	}

	private function escape(string $value): string {
		return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}
}
