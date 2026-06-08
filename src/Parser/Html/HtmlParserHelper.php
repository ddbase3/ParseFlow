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

namespace ParseFlow\Parser\Html;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use ParseFlow\Parser\Common\ParserValueHelper;

class HtmlParserHelper {

	public static function toText(mixed $value): string {
		$doc = self::document($value);
		return trim(preg_replace('/\s+/u', ' ', $doc->textContent) ?? '');
	}

	public static function toMarkdown(mixed $value): string {
		$doc = self::document($value);
		$body = $doc->getElementsByTagName('body')->item(0) ?? $doc->documentElement;
		return trim(self::nodeToMarkdown($body));
	}

	public static function toJsonDom(mixed $value): string {
		$doc = self::document($value);
		return ParserValueHelper::jsonEncode(self::nodeToArray($doc->documentElement));
	}

	public static function toXmlDom(mixed $value): string {
		$doc = self::document($value);
		$doc->formatOutput = true;
		return $doc->saveXML() ?: '';
	}

	public static function tablesToCsv(mixed $value): string {
		return ParserValueHelper::csvString(self::tables($value)[0] ?? []);
	}

	public static function tablesToJson(mixed $value): string {
		return ParserValueHelper::jsonEncode(self::tables($value));
	}

	public static function linksToJson(mixed $value): string {
		$doc = self::document($value);
		$out = [];
		foreach ($doc->getElementsByTagName('a') as $node) {
			$out[] = ['href' => $node->getAttribute('href'), 'text' => trim($node->textContent)];
		}
		return ParserValueHelper::jsonEncode($out);
	}

	public static function imagesToJson(mixed $value): string {
		$doc = self::document($value);
		$out = [];
		foreach ($doc->getElementsByTagName('img') as $node) {
			$out[] = ['src' => $node->getAttribute('src'), 'alt' => $node->getAttribute('alt'), 'title' => $node->getAttribute('title')];
		}
		return ParserValueHelper::jsonEncode($out);
	}

	public static function metaToJson(mixed $value): string {
		$doc = self::document($value);
		$out = [];
		foreach ($doc->getElementsByTagName('meta') as $node) {
			$key = $node->getAttribute('name') ?: $node->getAttribute('property');
			if ($key !== '') {
				$out[$key] = $node->getAttribute('content');
			}
		}
		return ParserValueHelper::jsonEncode($out);
	}

	public static function headingsToJson(mixed $value): string {
		$xpath = new DOMXPath(self::document($value));
		$out = [];
		foreach ($xpath->query('//h1|//h2|//h3|//h4|//h5|//h6') as $node) {
			$out[] = ['level' => (int)substr($node->nodeName, 1), 'text' => trim($node->textContent)];
		}
		return ParserValueHelper::jsonEncode($out);
	}

	public static function formsToJson(mixed $value): string {
		$doc = self::document($value);
		$out = [];
		foreach ($doc->getElementsByTagName('form') as $form) {
			$fields = [];
			foreach ($form->getElementsByTagName('input') as $input) {
				$fields[] = ['name' => $input->getAttribute('name'), 'type' => $input->getAttribute('type'), 'value' => $input->getAttribute('value')];
			}
			$out[] = ['action' => $form->getAttribute('action'), 'method' => $form->getAttribute('method'), 'fields' => $fields];
		}
		return ParserValueHelper::jsonEncode($out);
	}

	public static function jsonLd(mixed $value): string {
		$xpath = new DOMXPath(self::document($value));
		$out = [];
		foreach ($xpath->query('//script[@type="application/ld+json"]') as $node) {
			$data = json_decode(trim($node->textContent), true);
			$out[] = $data ?? trim($node->textContent);
		}
		return ParserValueHelper::jsonEncode($out);
	}

	public static function articleToText(mixed $value): string {
		$doc = self::document($value);
		$xpath = new DOMXPath($doc);
		$article = $xpath->query('//article')->item(0) ?? $doc->getElementsByTagName('body')->item(0) ?? $doc->documentElement;
		return trim(preg_replace('/\s+/u', ' ', $article->textContent) ?? '');
	}

	public static function listsToJson(mixed $value): string {
		$xpath = new DOMXPath(self::document($value));
		$out = [];
		foreach ($xpath->query('//ul|//ol') as $list) {
			$items = [];
			foreach ($list->childNodes as $child) {
				if ($child instanceof DOMElement && $child->tagName === 'li') {
					$items[] = trim($child->textContent);
				}
			}
			$out[] = ['type' => $list->nodeName, 'items' => $items];
		}
		return ParserValueHelper::jsonEncode($out);
	}

	public static function codeBlocksToFiles(mixed $value): array {
		$xpath = new DOMXPath(self::document($value));
		$out = [];
		$i = 1;
		foreach ($xpath->query('//pre|//code') as $node) {
			$text = trim($node->textContent);
			if ($text !== '') {
				$out['code-' . $i . '.txt'] = $text;
				$i++;
			}
		}
		return $out;
	}

	private static function document(mixed $value): DOMDocument {
		$html = ParserValueHelper::toString($value);
		$doc = new DOMDocument('1.0', 'UTF-8');
		$previous = libxml_use_internal_errors(true);
		$doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);
		return $doc;
	}

	private static function tables(mixed $value): array {
		$doc = self::document($value);
		$tables = [];
		foreach ($doc->getElementsByTagName('table') as $table) {
			$rows = [];
			foreach ($table->getElementsByTagName('tr') as $tr) {
				$row = [];
				foreach ($tr->childNodes as $cell) {
					if ($cell instanceof DOMElement && in_array($cell->tagName, ['td', 'th'], true)) {
						$row[] = trim($cell->textContent);
					}
				}
				if ($row) { $rows[] = $row; }
			}
			$tables[] = $rows;
		}
		return $tables;
	}

	private static function nodeToArray(?DOMNode $node): array {
		if ($node === null) { return []; }
		$data = ['name' => $node->nodeName];
		if ($node instanceof DOMElement && $node->hasAttributes()) {
			$data['attributes'] = [];
			foreach ($node->attributes as $attribute) {
				$data['attributes'][$attribute->nodeName] = $attribute->nodeValue;
			}
		}
		$children = [];
		foreach ($node->childNodes as $child) {
			if ($child->nodeType === XML_ELEMENT_NODE) {
				$children[] = self::nodeToArray($child);
			}
		}
		if ($children) { $data['children'] = $children; }
		$text = trim($node->textContent ?? '');
		if ($text !== '' && !$children) { $data['text'] = $text; }
		return $data;
	}

	private static function nodeToMarkdown(?DOMNode $node): string {
		if ($node === null) { return ''; }
		if ($node->nodeType === XML_TEXT_NODE) { return $node->textContent ?? ''; }
		$name = strtolower($node->nodeName);
		$inner = '';
		foreach ($node->childNodes as $child) { $inner .= self::nodeToMarkdown($child); }
		$inner = trim($inner);
		if (preg_match('/^h([1-6])$/', $name, $m)) { return str_repeat('#', (int)$m[1]) . ' ' . $inner . "\n\n"; }
		return match ($name) {
			'p', 'div', 'section', 'article' => $inner . "\n\n",
			'br' => "\n",
			'strong', 'b' => '**' . $inner . '**',
			'em', 'i' => '*' . $inner . '*',
			'li' => '- ' . $inner . "\n",
			'a' => '[' . $inner . '](' . (($node instanceof DOMElement) ? $node->getAttribute('href') : '') . ')',
			default => $inner
		};
	}
}
