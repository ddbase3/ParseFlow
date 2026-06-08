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

namespace ParseFlow\Parser\Csv;

use DOMDocument;
use ParseFlow\Parser\Common\ParserValueHelper;

class CsvParserHelper {

	public static function toJson(mixed $value): string {
		return ParserValueHelper::jsonEncode(self::rows($value));
	}

	public static function toXml(mixed $value): string {
		$doc = new DOMDocument('1.0', 'UTF-8');
		$root = $doc->createElement('rows');
		$doc->appendChild($root);
		foreach (self::rows($value) as $rowData) {
			$row = $doc->createElement('row');
			foreach ($rowData as $key => $cellValue) {
				$cell = $doc->createElement('cell');
				$cell->setAttribute('name', (string)$key);
				$cell->appendChild($doc->createTextNode((string)$cellValue));
				$row->appendChild($cell);
			}
			$root->appendChild($row);
		}
		$doc->formatOutput = true;
		return $doc->saveXML() ?: '';
	}

	public static function toHtmlTable(mixed $value): string {
		$rows = self::rows($value);
		$out = ['<table>'];
		foreach ($rows as $row) {
			$out[] = '<tr><td>' . implode('</td><td>', array_map(fn($cell) => htmlspecialchars((string)$cell, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), $row)) . '</td></tr>';
		}
		$out[] = '</table>';
		return implode("\n", $out);
	}

	public static function toMarkdownTable(mixed $value): string {
		$rows = self::rows($value);
		if (!$rows) { return ''; }
		$out = [];
		foreach ($rows as $i => $row) {
			$out[] = '| ' . implode(' | ', array_map('strval', $row)) . ' |';
			if ($i === 0) { $out[] = '| ' . implode(' | ', array_fill(0, count($row), '---')) . ' |'; }
		}
		return implode("\n", $out);
	}

	public static function toNdjson(mixed $value): string {
		return implode("\n", array_map(fn($row) => ParserValueHelper::jsonEncode($row, false), self::rows($value)));
	}

	public static function toIni(mixed $value): string {
		$out = [];
		foreach (self::rows($value) as $index => $row) {
			$out[] = '[row_' . ($index + 1) . ']';
			foreach ($row as $key => $val) { $out[] = $key . '="' . str_replace('"', '\"', (string)$val) . '"'; }
		}
		return implode("\n", $out);
	}

	private static function rows(mixed $value): array {
		$text = ParserValueHelper::toString($value);
		$fh = fopen('php://temp', 'r+');
		fwrite($fh, $text);
		rewind($fh);
		$rows = [];
		while (($row = fgetcsv($fh)) !== false) {
			$rows[] = $row;
		}
		fclose($fh);
		return $rows;
	}
}
