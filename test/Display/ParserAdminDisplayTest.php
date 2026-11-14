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


namespace Base3\ParseFlow\Test\Display;

use Base3\CoreFoundation\Api\IClassMap;
use Base3\CoreFoundation\Api\IMvcView;
use Base3\CoreFoundation\Settings\Api\ISettingsStore;
use Base3\ParseFlow\Display\ParserAdminDisplay;
use Base3\ParseFlow\Dto\ParserRoute;
use Base3\ParseFlow\Dto\ParserRouteQuality;
use Base3\ParseFlow\Dto\ParserState;
use Base3\ParseFlow\Test\Fixture\ParserClassMapFixture;
use Base3\ParseFlow\Test\Fixture\ParserConfigurableProbeFixture;
use Base3\ParseFlow\Test\Fixture\ParserProbeFixture;
use Base3\ParseFlow\Test\Fixture\ParserSettingsStoreFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ParserAdminDisplayTest extends TestCase {

	private function display(?IClassMap $map = null, ?ISettingsStore $settings = null): ParserAdminDisplay {
		$reflect = new \ReflectionClass(ParserAdminDisplay::class);
		$display = $reflect->newInstanceWithoutConstructor();
		$reflect->getProperty('classMap')->setValue($display, $map ?? new ParserClassMapFixture());
		$reflect->getProperty('settingsStore')->setValue($display, $settings ?? new ParserSettingsStoreFixture());
		return $display;
	}

	private function invoke(ParserAdminDisplay $display, string $method, mixed ...$args): mixed {
		return (new \ReflectionMethod(ParserAdminDisplay::class, $method))->invoke($display, ...$args);
	}

	private function route(string $name): ParserRoute {
		return new ParserRoute($name, 'route', new ParserState('string', 'text'), new ParserState('structured', 'json'), new ParserRouteQuality());
	}

	public function testDisplayIdentityHelpAndNoopDataSetter(): void {
		$display = $this->display();
		$view = $this->createStub(IMvcView::class);
		(new \ReflectionProperty(ParserAdminDisplay::class, 'view'))->setValue($display, $view);
		self::assertSame('parseflowparseradmindisplay', ParserAdminDisplay::getName());
		self::assertNull($display->setData(['ignored' => true]));
		self::assertStringContainsString('ParseFlow', $display->getHelp());
	}

	public function testPageDetailsFilteringAndSortingUseActualDiscoveredParsers(): void {
		$a = new ParserProbeFixture([$this->route(ParserProbeFixture::getName())]);
		$b = new ParserConfigurableProbeFixture([$this->route(ParserConfigurableProbeFixture::getName())]);
		$display = $this->display(new ParserClassMapFixture($a, $b));
		$page = $this->invoke($display, 'buildPageResponse', ['page' => 1, 'pageSize' => 1, 'sort' => ['key' => 'name', 'dir' => 'asc']]);
		self::assertSame(2, $page['total']);
		self::assertCount(1, $page['data']);
		self::assertTrue($page['hasMore']);
		$yes = $this->invoke($display, 'buildPageResponse', ['filters' => ['configuration' => 'yes']]);
		self::assertSame(1, $yes['total']);
		self::assertSame(ParserConfigurableProbeFixture::getName(), $yes['data'][0]['name']);
		$search = $this->invoke($display, 'buildPageResponse', ['search' => ParserProbeFixture::getName()]);
		self::assertSame(1, $search['total']);
		$detail = $this->invoke($display, 'buildDetailResponse', ['parser' => ParserConfigurableProbeFixture::getName()]);
		self::assertTrue($detail['found']);
		self::assertSame(2, $detail['detail']['effectiveConfig']['limit']);
		self::assertSame(true, $detail['detail']['effectiveConfig']['enabled']);
	}

	public function testSaveAndResetPersistValidatedParserConfiguration(): void {
		$store = new ParserSettingsStoreFixture();
		$parser = new ParserConfigurableProbeFixture([$this->route(ParserConfigurableProbeFixture::getName())]);
		$display = $this->display(new ParserClassMapFixture($parser), $store);
		$result = $this->invoke($display, 'saveParserConfig', [
			'parser' => ParserConfigurableProbeFixture::getName(),
			'config' => ['limit' => '5', 'enabled' => 'yes', 'ratio' => '0.25', 'tags' => ['one', 'two'], 'mode' => 'one']
		]);
		self::assertTrue($result['success']);
		self::assertSame(5, $store->get('parseflow-parser', ParserConfigurableProbeFixture::getName())['limit']);
		self::assertTrue($store->get('parseflow-parser', ParserConfigurableProbeFixture::getName())['enabled']);
		self::assertSame(0.25, $store->get('parseflow-parser', ParserConfigurableProbeFixture::getName())['ratio']);
		self::assertSame(1, $store->saveCount);
		self::assertTrue($result['row']['configured']);
		$reset = $this->invoke($display, 'resetParserConfig', ['parser' => ParserConfigurableProbeFixture::getName()]);
		self::assertTrue($reset['success']);
		self::assertFalse($store->has('parseflow-parser', ParserConfigurableProbeFixture::getName()));
		self::assertSame(2, $store->saveCount);
	}

	public function testRefusesMissingParserNamesAndNonConfigurableParsers(): void {
		$parser = new ParserProbeFixture([$this->route(ParserProbeFixture::getName())]);
		$display = $this->display(new ParserClassMapFixture($parser));
		foreach ([['buildDetailResponse', []], ['saveParserConfig', ['parser' => '']], ['resetParserConfig', ['parser' => '']], ['saveParserConfig', ['parser' => ParserProbeFixture::getName()]], ['buildDetailResponse', ['parser' => 'missing']]] as [$method, $request]) {
			try {
				$this->invoke($display, $method, $request);
				self::fail('Expected parser administration to reject an invalid request.');
			} catch (\RuntimeException $exception) {
				self::assertNotSame('', $exception->getMessage());
			}
		}
	}

	public static function schemaValues(): iterable {
		yield 'boolean yes' => ['enabled', ['type' => 'boolean'], 'yes', true];
		yield 'boolean no' => ['enabled', ['type' => 'boolean'], 'off', false];
		yield 'boolean integer' => ['enabled', ['type' => 'boolean'], 2, true];
		yield 'boolean false' => ['enabled', ['type' => 'boolean'], false, false];
		yield 'integer' => ['limit', ['type' => 'integer', 'minimum' => 0, 'maximum' => 10], '5', 5];
		yield 'number' => ['ratio', ['type' => 'number', 'minimum' => 0, 'maximum' => 1], '0.5', 0.5];
		yield 'list' => ['tags', ['type' => 'array', 'items' => ['type' => 'integer']], ['2', 3], [2, 3]];
		yield 'object' => ['options', ['type' => 'object'], ['x' => 1], ['x' => 1]];
		yield 'enum' => ['mode', ['type' => 'string', 'enum' => ['one', 'two']], 'two', 'two'];
	}

	#[DataProvider('schemaValues')]
	public function testNormalizesSchemaValues(string $key, array $schema, mixed $input, mixed $expected): void {
		self::assertSame($expected, $this->invoke($this->display(), 'normalizeSchemaValue', $key, $schema, $input));
	}

	public static function invalidSchemaValues(): iterable {
		yield 'boolean' => ['enabled', ['type' => 'boolean'], 'perhaps'];
		yield 'integer' => ['limit', ['type' => 'integer'], '1.3'];
		yield 'min' => ['limit', ['type' => 'integer', 'minimum' => 3], 2];
		yield 'max' => ['ratio', ['type' => 'number', 'maximum' => 1], 2.0];
		yield 'number' => ['ratio', ['type' => 'number'], 'text'];
		yield 'array' => ['tags', ['type' => 'array'], 'not-array'];
		yield 'object' => ['obj', ['type' => 'object'], 'not-array'];
		yield 'enum' => ['mode', ['type' => 'string', 'enum' => ['a', 'b']], 'invalid'];
	}

	#[DataProvider('invalidSchemaValues')]
	public function testRejectsInvalidSchemaValues(string $key, array $schema, mixed $input): void {
		$this->expectException(\RuntimeException::class);
		$this->invoke($this->display(), 'normalizeSchemaValue', $key, $schema, $input);
	}

	public function testRejectsUnknownAndMissingRequiredFields(): void {
		$display = $this->display();
		$schema = (new ParserConfigurableProbeFixture([]))->getSchema();
		foreach ([['unknown' => 1, 'limit' => 2], ['enabled' => true]] as $input) {
			try {
				$this->invoke($display, 'normalizeConfigBySchema', $schema, $input);
				self::fail('Expected configuration validation to fail.');
			} catch (\RuntimeException $exception) { self::assertStringContainsString('configuration field', $exception->getMessage()); }
		}
	}
}
