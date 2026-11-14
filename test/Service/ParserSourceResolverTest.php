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

use Base3\ParseFlow\Api\IParserSource;
use Base3\ParseFlow\Exception\ParserExecutionException;
use Base3\ParseFlow\Input\AutoDetectParserInput;
use Base3\ParseFlow\Input\DocumentParserInput;
use Base3\ParseFlow\Input\PlainTextParserInput;
use Base3\ParseFlow\Input\StructuredParserInput;
use Base3\ParseFlow\Service\ParserSourceResolver;
use Base3\ParseFlow\Source\BinaryParserSource;
use Base3\ParseFlow\Source\FileParserSource;
use Base3\ParseFlow\Source\StreamParserSource;
use Base3\ParseFlow\Source\StringParserSource;
use Base3\ParseFlow\Source\StructuredParserSource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ParserSourceResolverTest extends TestCase {

	private array $files = [];

	protected function tearDown(): void {
		foreach ($this->files as $file) { if (is_file($file)) { unlink($file); } }
		parent::tearDown();
	}

	public static function automaticFileFormats(): iterable {
		foreach ([
			'html' => ['document', 'html'], 'htm' => ['document', 'html'],
			'md' => ['string', 'markdown'], 'markdown' => ['string', 'markdown'],
			'csv' => ['string', 'csv'], 'json' => ['structured', 'json'],
			'xml' => ['structured', 'xml'], 'ini' => ['structured', 'ini'],
			'env' => ['structured', 'env'], 'rss' => ['structured', 'rss'],
			'atom' => ['structured', 'atom'], 'svg' => ['image', 'svg'],
			'gpx' => ['structured', 'gpx'], 'kml' => ['structured', 'kml'],
			'docx' => ['document', 'docx'], 'xlsx' => ['document', 'xlsx'],
			'pptx' => ['document', 'pptx'], 'odt' => ['document', 'odt'],
			'ods' => ['document', 'ods'], 'odp' => ['document', 'odp'],
			'epub' => ['document', 'epub'], 'png' => ['image', 'png'],
			'jpg' => ['image', 'jpeg'], 'jpeg' => ['image', 'jpeg'],
			'webp' => ['image', 'webp'], 'gif' => ['image', 'gif'],
			'xyz' => ['string', 'xyz']
		] as $ext => $state) { yield $ext => [$ext, ...$state]; }
	}

	#[DataProvider('automaticFileFormats')]
	public function testAutodetectsFileExtensions(string $ext, string $type, string $format): void {
		$temp = tempnam(sys_get_temp_dir(), 'parseflow_source_');
		self::assertIsString($temp);
		$path = $temp . '.' . $ext;
		self::assertTrue(rename($temp, $path));
		$this->files[] = $path;
		file_put_contents($path, 'Sample document');
		$inspection = (new ParserSourceResolver())->resolve(new FileParserSource($path, ['origin' => 'unit']), new AutoDetectParserInput());
		self::assertSame($type, $inspection->payloads[0]->state->type);
		self::assertSame($format, $inspection->payloads[0]->state->format);
		self::assertSame($ext, $inspection->extension);
		self::assertSame($path, $inspection->payloads[0]->getValue());
		self::assertSame('unit', $inspection->metadata['origin']);
		self::assertSame(strlen('Sample document'), $inspection->size);
	}

	public static function autodetectedStringTypes(): iterable {
		foreach (['html' => 'document', 'json' => 'structured', 'xml' => 'structured', 'csv' => 'structured', 'ini' => 'structured', 'env' => 'structured', 'rss' => 'structured', 'atom' => 'structured', 'sitemap' => 'structured', 'gpx' => 'structured', 'kml' => 'structured', 'markdown' => 'string', 'text' => 'string'] as $format => $type) {
			yield $format => [$format, $type];
		}
	}

	#[DataProvider('autodetectedStringTypes')]
	public function testStringInputAutodetection(string $format, string $type): void {
		$input = $this->createMock(\Base3\ParseFlow\Api\IParserInput::class);
		$input->method('getType')->willReturn('auto');
		$input->method('getFormat')->willReturn($format);
		$input->method('getOptions')->willReturn([]);
		$inspection = (new ParserSourceResolver())->resolve(new StringParserSource('contents', ['value' => true]), $input);
		self::assertSame($type, $inspection->payloads[0]->state->type);
		self::assertSame($format, $inspection->payloads[0]->state->format);
		self::assertSame('contents', $inspection->payloads[0]->getValue());
		self::assertSame(['value' => true], $inspection->payloads[0]->metadata);
	}

	public function testExplicitStringMarkdownUsesStringType(): void {
		$inspection = (new ParserSourceResolver())->resolve(new StringParserSource('# Heading'), new DocumentParserInput('markdown'));
		self::assertSame('string', $inspection->payloads[0]->state->type);
		self::assertSame('markdown', $inspection->payloads[0]->state->format);
	}

	public function testExplicitBinaryStructuredAndStreamSources(): void {
		$resolver = new ParserSourceResolver();
		$binary = $resolver->resolve(new BinaryParserSource('123', ['filename' => 'IMAGE.PNG']), new AutoDetectParserInput());
		self::assertSame('PNG', $binary->extension === null ? null : strtoupper($binary->extension));
		self::assertSame(3, $binary->size);
		self::assertSame('image', $binary->payloads[0]->state->type);
		$structured = $resolver->resolve(new StructuredParserSource(['a' => 1], ['origin' => 'test']), new StructuredParserInput(null));
		self::assertSame('structured', $structured->payloads[0]->state->format);
		self::assertSame(['a' => 1], $structured->payloads[0]->getValue());
		$autoStructured = $resolver->resolve(new StructuredParserSource(['a' => 1]), new AutoDetectParserInput());
		self::assertSame('structured', $autoStructured->payloads[0]->state->type);
		$stream = fopen('php://temp', 'w+');
		try {
			$streamInspection = $resolver->resolve(new StreamParserSource($stream, ['filename' => 'archive.json']), new AutoDetectParserInput());
			self::assertSame($stream, $streamInspection->payloads[0]->getValue());
			self::assertSame('json', $streamInspection->extension);
		} finally { fclose($stream); }
	}

	public function testRejectsUnreadableFile(): void {
		$this->expectException(ParserExecutionException::class);
		(new ParserSourceResolver())->resolve(new FileParserSource(sys_get_temp_dir() . '/parseflow_missing_unit_test_' . bin2hex(random_bytes(8)) . '.bin'), new AutoDetectParserInput());
	}

	public function testRejectsUnknownSourceType(): void {
		$source = $this->createStub(IParserSource::class);
		$source->method('getType')->willReturn('unknown');
		$this->expectException(ParserExecutionException::class);
		$this->expectExceptionMessage('Unsupported parser source type');
		(new ParserSourceResolver())->resolve($source, new PlainTextParserInput());
	}
}
