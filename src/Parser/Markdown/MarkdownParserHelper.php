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

namespace ParseFlow\Parser\Markdown;

use ParseFlow\Parser\Common\ParserValueHelper;

class MarkdownParserHelper {

	public static function toText(mixed $value): string {
		$text = ParserValueHelper::toString($value);
		$text = preg_replace('/```.*?```/su', '', $text) ?? $text;
		$text = preg_replace('/`([^`]+)`/u', '$1', $text) ?? $text;
		$text = preg_replace('/!\[([^\]]*)\]\([^)]*\)/u', '$1', $text) ?? $text;
		$text = preg_replace('/\[([^\]]+)\]\([^)]*\)/u', '$1', $text) ?? $text;
		$text = preg_replace('/^[#>*\-+\s]+/mu', '', $text) ?? $text;
		$text = str_replace(['**', '__', '*', '_', '~'], '', $text);
		return trim($text);
	}

	public static function toHtml(mixed $value): string {
		$lines = ParserValueHelper::lines(ParserValueHelper::toString($value));
		$out = [];
		foreach ($lines as $line) {
			$line = rtrim($line);
			if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $m)) {
				$level = strlen($m[1]);
				$out[] = '<h' . $level . '>' . htmlspecialchars($m[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h' . $level . '>';
			} elseif (preg_match('/^[-*+]\s+(.+)$/', $line, $m)) {
				$out[] = '<ul><li>' . htmlspecialchars($m[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li></ul>';
			} elseif ($line !== '') {
				$out[] = '<p>' . htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
			}
		}
		return implode("\n", $out);
	}

	public static function toJsonAst(mixed $value): string {
		$nodes = [];
		foreach (ParserValueHelper::lines(ParserValueHelper::toString($value)) as $line) {
			if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $m)) {
				$nodes[] = ['type' => 'heading', 'level' => strlen($m[1]), 'text' => $m[2]];
			} elseif (preg_match('/^[-*+]\s+(.+)$/', $line, $m)) {
				$nodes[] = ['type' => 'list_item', 'text' => $m[1]];
			} elseif (trim($line) !== '') {
				$nodes[] = ['type' => 'paragraph', 'text' => trim($line)];
			}
		}
		return ParserValueHelper::jsonEncode($nodes);
	}

	public static function tablesToCsv(mixed $value): string {
		$tables = self::tables(ParserValueHelper::toString($value));
		return ParserValueHelper::csvString($tables[0] ?? []);
	}

	public static function tablesToJson(mixed $value): string {
		return ParserValueHelper::jsonEncode(self::tables(ParserValueHelper::toString($value)));
	}

	public static function linksToJson(mixed $value): string {
		preg_match_all('/\[([^\]]+)\]\(([^)]+)\)/u', ParserValueHelper::toString($value), $matches, PREG_SET_ORDER);
		$out = [];
		foreach ($matches as $m) { $out[] = ['text' => $m[1], 'href' => $m[2]]; }
		return ParserValueHelper::jsonEncode($out);
	}

	public static function imagesToJson(mixed $value): string {
		preg_match_all('/!\[([^\]]*)\]\(([^)]+)\)/u', ParserValueHelper::toString($value), $matches, PREG_SET_ORDER);
		$out = [];
		foreach ($matches as $m) { $out[] = ['alt' => $m[1], 'src' => $m[2]]; }
		return ParserValueHelper::jsonEncode($out);
	}

	public static function headingsToJson(mixed $value): string {
		preg_match_all('/^(#{1,6})\s+(.+)$/mu', ParserValueHelper::toString($value), $matches, PREG_SET_ORDER);
		$out = [];
		foreach ($matches as $m) { $out[] = ['level' => strlen($m[1]), 'text' => trim($m[2])]; }
		return ParserValueHelper::jsonEncode($out);
	}

	public static function frontMatterToJson(mixed $value): string {
		$text = ParserValueHelper::toString($value);
		$out = [];
		if (preg_match('/^---\R(.*?)\R---/su', $text, $m)) {
			foreach (ParserValueHelper::lines($m[1]) as $line) {
				if (str_contains($line, ':')) {
					[$key, $val] = array_map('trim', explode(':', $line, 2));
					$out[$key] = $val;
				}
			}
		}
		return ParserValueHelper::jsonEncode($out);
	}

	public static function codeBlocksToFiles(mixed $value): array {
		preg_match_all('/```([^\n`]*)\R(.*?)```/su', ParserValueHelper::toString($value), $matches, PREG_SET_ORDER);
		$out = [];
		$i = 1;
		foreach ($matches as $m) {
			$ext = trim($m[1]) !== '' ? preg_replace('/[^a-z0-9]/i', '', trim($m[1])) : 'txt';
			$out['code-' . $i . '.' . strtolower($ext)] = $m[2];
			$i++;
		}
		return $out;
	}

	private static function tables(string $markdown): array {
		$tables = [];
		$current = [];
		foreach (ParserValueHelper::lines($markdown) as $line) {
			if (str_contains($line, '|')) {
				$cells = array_map('trim', explode('|', trim($line, '| ')));
				if ($cells && !preg_match('/^[-: ]+$/', implode('', $cells))) { $current[] = $cells; }
			} elseif ($current) {
				$tables[] = $current;
				$current = [];
			}
		}
		if ($current) { $tables[] = $current; }
		return $tables;
	}
}
