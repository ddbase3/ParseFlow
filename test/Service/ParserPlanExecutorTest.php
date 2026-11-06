<?php declare(strict_types=1);

namespace ParseFlow\Test\Service;

use ParseFlow\Dto\ParserPayload;
use ParseFlow\Dto\ParserPlan;
use ParseFlow\Dto\ParserPlanStep;
use ParseFlow\Dto\ParserRequest;
use ParseFlow\Dto\ParserRoute;
use ParseFlow\Dto\ParserRouteEvaluation;
use ParseFlow\Dto\ParserRouteQuality;
use ParseFlow\Dto\ParserState;
use ParseFlow\Exception\ParserExecutionException;
use ParseFlow\Input\PlainTextParserInput;
use ParseFlow\Output\StructuredParserOutput;
use ParseFlow\Service\ParserPlanExecutor;
use ParseFlow\Service\ParserRouteRegistry;
use ParseFlow\Service\ParserTempResourceManager;
use ParseFlow\Source\StringParserSource;
use ParseFlow\Target\ReturnParserTarget;
use ParseFlow\Test\Fixture\ParserClassMapFixture;
use ParseFlow\Test\Fixture\ParserProbeFixture;
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
