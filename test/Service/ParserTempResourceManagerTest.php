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

use Base3\ParseFlow\Service\ParserTempResourceManager;
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
