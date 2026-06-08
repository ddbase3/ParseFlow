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

namespace ParseFlow\Service;

use Base3\Api\IClassMap;
use ParseFlow\Api\IParser;
use ParseFlow\Dto\ParserRoute;

/**
 * Loads parser instances and their routes through BASE3 ClassMap discovery.
 */
class ParserRouteRegistry {

	/** @var array<string,IParser>|null */
	private ?array $parsers = null;

	/** @var ParserRoute[]|null */
	private ?array $routes = null;

	public function __construct(private readonly IClassMap $classMap) {}

	/**
	 * @return array<string,IParser>
	 */
	public function getParsers(): array {
		if ($this->parsers !== null) {
			return $this->parsers;
		}

		$this->parsers = [];
		$instances = $this->classMap->getInstancesByInterface(IParser::class);
		foreach ($instances as $parser) {
			if ($parser instanceof IParser) {
				$this->parsers[$parser::getName()] = $parser;
			}
		}

		ksort($this->parsers);
		return $this->parsers;
	}

	public function getParser(string $parserName): ?IParser {
		$parser = $this->classMap->getInstanceByInterfaceName(IParser::class, $parserName);
		if ($parser instanceof IParser) {
			return $parser;
		}

		return $this->getParsers()[$parserName] ?? null;
	}

	/**
	 * @return ParserRoute[]
	 */
	public function getRoutes(): array {
		if ($this->routes !== null) {
			return $this->routes;
		}

		$this->routes = [];
		foreach ($this->getParsers() as $parser) {
			foreach ($parser->getRoutes() as $route) {
				$this->routes[] = $route;
			}
		}

		usort($this->routes, fn(ParserRoute $a, ParserRoute $b) => strcmp($a->getKey(), $b->getKey()));
		return $this->routes;
	}
}
