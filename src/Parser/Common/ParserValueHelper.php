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

namespace ParseFlow\Parser\Common;

/**
 * Shared value conversion helpers for local parser implementations.
 */
class ParserValueHelper {

	public static function toString(mixed $value): string {
		if (is_string($value) && is_file($value)) {
			$content = file_get_contents($value);
			return $content === false ? '' : $content;
		}

		if (is_resource($value)) {
			$contents = stream_get_contents($value);
			return $contents === false ? '' : $contents;
		}

		if (is_array($value) || is_object($value)) {
			return (string)json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		return (string)$value;
	}

	public static function toJsonArray(mixed $value): array {
		if (is_array($value)) {
			return $value;
		}

		$decoded = json_decode(self::toString($value), true);
		return is_array($decoded) ? $decoded : [];
	}

	public static function jsonEncode(mixed $value, bool $pretty = true): string {
		$options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
		if ($pretty) {
			$options |= JSON_PRETTY_PRINT;
		}

		$result = json_encode($value, $options);
		return $result === false ? 'null' : $result;
	}

	public static function csvString(array $rows): string {
		$fh = fopen('php://temp', 'r+');
		foreach ($rows as $row) {
			fputcsv($fh, is_array($row) ? $row : [$row]);
		}
		rewind($fh);
		$out = stream_get_contents($fh);
		fclose($fh);
		return $out === false ? '' : $out;
	}

	public static function lines(string $value): array {
		return preg_split('/\R/u', $value) ?: [];
	}
}
