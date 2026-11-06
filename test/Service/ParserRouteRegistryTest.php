<?php declare(strict_types=1);

namespace ParseFlow\Test\Service;

use ParseFlow\Dto\ParserRoute;
use ParseFlow\Dto\ParserRouteQuality;
use ParseFlow\Dto\ParserState;
use ParseFlow\Service\ParserRouteRegistry;
use ParseFlow\Test\Fixture\ParserClassMapFixture;
use ParseFlow\Test\Fixture\ParserProbeFixture;
use ParseFlow\Test\Fixture\ParserSecondProbeFixture;
use PHPUnit\Framework\TestCase;

final class ParserRouteRegistryTest extends TestCase {

	public function testDiscoversOrdersAndCachesParsersAndRoutes(): void {
		$a = new ParserState('string', 'text');
		$b = new ParserState('structured', 'json');
		$quality = new ParserRouteQuality();
		$second = new ParserSecondProbeFixture([new ParserRoute(ParserSecondProbeFixture::getName(), 'z', $a, $b, $quality)]);
		$first = new ParserProbeFixture([new ParserRoute(ParserProbeFixture::getName(), 'a', $a, $b, $quality)]);
		$classMap = new ParserClassMapFixture($second, $first);
		$registry = new ParserRouteRegistry($classMap);
		self::assertSame([ParserProbeFixture::getName(), ParserSecondProbeFixture::getName()], array_keys($registry->getParsers()));
		self::assertSame($first, $registry->getParser(ParserProbeFixture::getName()));
		self::assertSame(['parserprobefixture:a', 'parsersecondprobefixture:z'], array_map(static fn(ParserRoute $r): string => $r->getKey(), $registry->getRoutes()));
		self::assertSame($registry->getParsers(), $registry->getParsers());
		self::assertSame($registry->getRoutes(), $registry->getRoutes());
		self::assertSame(1, $classMap->discoveryCount);
	}

	public function testUsesDiscoveredParserWhenDirectLookupIsUnavailable(): void {
		$parser = new ParserProbeFixture([]);
		$classMap = new ParserClassMapFixture($parser);
		$classMap->directLookup = false;
		$registry = new ParserRouteRegistry($classMap);
		self::assertSame($parser, $registry->getParser(ParserProbeFixture::getName()));
		self::assertNull($registry->getParser('missing'));
	}

	public function testEmptyRegistryExposesNoParsersOrRoutes(): void {
		$registry = new ParserRouteRegistry(new ParserClassMapFixture());
		self::assertSame([], $registry->getParsers());
		self::assertSame([], $registry->getRoutes());
	}
}
