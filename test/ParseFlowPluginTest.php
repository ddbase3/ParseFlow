<?php declare(strict_types=1);

namespace ParseFlow\Test;

use Base3\Api\IClassMap;
use Base3\Api\IContainer;
use ParseFlow\Api\IParserService;
use ParseFlow\ParseFlowPlugin;
use ParseFlow\ParserSettings;
use ParseFlow\Service\DefaultParserService;
use ParseFlow\Test\Fixture\ParserClassMapFixture;
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
