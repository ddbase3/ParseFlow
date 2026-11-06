<?php declare(strict_types=1);

namespace ParseFlow\Test\Service;

use ParseFlow\Dto\ParserRoute;
use ParseFlow\Dto\ParserRouteEvaluation;
use ParseFlow\Dto\ParserRouteQuality;
use ParseFlow\Dto\ParserState;
use ParseFlow\Dto\ParserStrategy;
use ParseFlow\Service\ParserScoreCalculator;
use PHPUnit\Framework\TestCase;

final class ParserScoreCalculatorTest extends TestCase {

	private function route(ParserRouteQuality $quality, array $features = ['local']): ParserRoute {
		return new ParserRoute('test-parser', 'route', new ParserState('string', 'text'), new ParserState('structured', 'json'), $quality, $features);
	}

	public function testQualityWeightsAndCostsAreDeterministic(): void {
		$calculator = new ParserScoreCalculator();
		$quality = new ParserRouteQuality(textQuality: 0.2, structureQuality: 0.8, priority: 100, lossy: true, requiresExternalService: true, speed: 0.2, monetaryCost: 3.0);
		$strategy = ParserStrategy::balanced();
		$weighted = $calculator->combinedQuality($quality, $strategy);
		self::assertGreaterThan(0.0, $weighted);
		self::assertLessThan(1.0, $weighted);
		$route = $this->route($quality);
		$cost = $calculator->calculate($route, ParserRouteEvaluation::supported($quality, 0.8), $strategy);
		self::assertGreaterThan(3.0, $cost);
		self::assertGreaterThan(0.0, $calculator->calculate($route, ParserRouteEvaluation::supported($quality, 0.01), $strategy));
		$noWeights = new ParserStrategy(textWeight: 0, structureWeight: 0, layoutWeight: 0, tableWeight: 0, semanticWeight: 0, stabilityWeight: 0);
		self::assertSame(1.0, $calculator->combinedQuality($quality, $noWeights));
	}

	public function testPerfectQualityCannotProduceNegativeCost(): void {
		$quality = new ParserRouteQuality(priority: 10000);
		$cost = (new ParserScoreCalculator())->calculate($this->route($quality), ParserRouteEvaluation::supported($quality), new ParserStrategy(stepPenalty: 0.0, speedWeight: 0.0));
		self::assertSame(0.0, $cost);
	}

	public function testRejectsUnsupportedAndForbiddenRoutes(): void {
		$calculator = new ParserScoreCalculator();
		$ordinary = new ParserRouteQuality();
		$lossy = new ParserRouteQuality(lossy: true);
		$external = new ParserRouteQuality(requiresExternalService: true);
		self::assertTrue($calculator->violatesConstraints($this->route($ordinary), ParserRouteEvaluation::unsupported($ordinary), ParserStrategy::balanced()));
		self::assertTrue($calculator->violatesConstraints($this->route($lossy), ParserRouteEvaluation::supported($lossy), new ParserStrategy(allowLossy: false)));
		self::assertTrue($calculator->violatesConstraints($this->route($external), ParserRouteEvaluation::supported($external), ParserStrategy::localOnly()));
		self::assertTrue($calculator->violatesConstraints($this->route($ordinary), ParserRouteEvaluation::supported($ordinary), new ParserStrategy(allowedParsers: ['some-other-parser'])));
		self::assertTrue($calculator->violatesConstraints($this->route($ordinary), ParserRouteEvaluation::supported($ordinary), new ParserStrategy(disabledParsers: ['test-parser'])));
		self::assertTrue($calculator->violatesConstraints($this->route($ordinary), ParserRouteEvaluation::supported($ordinary), new ParserStrategy(requiredFeatures: ['external'])));
		self::assertFalse($calculator->violatesConstraints($this->route($ordinary), ParserRouteEvaluation::supported($ordinary), new ParserStrategy(requiredFeatures: ['local'], allowedParsers: ['test-parser'])));
	}
}
