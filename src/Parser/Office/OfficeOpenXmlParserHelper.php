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

namespace ParseFlow\Parser\Office;

use DOMDocument;
use DOMXPath;
use ParseFlow\Parser\Common\ParserValueHelper;
use ZipArchive;

class OfficeOpenXmlParserHelper {

	public static function docxToText(mixed $value): string {
		$xml = self::zipEntry($value, 'word/document.xml');
		return self::wordText($xml);
	}

	public static function docxToHtml(mixed $value): string {
		$xml = self::zipEntry($value, 'word/document.xml');
		$paragraphs = preg_split('/<\/?w:p[^>]*>/u', $xml) ?: [];
		$out = [];
		foreach ($paragraphs as $paragraph) {
			$text = self::wordText($paragraph);
			if (trim($text) !== '') { $out[] = '<p>' . htmlspecialchars(trim($text), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'; }
		}
		return implode("\n", $out);
	}

	public static function docxToMarkdown(mixed $value): string {
		return trim(preg_replace('/\R{2,}/u', "\n\n", self::docxToText($value)) ?? '');
	}

	public static function docxToJsonStructure(mixed $value): string {
		$text = self::docxToText($value);
		$paragraphs = array_values(array_filter(array_map('trim', preg_split('/\R+/u', $text) ?: [])));
		return ParserValueHelper::jsonEncode(['paragraphs' => $paragraphs]);
	}

	public static function docxMetadataToJson(mixed $value): string {
		return ParserValueHelper::jsonEncode(self::coreProperties($value));
	}

	public static function docxImagesToFiles(mixed $value): array {
		return self::extractZipPrefix($value, 'word/media/');
	}

	public static function xlsxToCsv(mixed $value): string {
		return ParserValueHelper::csvString(self::xlsxRows($value));
	}

	public static function xlsxToJson(mixed $value): string {
		return ParserValueHelper::jsonEncode(self::xlsxRows($value));
	}

	public static function xlsxToHtmlTables(mixed $value): string {
		$rows = self::xlsxRows($value);
		$out = ['<table>'];
		foreach ($rows as $row) { $out[] = '<tr><td>' . implode('</td><td>', array_map(fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), $row)) . '</td></tr>'; }
		$out[] = '</table>';
		return implode("\n", $out);
	}

	public static function xlsxMetadataToJson(mixed $value): string {
		return ParserValueHelper::jsonEncode(self::coreProperties($value));
	}

	public static function pptxToText(mixed $value): string {
		$slides = self::pptxSlides($value);
		return implode("\n\n", array_map(fn($slide) => implode("\n", $slide), $slides));
	}

	public static function pptxToJsonOutline(mixed $value): string {
		return ParserValueHelper::jsonEncode(self::pptxSlides($value));
	}

	public static function pptxToHtmlOutline(mixed $value): string {
		$out = [];
		foreach (self::pptxSlides($value) as $i => $slide) {
			$out[] = '<section><h2>Slide ' . ($i + 1) . '</h2><ul>';
			foreach ($slide as $line) { $out[] = '<li>' . htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>'; }
			$out[] = '</ul></section>';
		}
		return implode("\n", $out);
	}

	public static function pptxImagesToFiles(mixed $value): array {
		return self::extractZipPrefix($value, 'ppt/media/');
	}

	public static function pptxMetadataToJson(mixed $value): string {
		return ParserValueHelper::jsonEncode(self::coreProperties($value));
	}

	private static function path(mixed $value): string {
		return is_string($value) && is_file($value) ? $value : '';
	}

	private static function zipEntry(mixed $value, string $name): string {
		$path = self::path($value);
		if ($path === '') { return ''; }
		$zip = new ZipArchive();
		if ($zip->open($path) !== true) { return ''; }
		$content = $zip->getFromName($name);
		$zip->close();
		return is_string($content) ? $content : '';
	}

	private static function wordText(string $xml): string {
		preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/su', $xml, $matches);
		$parts = array_map(fn($part) => html_entity_decode(strip_tags($part), ENT_QUOTES | ENT_XML1, 'UTF-8'), $matches[1] ?? []);
		return trim(implode(' ', $parts));
	}

	private static function coreProperties(mixed $value): array {
		$xml = self::zipEntry($value, 'docProps/core.xml');
		if ($xml === '') { return []; }
		$doc = new DOMDocument();
		$previous = libxml_use_internal_errors(true);
		$doc->loadXML($xml);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);
		$out = [];
		foreach ($doc->documentElement?->childNodes ?? [] as $node) {
			if ($node instanceof \DOMElement) { $out[$node->localName] = $node->textContent; }
		}
		return $out;
	}

	private static function extractZipPrefix(mixed $value, string $prefix): array {
		$path = self::path($value);
		if ($path === '') { return []; }
		$zip = new ZipArchive();
		if ($zip->open($path) !== true) { return []; }
		$out = [];
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$name = $zip->getNameIndex($i);
			if (is_string($name) && str_starts_with($name, $prefix)) {
				$content = $zip->getFromIndex($i);
				if (is_string($content)) { $out[basename($name)] = $content; }
			}
		}
		$zip->close();
		return $out;
	}

	private static function xlsxRows(mixed $value): array {
		$shared = self::xlsxSharedStrings($value);
		$sheetXml = self::zipEntry($value, 'xl/worksheets/sheet1.xml');
		if ($sheetXml === '') { return []; }
		$doc = new DOMDocument();
		$previous = libxml_use_internal_errors(true);
		$doc->loadXML($sheetXml);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);
		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
		$rows = [];
		foreach ($xpath->query('//x:sheetData/x:row') as $rowNode) {
			$row = [];
			foreach ($xpath->query('x:c', $rowNode) as $cell) {
				$type = $cell->getAttribute('t');
				$valueNode = $xpath->query('x:v', $cell)->item(0);
				$raw = $valueNode?->textContent ?? '';
				$row[] = $type === 's' ? ($shared[(int)$raw] ?? '') : $raw;
			}
			$rows[] = $row;
		}
		return $rows;
	}

	private static function xlsxSharedStrings(mixed $value): array {
		$xml = self::zipEntry($value, 'xl/sharedStrings.xml');
		if ($xml === '') { return []; }
		$doc = new DOMDocument();
		$previous = libxml_use_internal_errors(true);
		$doc->loadXML($xml);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);
		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
		$out = [];
		foreach ($xpath->query('//x:si') as $si) { $out[] = trim($si->textContent); }
		return $out;
	}

	private static function pptxSlides(mixed $value): array {
		$path = self::path($value);
		if ($path === '') { return []; }
		$zip = new ZipArchive();
		if ($zip->open($path) !== true) { return []; }
		$slides = [];
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$name = $zip->getNameIndex($i);
			if (is_string($name) && preg_match('#^ppt/slides/slide[0-9]+\.xml$#', $name)) {
				$xml = $zip->getFromIndex($i);
				if (is_string($xml)) {
					preg_match_all('/<a:t>(.*?)<\/a:t>/su', $xml, $matches);
					$slides[$name] = array_map(fn($text) => html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_XML1, 'UTF-8'), $matches[1] ?? []);
				}
			}
		}
		ksort($slides);
		$zip->close();
		return array_values($slides);
	}
}
