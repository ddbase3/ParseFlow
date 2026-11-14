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


namespace Base3\ParseFlow\Test;

use Base3\CoreFoundation\Api\IClassMap;
use Base3\CoreFoundation\Api\IContainer;
use Base3\ParseFlow\Api\IParserService;
use Base3\ParseFlow\ParseFlowPlugin;
use Base3\ParseFlow\ParserSettings;
use Base3\ParseFlow\Service\DefaultParserService;
use Base3\ParseFlow\Test\Fixture\ParserClassMapFixture;
use PHPUnit\Framework\TestCase;

final class ParseFlowPluginTest extends TestCase {

	public function testRegistersLazyParserServiceWithoutResolvingDependenciesDuringInit(): void {
		$definitions = [];
		$container = $this->createMock(IContainer::class);
		$container->expects(self::exactly(2))->method('set')->willReturnCallback(static function(string $key, mixed $value, int $flags = 0) use (&$definitions, $container): IContainer {
			$definitions[$key] = [$value, $flags];
			return $container;
		});
		$container->expects(self::never())->method('get');
		$plugin = new ParseFlowPlugin($container);
		$plugin->init();
		self::assertSame('parseflowplugin', ParseFlowPlugin::getName());
		self::assertSame($plugin, $definitions[ParseFlowPlugin::getName()][0]);
		self::assertInstanceOf(\Closure::class, $definitions[IParserService::class][0]);
		self::assertSame(IContainer::SHARED, $definitions[IParserService::class][1]);
		self::assertSame('parseflow-parser', ParserSettings::GROUP);
	}

	public function testFactoryResolvesClassMapOnlyWhenInvoked(): void {
		$definitions = [];
		$map = new ParserClassMapFixture();
		$container = $this->createMock(IContainer::class);
		$container->method('set')->willReturnCallback(static function(string $key, mixed $definition, int $flags = 0) use (&$definitions, $container): IContainer {
			$definitions[$key] = $definition;
			return $container;
		});
		$container->expects(self::once())->method('get')->with(IClassMap::class)->willReturn($map);
		(new ParseFlowPlugin($container))->init();
		self::assertInstanceOf(DefaultParserService::class, $definitions[IParserService::class]($container));
	}
}
