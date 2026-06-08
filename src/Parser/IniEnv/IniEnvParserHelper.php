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

namespace ParseFlow\Parser\IniEnv;

use DOMDocument;
use ParseFlow\Parser\Common\ParserValueHelper;

class IniEnvParserHelper {

	public static function iniToJson(mixed $value): string {
		return ParserValueHelper::jsonEncode(parse_ini_string(ParserValueHelper::toString($value), true, INI_SCANNER_TYPED) ?: []);
	}

	public static function iniToXml(mixed $value): string {
		$data = parse_ini_string(ParserValueHelper::toString($value), true, INI_SCANNER_TYPED) ?: [];
		$doc = new DOMDocument('1.0', 'UTF-8');
		$root = $doc->createElement('ini');
		$doc->appendChild($root);
		foreach ($data as $key => $val) {
			$node = $doc->createElement(is_array($val) ? 'section' : 'entry');
			$node->setAttribute('name', (string)$key);
			if (is_array($val)) {
				foreach ($val as $k => $v) { $child = $doc->createElement('entry', htmlspecialchars((string)$v, ENT_XML1)); $child->setAttribute('name', (string)$k); $node->appendChild($child); }
			} else {
				$node->appendChild($doc->createTextNode((string)$val));
			}
			$root->appendChild($node);
		}
		$doc->formatOutput = true;
		return $doc->saveXML() ?: '';
	}

	public static function iniToEnv(mixed $value): string {
		$data = parse_ini_string(ParserValueHelper::toString($value), true, INI_SCANNER_TYPED) ?: [];
		$out = [];
		foreach ($data as $key => $val) {
			if (is_array($val)) { foreach ($val as $k => $v) { $out[] = strtoupper((string)$key . '_' . (string)$k) . '=' . self::quote($v); } }
			else { $out[] = strtoupper((string)$key) . '=' . self::quote($val); }
		}
		return implode("\n", $out);
	}

	public static function envToJson(mixed $value): string {
		return ParserValueHelper::jsonEncode(self::envArray(ParserValueHelper::toString($value)));
	}

	public static function envToIni(mixed $value): string {
		$out = [];
		foreach (self::envArray(ParserValueHelper::toString($value)) as $key => $val) { $out[] = $key . '="' . str_replace('"', '\"', (string)$val) . '"'; }
		return implode("\n", $out);
	}

	private static function envArray(string $text): array {
		$out = [];
		foreach (ParserValueHelper::lines($text) as $line) {
			$line = trim($line);
			if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) { continue; }
			[$key, $val] = explode('=', $line, 2);
			$out[trim($key)] = trim(trim($val), '"\'');
		}
		return $out;
	}

	private static function quote(mixed $value): string {
		return '"' . str_replace('"', '\"', (string)$value) . '"';
	}
}
