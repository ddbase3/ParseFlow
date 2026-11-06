<?php declare(strict_types=1);

namespace ParseFlow\Test\Output;

use Base3\Api\IAssetResolver;
use Base3\LinkTarget\Api\ILinkTargetService;
use Base3\Translation\Api\ITranslation;
use ParseFlow\Api\IParserService;
use ParseFlow\Dto\ParserCapabilityReport;
use ParseFlow\Output\ParseFlowGraphOutput;
use PHPUnit\Framework\TestCase;

final class ParseFlowGraphOutputTest extends TestCase {

	private function createGraphOutput(ParserCapabilityReport $report): ParseFlowGraphOutput {
		$service = $this->createMock(IParserService::class);
		$service->method('explore')->willReturn($report);
		$assets = $this->createMock(IAssetResolver::class);
		$assets->method('resolve')->willReturnCallback(static fn(string $path): string => '/assets/' . basename($path));
		$links = $this->createMock(ILinkTargetService::class);
		$links->method('getLink')->willReturn('/parseflowgraphoutput.json?x=<test>');
		$translation = $this->createMock(ITranslation::class);
		$translation->method('translate')->willReturnCallback(static fn(string $set, string $section, string $key, string $fallback = ''): string => $fallback);
		return new ParseFlowGraphOutput($service, $assets, $links, $translation);
	}

	public function testEmptyGraphRendersValidJsonAndLocalizedHtml(): void {
		$output = $this->createGraphOutput(new ParserCapabilityReport());
		self::assertSame('parseflowgraphoutput', ParseFlowGraphOutput::getName());
		$json = json_decode($output->getOutput('json'), true, flags: JSON_THROW_ON_ERROR);
		self::assertSame(0, $json['metadata']['nodeCount']);
		self::assertSame(0, $json['metadata']['edgeCount']);
		self::assertSame([], $json['nodes']);
		$html = $output->getOutput('html');
		self::assertStringContainsString('data-parseflow-graph', $html);
		self::assertStringContainsString('/assets/parseflowgraph.css', $html);
		self::assertStringContainsString('/assets/parseflowgraph.js', $html);
		self::assertStringContainsString('&lt;test&gt;', $html);
		self::assertStringContainsString('ParseFlow Graph', $html);
	}

	public function testGraphBuildsNodesEdgesLabelsAndKeepsSourceMetadata(): void {
		$report = new ParserCapabilityReport(
			parsers: ['example'],
			routes: [[
				'parserName' => 'example', 'routeName' => 'to-json',
				'from' => ['type' => 'string', 'format' => 'text'],
				'to' => ['type' => 'structured', 'format' => 'json', 'key' => 'structured|json|*|*'],
				'quality' => ['textQuality' => 0.9, 'imageQuality' => 0.2]
			]],
			states: [['type' => 'string', 'format' => 'text', 'key' => 'string|text|*|*']],
			outputs: [['type' => 'structured', 'format' => 'json']],
			metadata: ['routeCount' => 1, 'parserCount' => 1]
		);
		$result = json_decode($this->createGraphOutput($report)->getOutput('json'), true, flags: JSON_THROW_ON_ERROR);
		self::assertSame(2, $result['metadata']['nodeCount']);
		self::assertSame(1, $result['metadata']['edgeCount']);
		self::assertSame('example', $result['edges'][0]['parserName']);
		self::assertSame('example / to-json', $result['edges'][0]['label']);
		self::assertSame('string/text', $result['routes'][0]['from']);
		self::assertSame('structured/json', $result['routes'][0]['to']);
		self::assertSame(0.2, $result['routes'][0]['imageQuality']);
		self::assertSame(['example'], $result['parsers']);
	}
}
