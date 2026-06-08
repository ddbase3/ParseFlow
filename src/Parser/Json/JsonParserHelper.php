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

namespace ParseFlow\Parser\Json;

use DOMDocument;
use ParseFlow\Parser\Common\ParserValueHelper;

class JsonParserHelper {

	public static function toXml(mixed $value): string {
		$doc = new DOMDocument('1.0', 'UTF-8');
		$root = $doc->createElement('json');
		$doc->appendChild($root);
		self::appendXml($doc, $root, ParserValueHelper::toJsonArray($value));
		$doc->formatOutput = true;
		return $doc->saveXML() ?: '';
	}

	public static function toCsv(mixed $value): string {
		$data = ParserValueHelper::toJsonArray($value);
		$rows = self::tabularRows($data);
		return ParserValueHelper::csvString($rows);
	}

	public static function toHtmlTable(mixed $value): string {
		$rows = self::tabularRows(ParserValueHelper::toJsonArray($value));
		$out = ['<table>'];
		foreach ($rows as $row) { $out[] = '<tr><td>' . implode('</td><td>', array_map('htmlspecialchars', array_map('strval', $row))) . '</td></tr>'; }
		$out[] = '</table>';
		return implode("\n", $out);
	}

	public static function toMarkdownTable(mixed $value): string {
		$rows = self::tabularRows(ParserValueHelper::toJsonArray($value));
		if (!$rows) { return ''; }
		$out = [];
		foreach ($rows as $i => $row) {
			$out[] = '| ' . implode(' | ', array_map('strval', $row)) . ' |';
			if ($i === 0) { $out[] = '| ' . implode(' | ', array_fill(0, count($row), '---')) . ' |'; }
		}
		return implode("\n", $out);
	}

	public static function toNdjson(mixed $value): string {
		$data = ParserValueHelper::toJsonArray($value);
		$items = array_is_list($data) ? $data : [$data];
		return implode("\n", array_map(fn($row) => ParserValueHelper::jsonEncode($row, false), $items));
	}

	public static function toYamlText(mixed $value): string {
		return self::yaml(ParserValueHelper::toJsonArray($value));
	}

	public static function toPhpArray(mixed $value): string {
		return '<?php return ' . var_export(ParserValueHelper::toJsonArray($value), true) . ';';
	}

	private static function appendXml(DOMDocument $doc, \DOMElement $parent, mixed $value): void {
		if (is_array($value)) {
			foreach ($value as $key => $childValue) {
				$name = is_string($key) && preg_match('/^[A-Za-z_][A-Za-z0-9_.-]*$/', $key) ? $key : 'item';
				$node = $doc->createElement($name);
				if (!is_string($key)) { $node->setAttribute('index', (string)$key); }
				$parent->appendChild($node);
				self::appendXml($doc, $node, $childValue);
			}
		} else {
			$parent->appendChild($doc->createTextNode((string)$value));
		}
	}

	private static function tabularRows(array $data): array {
		$items = array_is_list($data) ? $data : [$data];
		$headers = [];
		foreach ($items as $item) { if (is_array($item)) { $headers = array_values(array_unique(array_merge($headers, array_keys($item)))); } }
		if (!$headers) { return $items; }
		$rows = [$headers];
		foreach ($items as $item) {
			$row = [];
			foreach ($headers as $header) { $row[] = is_array($item[$header] ?? null) ? ParserValueHelper::jsonEncode($item[$header], false) : ($item[$header] ?? ''); }
			$rows[] = $row;
		}
		return $rows;
	}

	private static function yaml(mixed $data, int $indent = 0): string {
		$prefix = str_repeat('  ', $indent);
		if (!is_array($data)) { return $prefix . (string)$data . "\n"; }
		$out = '';
		foreach ($data as $key => $value) {
			$out .= $prefix . (is_int($key) ? '- ' : $key . ': ');
			if (is_array($value)) { $out .= "\n" . self::yaml($value, $indent + 1); } else { $out .= (string)$value . "\n"; }
		}
		return $out;
	}
}
