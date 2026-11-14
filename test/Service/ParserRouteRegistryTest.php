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


namespace Base3\ParseFlow\Test\Service;

use Base3\ParseFlow\Dto\ParserRoute;
use Base3\ParseFlow\Dto\ParserRouteQuality;
use Base3\ParseFlow\Dto\ParserState;
use Base3\ParseFlow\Service\ParserRouteRegistry;
use Base3\ParseFlow\Test\Fixture\ParserClassMapFixture;
use Base3\ParseFlow\Test\Fixture\ParserProbeFixture;
use Base3\ParseFlow\Test\Fixture\ParserSecondProbeFixture;
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
