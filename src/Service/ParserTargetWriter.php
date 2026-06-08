<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of ParseFlow for BASE3 Framework.
 *
 * ParseFlow provides a graph-based parser service with discoverable
 * parser capabilities, deterministic planning and modular execution.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de/v/parseflow
 * https://github.com/ddbase3/ParseFlow
 **********************************************************************/

namespace ParseFlow\Service;

use ParseFlow\Api\IParserTarget;
use ParseFlow\Dto\ParserPayload;
use ParseFlow\Exception\ParserTargetException;
use ParseFlow\Target\DirectoryParserTarget;
use ParseFlow\Target\FileParserTarget;
use ParseFlow\Target\ReturnParserTarget;
use ParseFlow\Target\StreamParserTarget;

/**
 * Writes final payloads to return values, files, directories or streams.
 */
class ParserTargetWriter {

	public function write(ParserPayload $payload, IParserTarget $target): mixed {
		return match ($target->getType()) {
			'return' => $this->writeReturn($payload, $target),
			'file' => $this->writeFile($payload, $target),
			'directory' => $this->writeDirectory($payload, $target),
			'stream' => $this->writeStream($payload, $target),
			default => throw new ParserTargetException('Unsupported parser target type: ' . $target->getType())
		};
	}

	private function writeReturn(ParserPayload $payload, ReturnParserTarget $target): ParserPayload {
		return $payload;
	}

	private function writeFile(ParserPayload $payload, FileParserTarget $target): array {
		$value = $payload->getValue();
		$content = $this->stringify($value);
		$dir = dirname($target->getPath());
		if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
			throw new ParserTargetException('Could not create target directory: ' . $dir);
		}
		file_put_contents($target->getPath(), $content);
		return ['path' => $target->getPath()];
	}

	private function writeDirectory(ParserPayload $payload, DirectoryParserTarget $target): array {
		if (!is_dir($target->getPath()) && !mkdir($target->getPath(), 0775, true) && !is_dir($target->getPath())) {
			throw new ParserTargetException('Could not create target directory: ' . $target->getPath());
		}

		$value = $payload->getValue();
		if (!is_array($value)) {
			throw new ParserTargetException('Directory target expects an array payload.');
		}

		$written = [];
		foreach ($value as $name => $content) {
			$path = rtrim($target->getPath(), '/\\') . DIRECTORY_SEPARATOR . basename((string)$name);
			if (is_string($content) && is_file($content)) {
				copy($content, $path);
			} else {
				file_put_contents($path, $this->stringify($content));
			}
			$written[] = $path;
		}

		return ['paths' => $written];
	}

	private function writeStream(ParserPayload $payload, StreamParserTarget $target): array {
		$stream = $target->getStream();
		if (!is_resource($stream)) {
			throw new ParserTargetException('Stream target does not contain a valid resource.');
		}
		fwrite($stream, $this->stringify($payload->getValue()));
		return ['stream' => true];
	}

	private function stringify(mixed $value): string {
		if (is_array($value) || is_object($value)) {
			$content = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			if ($content === false) {
				throw new ParserTargetException('Could not encode structured payload as JSON.');
			}
			return $content;
		}

		return (string)$value;
	}
}
