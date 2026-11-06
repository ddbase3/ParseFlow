<?php declare(strict_types=1);

namespace ParseFlow\Test\Service;

use ParseFlow\Service\ParserTempResourceManager;
use PHPUnit\Framework\TestCase;

final class ParserTempResourceManagerTest extends TestCase {

	public function testCreatesAndCleansUpRegisteredTemporaryFiles(): void {
		$manager = new ParserTempResourceManager();
		$file = $manager->createTempFile('parseflow_unit_');
		self::assertFileExists($file);
		$second = tempnam(sys_get_temp_dir(), 'parseflow_existing_');
		self::assertIsString($second);
		$manager->register($second);
		$manager->cleanup();
		self::assertFileDoesNotExist($file);
		self::assertFileDoesNotExist($second);
		$manager->cleanup();
	}

	public function testIgnoresNonFilesAndCanCleanDuplicateEntries(): void {
		$manager = new ParserTempResourceManager();
		$manager->register('/this/path/never/existed/parseflow');
		$manager->cleanup();
		$property = new \ReflectionProperty(ParserTempResourceManager::class, 'resources');
		self::assertSame([], $property->getValue($manager));
	}
}
