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


namespace Base3\ParseFlow\Test\Parser;

use Base3\ParseFlow\Dto\ParserPayload;
use Base3\ParseFlow\Dto\ParserPlanStep;
use Base3\ParseFlow\Dto\ParserPlanningContext;
use Base3\ParseFlow\Dto\ParserRequest;
use Base3\ParseFlow\Dto\ParserRouteEvaluation;
use Base3\ParseFlow\Dto\ParserStrategy;
use Base3\ParseFlow\Dto\ParserStepRequest;
use Base3\ParseFlow\Exception\ParserExecutionException;
use Base3\ParseFlow\Input\AutoDetectParserInput;
use Base3\ParseFlow\Output\StringParserOutput;
use Base3\ParseFlow\Parser\AbstractSingleRouteParser;
use Base3\ParseFlow\Source\StringParserSource;
use Base3\ParseFlow\Target\ReturnParserTarget;
use Base3\ParseFlow\Test\Fixture\ParserSampleFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ParserCatalogTest extends TestCase {

	public static function parserClasses(): iterable {
		$root = dirname(__DIR__, 2) . '/src/Parser';
		foreach (glob($root . '/*/*.php') ?: [] as $path) {
			$namespace = basename(dirname($path));
			$class = 'Base3\ParseFlow\\Parser\\' . $namespace . '\\' . basename($path, '.php');
			if (is_subclass_of($class, AbstractSingleRouteParser::class)) {
				yield $namespace . '/' . basename($path, '.php') => [$class];
			}
		}
	}

	#[DataProvider('parserClasses')]
	public function testEveryDiscoveredParserHasOneWellFormedRouteAndExecutableCapability(string $class): void {
		$parser = new $class();
		self::assertInstanceOf(AbstractSingleRouteParser::class, $parser);
		self::assertSame(strtolower((new \ReflectionClass($class))->getShortName()), $parser::getName());
		$routes = $parser->getRoutes();
		self::assertCount(1, $routes);
		$route = $routes[0];
		self::assertSame($parser::getName(), $route->parserName);
		self::assertSame($parser::getName(), $route->routeName);
		self::assertSame('local', $route->features[0]);
		self::assertNotSame('', $route->from->type);
		self::assertNotSame('', $route->to->type);
		$request = new ParserRequest(new StringParserSource('fixture'), new AutoDetectParserInput(), new StringParserOutput(), new ReturnParserTarget());
		$value = ParserSampleFixture::value($route->from);
		$payload = new ParserPayload($route->from, $value, ['origin' => 'catalog']);
		$evaluation = $parser->evaluate($route, new ParserPlanningContext($request, $payload, ParserStrategy::balanced()));
		self::assertInstanceOf(ParserRouteEvaluation::class, $evaluation);
		if (!$evaluation->supported) {
			$this->expectException(ParserExecutionException::class);
			$parser->execute(new ParserStepRequest($request, new ParserPlanStep($route->parserName, $route, $route->from, $route->to, 0.1, $evaluation), $payload));
			return;
		}

		$archive = null;
		if (str_contains($class, '\\Office\\')) {
			$archive = ParserSampleFixture::officeFile();
			$payload = new ParserPayload($route->from, $archive, ['origin' => 'catalog']);
		}

		try {
			$step = new ParserPlanStep($route->parserName, $route, $route->from, $route->to, 0.1, $evaluation);
			$result = $parser->execute(new ParserStepRequest($request, $step, $payload));
			self::assertSame($route->to->getKey(), $result->payload->state->getKey());
			self::assertSame(['origin' => 'catalog'], $result->payload->metadata);
			self::assertTrue(is_array($result->payload->getValue()) || is_string($result->payload->getValue()));
			self::assertSame([], $result->temporaryResources);
		} finally {
			if ($archive !== null && is_file($archive)) { unlink($archive); }
		}
	}
}
