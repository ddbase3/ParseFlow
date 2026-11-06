<?php declare(strict_types=1);

namespace ParseFlow\Test\Fixture;

use Base3\Api\IClassMap;
use ParseFlow\Api\IParser;

final class ParserClassMapFixture implements IClassMap {

	/** @var array<string,IParser> */
	private array $parsers = [];
	private mixed $missing = null;
	public bool $directLookup = true;
	public int $discoveryCount = 0;

	public function __construct(IParser ...$parsers) {
		foreach ($parsers as $parser) { $this->parsers[$parser::getName()] = $parser; }
	}

	public function instantiate(string $class) { return null; }
	public function instantiateWith(string $class, array $arguments = []) { return null; }
	public function generate($regenerate = false): void {}
	public function getApps() { return ['ParseFlow']; }
	public function &getInstances(array $criteria = []) {
		$this->discoveryCount++;
		$instances = array_values($this->parsers);
		return $instances;
	}
	public function &getInstancesByInterface($interface) {
		$this->discoveryCount++;
		$instances = array_values($this->parsers);
		return $instances;
	}
	public function &getInstancesByAppInterface($app, $interface, $retry = false) {
		return $this->getInstancesByInterface($interface);
	}
	public function &getInstanceByAppName($app, $name, $retry = false) {
		return $this->getInstanceByInterfaceName(IParser::class, $name);
	}
	public function getClassByInterfaceName(string $interface, string $name): ?string {
		return isset($this->parsers[$name]) ? $this->parsers[$name]::class : null;
	}
	public function &getInstanceByInterfaceName($interface, $name, $retry = false) {
		if ($this->directLookup && isset($this->parsers[$name])) { return $this->parsers[$name]; }
		return $this->missing;
	}
	public function &getInstanceByAppInterfaceName($app, $interface, $name, $retry = false) {
		return $this->getInstanceByInterfaceName($interface, $name, $retry);
	}
	public function getPlugins() { return []; }
}
