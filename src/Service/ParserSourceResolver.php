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

use ParseFlow\Api\IParserInput;
use ParseFlow\Api\IParserSource;
use ParseFlow\Dto\ParserPayload;
use ParseFlow\Dto\ParserSourceInspection;
use ParseFlow\Dto\ParserState;
use ParseFlow\Exception\ParserExecutionException;
use ParseFlow\Source\BinaryParserSource;
use ParseFlow\Source\FileParserSource;
use ParseFlow\Source\StreamParserSource;
use ParseFlow\Source\StringParserSource;

/**
 * Resolves transport sources into initial parser payloads.
 */
class ParserSourceResolver {

	public function resolve(IParserSource $source, IParserInput $input): ParserSourceInspection {
		return match ($source->getType()) {
			'file' => $this->resolveFile($source, $input),
			'string' => $this->resolveString($source, $input),
			'binary' => $this->resolveBinary($source, $input),
			'stream' => $this->resolveStream($source, $input),
			default => throw new ParserExecutionException('Unsupported parser source type: ' . $source->getType())
		};
	}

	private function resolveFile(FileParserSource $source, IParserInput $input): ParserSourceInspection {
		$path = $source->getPath();
		if (!is_file($path) || !is_readable($path)) {
			throw new ParserExecutionException('Parser source file is not readable: ' . $path);
		}

		$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION)) ?: null;
		$state = $this->stateFromInput($input, $extension);
		$state = new ParserState($state->type, $state->format, $this->detectMime($path), $extension, $state->features, $state->metadata);
		$payload = new ParserPayload($state, $path, $source->getMetadata() + ['path' => $path]);

		return new ParserSourceInspection([$payload], $state->mimeType, $extension, filesize($path) ?: null, $source->getMetadata());
	}

	private function resolveString(StringParserSource $source, IParserInput $input): ParserSourceInspection {
		$format = $input->getFormat() ?? 'text';
		$type = $input->getType() === 'auto' ? $this->detectStringType($format, $source->getValue()) : $input->getType();
		if ($type === 'document' && $format === 'markdown') { $type = 'string'; }
		$state = new ParserState($type, $format);
		return new ParserSourceInspection([new ParserPayload($state, $source->getValue(), $source->getMetadata())], metadata: $source->getMetadata());
	}

	private function resolveBinary(BinaryParserSource $source, IParserInput $input): ParserSourceInspection {
		$metadata = $source->getMetadata();
		$extension = isset($metadata['filename']) ? strtolower(pathinfo((string)$metadata['filename'], PATHINFO_EXTENSION)) : null;
		$state = $this->stateFromInput($input, $extension);
		return new ParserSourceInspection([new ParserPayload($state, $source->getValue(), $metadata)], extension: $extension, size: strlen($source->getValue()), metadata: $metadata);
	}

	private function resolveStream(StreamParserSource $source, IParserInput $input): ParserSourceInspection {
		$metadata = $source->getMetadata();
		$extension = isset($metadata['filename']) ? strtolower(pathinfo((string)$metadata['filename'], PATHINFO_EXTENSION)) : null;
		$state = $this->stateFromInput($input, $extension);
		return new ParserSourceInspection([new ParserPayload($state, $source->getStream(), $metadata)], extension: $extension, metadata: $metadata);
	}

	private function stateFromInput(IParserInput $input, ?string $extension): ParserState {
		if ($input->getType() !== 'auto') {
			return new ParserState($input->getType(), $input->getFormat() ?? $extension);
		}

		return match ($extension) {
			'html', 'htm' => new ParserState('document', 'html'),
			'md', 'markdown' => new ParserState('string', 'markdown'),
			'csv' => new ParserState('string', 'csv'),
			'json' => new ParserState('structured', 'json'),
			'xml' => new ParserState('structured', 'xml'),
			'ini' => new ParserState('structured', 'ini'),
			'env' => new ParserState('structured', 'env'),
			'rss' => new ParserState('structured', 'rss'),
			'atom' => new ParserState('structured', 'atom'),
			'svg' => new ParserState('image', 'svg'),
			'gpx' => new ParserState('structured', 'gpx'),
			'kml' => new ParserState('structured', 'kml'),
			'docx', 'xlsx', 'pptx', 'odt', 'ods', 'odp', 'epub' => new ParserState('document', $extension),
			'png', 'jpg', 'jpeg', 'webp', 'gif' => new ParserState('image', $extension === 'jpg' ? 'jpeg' : $extension),
			default => new ParserState('string', $extension ?: 'text')
		};
	}

	private function detectStringType(string $format, string $value): string {
		return match ($format) {
			'html' => 'document',
			'json', 'xml', 'csv', 'ini', 'env', 'rss', 'atom', 'sitemap', 'gpx', 'kml' => 'structured',
			default => 'string'
		};
	}

	private function detectMime(string $path): ?string {
		if (!function_exists('finfo_open')) {
			return null;
		}

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		if ($finfo === false) {
			return null;
		}

		$mime = finfo_file($finfo, $path) ?: null;
		finfo_close($finfo);
		return $mime;
	}
}
