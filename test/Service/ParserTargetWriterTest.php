<?php declare(strict_types=1);

namespace ParseFlow\Test\Service;

use ParseFlow\Api\IParserTarget;
use ParseFlow\Dto\ParserPayload;
use ParseFlow\Dto\ParserState;
use ParseFlow\Exception\ParserTargetException;
use ParseFlow\Service\ParserTargetWriter;
use ParseFlow\Target\DirectoryParserTarget;
use ParseFlow\Target\FileParserTarget;
use ParseFlow\Target\ReturnParserTarget;
use ParseFlow\Target\StreamParserTarget;
use PHPUnit\Framework\TestCase;

final class ParserTargetWriterTest extends TestCase {

	private array $created = [];

	protected function tearDown(): void {
		foreach (array_reverse($this->created) as $path) {
			if (is_file($path)) { unlink($path); }
			if (is_dir($path)) { rmdir($path); }
		}
		parent::tearDown();
	}

	private function payload(mixed $value): ParserPayload {
		return new ParserPayload(new ParserState('structured', 'json'), $value);
	}

	private function directory(): string {
		$path = sys_get_temp_dir() . '/parseflow_target_' . bin2hex(random_bytes(7));
		$this->created[] = $path;
		return $path;
	}

	public function testReturnTargetDoesNotModifyPayload(): void {
		$payload = $this->payload(['hello' => 'world']);
		self::assertSame($payload, (new ParserTargetWriter())->write($payload, new ReturnParserTarget()));
	}

	public function testFileTargetCreatesDirectoriesAndWritesStructuredData(): void {
		$folder = $this->directory();
		$file = $folder . '/nested/result.json';
		$this->created[] = $folder . '/nested';
		$this->created[] = $file;
		$result = (new ParserTargetWriter())->write($this->payload(['hello' => 'world']), new FileParserTarget($file));
		self::assertSame(['path' => $file], $result);
		self::assertSame(['hello' => 'world'], json_decode((string)file_get_contents($file), true));
	}

	public function testDirectoryTargetWritesValuesAndCopiesExistingFiles(): void {
		$folder = $this->directory();
		$source = tempnam(sys_get_temp_dir(), 'parseflow_copy_');
		self::assertIsString($source);
		$this->created[] = $source;
		file_put_contents($source, 'copied');
		$this->created[] = $folder . '/out.txt';
		$this->created[] = $folder . '/data.json';
		$this->created[] = $folder . '/source.bin';
		$result = (new ParserTargetWriter())->write($this->payload([
			'../out.txt' => 'escaped', 'data.json' => ['a' => 1], 'source.bin' => $source
		]), new DirectoryParserTarget($folder));
		self::assertCount(3, $result['paths']);
		self::assertSame('escaped', file_get_contents($folder . '/out.txt'));
		self::assertSame(['a' => 1], json_decode((string)file_get_contents($folder . '/data.json'), true));
		self::assertSame('copied', file_get_contents($folder . '/source.bin'));
	}

	public function testDirectoryTargetRejectsNonArray(): void {
		$folder = $this->directory();
		$this->expectException(ParserTargetException::class);
		$this->expectExceptionMessage('expects an array');
		(new ParserTargetWriter())->write($this->payload('text'), new DirectoryParserTarget($folder));
	}

	public function testStreamTargetWritesBytes(): void {
		$stream = fopen('php://temp', 'w+');
		try {
			self::assertSame(['stream' => true], (new ParserTargetWriter())->write($this->payload('abc'), new StreamParserTarget($stream)));
			rewind($stream);
			self::assertSame('abc', stream_get_contents($stream));
		} finally { fclose($stream); }
	}

	public function testStreamTargetRejectsInvalidResource(): void {
		$this->expectException(ParserTargetException::class);
		(new ParserTargetWriter())->write($this->payload('text'), new StreamParserTarget(null));
	}

	public function testUnknownTargetIsRejected(): void {
		$target = $this->createStub(IParserTarget::class);
		$target->method('getType')->willReturn('invalid');
		$this->expectException(ParserTargetException::class);
		$this->expectExceptionMessage('Unsupported parser target');
		(new ParserTargetWriter())->write($this->payload('text'), $target);
	}

	public function testRejectsNonEncodableValue(): void {
		$stream = fopen('php://temp', 'w+');
		try {
			$this->expectException(ParserTargetException::class);
			(new ParserTargetWriter())->write($this->payload(['invalid' => "\xB1\x31"]), new StreamParserTarget($stream));
		} finally { fclose($stream); }
	}
}
