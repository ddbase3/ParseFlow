<?php declare(strict_types=1);

namespace ParseFlow\Test\Fixture;

use Base3\Settings\Api\ISettingsStore;

final class ParserSettingsStoreFixture implements ISettingsStore {

	private array $data = [];
	public int $saveCount = 0;

	public function get(string $group, string $name, array $default = []): array {
		return $this->data[$group][$name] ?? $default;
	}

	public function set(string $group, string $name, array $settings): void {
		$this->data[$group][$name] = $settings;
	}

	public function has(string $group, string $name): bool {
		return isset($this->data[$group][$name]);
	}

	public function remove(string $group, string $name): void {
		unset($this->data[$group][$name]);
	}

	public function getGroup(string $group): array {
		return $this->data[$group] ?? [];
	}

	public function save(): void { $this->saveCount++; }
	public function reload(): void {}
}
