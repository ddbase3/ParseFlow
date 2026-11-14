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

use Base3\ParseFlow\Dto\ParserPayload;
use Base3\ParseFlow\Dto\ParserPlan;
use Base3\ParseFlow\Dto\ParserPlanStep;
use Base3\ParseFlow\Dto\ParserRequest;
use Base3\ParseFlow\Dto\ParserRoute;
use Base3\ParseFlow\Dto\ParserRouteEvaluation;
use Base3\ParseFlow\Dto\ParserRouteQuality;
use Base3\ParseFlow\Dto\ParserState;
use Base3\ParseFlow\Exception\ParserExecutionException;
use Base3\ParseFlow\Input\PlainTextParserInput;
use Base3\ParseFlow\Output\StructuredParserOutput;
use Base3\ParseFlow\Service\ParserPlanExecutor;
use Base3\ParseFlow\Service\ParserRouteRegistry;
use Base3\ParseFlow\Service\ParserTempResourceManager;
use Base3\ParseFlow\Source\StringParserSource;
use Base3\ParseFlow\Target\ReturnParserTarget;
use Base3\ParseFlow\Test\Fixture\ParserClassMapFixture;
use Base3\ParseFlow\Test\Fixture\ParserProbeFixture;
use PHPUnit\Framework\TestCase;

final class ParserPlanExecutorTest extends TestCase {

	private function request(array $options = []): ParserRequest {
		return new ParserRequest(new StringParserSource('raw'), new PlainTextParserInput(), new StructuredParserOutput('json'), new ReturnParserTarget(), options: $options);
	}

	private function plan(string $name = 'parserprobefixture'): ParserPlan {
		$a = new ParserState('string', 'text');
		$b = new ParserState('structured', 'json');
		$q = new ParserRouteQuality();
		$route = new ParserRoute($name, 'convert', $a, $b, $q);
		return new ParserPlan($a, $b, [new ParserPlanStep($name, $route, $a, $b, 0.1, ParserRouteEvaluation::supported($q))]);
	}

	public function testExecutesPlanAndCleansUpTemporaryResource(): void {
		$file = tempnam(sys_get_temp_dir(), 'parseflow_executor_');
		self::assertIsString($file);
		$probe = new ParserProbeFixture([], temporaryResource: $file);
		$executor = new ParserPlanExecutor(new ParserRouteRegistry(new ParserClassMapFixture($probe)), new ParserTempResourceManager());
		$result = $executor->execute($this->request(), $this->plan(), [new ParserPayload(new ParserState('string', 'text'), 'raw', ['origin' => true])]);
		self::assertSame('parsed:raw', $result->getValue());
		self::assertSame(['origin' => true], $result->metadata);
		self::assertFileDoesNotExist($file);
	}

	public function testKeepsTemporaryResourcesWhenRequested(): void {
		$file = tempnam(sys_get_temp_dir(), 'parseflow_keep_');
		self::assertIsString($file);
		try {
			$probe = new ParserProbeFixture([], temporaryResource: $file);
			$executor = new ParserPlanExecutor(new ParserRouteRegistry(new ParserClassMapFixture($probe)), new ParserTempResourceManager());
			$executor->execute($this->request(['keepTemporaryResources' => true]), $this->plan(), [new ParserPayload(new ParserState('string', 'text'), 'raw')]);
			self::assertFileExists($file);
		} finally { unlink($file); }
	}

	public function testRejectsMissingInitialPayload(): void {
		$executor = new ParserPlanExecutor(new ParserRouteRegistry(new ParserClassMapFixture()), new ParserTempResourceManager());
		$this->expectException(ParserExecutionException::class);
		$this->expectExceptionMessage('Initial payload');
		$executor->execute($this->request(), $this->plan(), []);
	}

	public function testRejectsMissingParser(): void {
		$executor = new ParserPlanExecutor(new ParserRouteRegistry(new ParserClassMapFixture()), new ParserTempResourceManager());
		$this->expectException(ParserExecutionException::class);
		$this->expectExceptionMessage('Parser not found');
		$executor->execute($this->request(), $this->plan('missing'), [new ParserPayload(new ParserState('string', 'text'), 'raw')]);
	}

	public function testPropagatesParserFailure(): void {
		$probe = new ParserProbeFixture([], fail: true);
		$executor = new ParserPlanExecutor(new ParserRouteRegistry(new ParserClassMapFixture($probe)), new ParserTempResourceManager());
		$this->expectException(ParserExecutionException::class);
		$this->expectExceptionMessage('fixture failed');
		$executor->execute($this->request(), $this->plan(), [new ParserPayload(new ParserState('string', 'text'), 'raw')]);
	}
}
