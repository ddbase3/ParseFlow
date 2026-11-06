<?php declare(strict_types=1);

namespace ParseFlow\Test\Model;

use ParseFlow\Input\AutoDetectParserInput;
use ParseFlow\Input\DocumentParserInput;
use ParseFlow\Input\ImageParserInput;
use ParseFlow\Input\PlainTextParserInput;
use ParseFlow\Input\StructuredParserInput;
use ParseFlow\Output\BinaryParserOutput;
use ParseFlow\Output\ChunksParserOutput;
use ParseFlow\Output\FilesParserOutput;
use ParseFlow\Output\ImageParserOutput;
use ParseFlow\Output\StreamParserOutput;
use ParseFlow\Output\StringParserOutput;
use ParseFlow\Output\StructuredParserOutput;
use ParseFlow\Source\BinaryParserSource;
use ParseFlow\Source\FileParserSource;
use ParseFlow\Source\StreamParserSource;
use ParseFlow\Source\StringParserSource;
use ParseFlow\Source\StructuredParserSource;
use ParseFlow\Target\DirectoryParserTarget;
use ParseFlow\Target\FileParserTarget;
use ParseFlow\Target\ReturnParserTarget;
use ParseFlow\Target\StreamParserTarget;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ParserEndpointModelsTest extends TestCase {

	public static function inputs(): iterable {
		yield 'auto' => [AutoDetectParserInput::class, 'auto', null];
		yield 'document' => [DocumentParserInput::class, 'document', 'docx'];
		yield 'image' => [ImageParserInput::class, 'image', 'jpeg'];
		yield 'plain' => [PlainTextParserInput::class, 'string', 'text'];
		yield 'structured' => [StructuredParserInput::class, 'structured', 'json'];
	}

	#[DataProvider('inputs')]
	public function testInputContract(string $class, string $type, ?string $format): void {
		$input = $class === AutoDetectParserInput::class ? new $class(['marker' => true]) : new $class($format, ['marker' => true]);
		self::assertSame($type, $input->getType());
		self::assertSame($format, $input->getFormat());
		self::assertSame(['marker' => true], $input->getOptions());
	}

	public static function outputs(): iterable {
		yield 'binary' => [BinaryParserOutput::class, 'binary', 'png'];
		yield 'chunks' => [ChunksParserOutput::class, 'chunks', 'rag'];
		yield 'files' => [FilesParserOutput::class, 'files', 'text'];
		yield 'image' => [ImageParserOutput::class, 'image', 'jpeg'];
		yield 'stream' => [StreamParserOutput::class, 'stream', 'text'];
		yield 'string' => [StringParserOutput::class, 'string', 'markdown'];
		yield 'structured' => [StructuredParserOutput::class, 'structured', 'json'];
	}

	#[DataProvider('outputs')]
	public function testOutputContract(string $class, string $type, ?string $format): void {
		$output = new $class($format, ['marker' => true]);
		self::assertSame($type, $output->getType());
		self::assertSame($format, $output->getFormat());
		self::assertSame(['marker' => true], $output->getOptions());
	}

	public function testSourceModelsPreserveDataAndMetadata(): void {
		$file = new FileParserSource('/tmp/TEST.json', ['source' => 'unit']);
		self::assertSame('file', $file->getType());
		self::assertSame('/tmp/TEST.json', $file->getPath());
		self::assertSame('/tmp/TEST.json', $file->getMetadata()['path']);
		self::assertSame('unit', $file->getMetadata()['source']);
		$string = new StringParserSource('abc', ['source' => 'unit']);
		self::assertSame('string', $string->getType());
		self::assertSame('abc', $string->getValue());
		self::assertSame('unit', $string->getMetadata()['source']);
		$binary = new BinaryParserSource("\x00\x01", ['source' => 'unit']);
		self::assertSame('binary', $binary->getType());
		self::assertSame("\x00\x01", $binary->getValue());
		self::assertSame(2, $binary->getMetadata()['size']);
		$structured = new StructuredParserSource(['value' => 5], ['origin' => 'parser']);
		self::assertSame('structured', $structured->getType());
		self::assertSame(['value' => 5], $structured->getValue());
		self::assertSame(['origin' => 'parser'], $structured->getMetadata());
		$streamResource = fopen('php://temp', 'w+');
		try {
			$stream = new StreamParserSource($streamResource, ['kind' => 'input']);
			self::assertSame('stream', $stream->getType());
			self::assertSame($streamResource, $stream->getStream());
			self::assertSame(['kind' => 'input'], $stream->getMetadata());
		} finally { fclose($streamResource); }
	}

	public function testTargetModelsPreserveOptionsAndPaths(): void {
		$file = new FileParserTarget('/tmp/result.txt', ['mode' => 'file']);
		self::assertSame('file', $file->getType());
		self::assertSame('/tmp/result.txt', $file->getPath());
		self::assertSame('file', $file->getOptions()['mode']);
		self::assertSame('/tmp/result.txt', $file->getOptions()['path']);
		$directory = new DirectoryParserTarget('/tmp/results', ['mode' => 'directory']);
		self::assertSame('directory', $directory->getType());
		self::assertSame('/tmp/results', $directory->getPath());
		self::assertSame('/tmp/results', $directory->getOptions()['path']);
		$return = new ReturnParserTarget(['streaming' => false]);
		self::assertSame('return', $return->getType());
		self::assertSame(['streaming' => false], $return->getOptions());
		$resource = fopen('php://temp', 'w+');
		try {
			$stream = new StreamParserTarget($resource, ['mode' => 'stream']);
			self::assertSame('stream', $stream->getType());
			self::assertSame($resource, $stream->getStream());
			self::assertSame('stream', $stream->getOptions()['mode']);
		} finally { fclose($resource); }
	}
}
