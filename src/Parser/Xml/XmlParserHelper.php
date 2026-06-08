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

namespace ParseFlow\Parser\Xml;

use DOMDocument;
use DOMElement;
use SimpleXMLElement;
use ParseFlow\Parser\Common\ParserValueHelper;

class XmlParserHelper {

	public static function toJson(mixed $value): string {
		return ParserValueHelper::jsonEncode(self::xmlArray(self::simple($value)));
	}

	public static function toText(mixed $value): string {
		return trim(preg_replace('/\s+/u', ' ', dom_import_simplexml(self::simple($value))->textContent ?? '') ?? '');
	}

	public static function toHtml(mixed $value): string {
		$xml = self::simple($value);
		return '<pre>' . htmlspecialchars(self::simpleXmlString($xml), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
	}

	public static function toCsv(mixed $value): string {
		$rows = [];
		foreach (self::simple($value)->children() as $child) {
			$rows[] = array_map('strval', (array)$child);
		}
		return ParserValueHelper::csvString($rows);
	}

	public static function toMarkdown(mixed $value): string {
		return '```xml' . "\n" . self::simpleXmlString(self::simple($value)) . "\n" . '```';
	}

	public static function rssToJson(mixed $value): string {
		$xml = self::simple($value);
		$out = [];
		foreach ($xml->channel->item ?? [] as $item) {
			$out[] = ['title' => (string)$item->title, 'link' => (string)$item->link, 'description' => (string)$item->description, 'pubDate' => (string)$item->pubDate];
		}
		return ParserValueHelper::jsonEncode($out);
	}

	public static function rssToHtml(mixed $value): string {
		$items = json_decode(self::rssToJson($value), true) ?: [];
		$out = ['<ul>'];
		foreach ($items as $item) { $out[] = '<li><a href="' . htmlspecialchars($item['link'] ?? '', ENT_QUOTES) . '">' . htmlspecialchars($item['title'] ?? '', ENT_QUOTES) . '</a></li>'; }
		$out[] = '</ul>';
		return implode("\n", $out);
	}

	public static function rssToMarkdown(mixed $value): string {
		$items = json_decode(self::rssToJson($value), true) ?: [];
		return implode("\n", array_map(fn($item) => '- [' . ($item['title'] ?? '') . '](' . ($item['link'] ?? '') . ')', $items));
	}

	public static function atomToJson(mixed $value): string {
		$xml = self::simple($value);
		$xml->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
		$out = [];
		foreach ($xml->entry ?? [] as $entry) {
			$link = '';
			foreach ($entry->link ?? [] as $l) { if ((string)$l['href'] !== '') { $link = (string)$l['href']; break; } }
			$out[] = ['title' => (string)$entry->title, 'link' => $link, 'summary' => (string)$entry->summary, 'updated' => (string)$entry->updated];
		}
		return ParserValueHelper::jsonEncode($out);
	}

	public static function atomToHtml(mixed $value): string {
		$items = json_decode(self::atomToJson($value), true) ?: [];
		$out = ['<ul>'];
		foreach ($items as $item) { $out[] = '<li><a href="' . htmlspecialchars($item['link'] ?? '', ENT_QUOTES) . '">' . htmlspecialchars($item['title'] ?? '', ENT_QUOTES) . '</a></li>'; }
		$out[] = '</ul>';
		return implode("\n", $out);
	}

	public static function atomToMarkdown(mixed $value): string {
		$items = json_decode(self::atomToJson($value), true) ?: [];
		return implode("\n", array_map(fn($item) => '- [' . ($item['title'] ?? '') . '](' . ($item['link'] ?? '') . ')', $items));
	}

	public static function sitemapToJson(mixed $value): string {
		$xml = self::simple($value);
		$out = [];
		foreach ($xml->url ?? [] as $url) {
			$out[] = ['loc' => (string)$url->loc, 'lastmod' => (string)$url->lastmod, 'changefreq' => (string)$url->changefreq, 'priority' => (string)$url->priority];
		}
		return ParserValueHelper::jsonEncode($out);
	}

	public static function sitemapToCsv(mixed $value): string {
		$items = json_decode(self::sitemapToJson($value), true) ?: [];
		$rows = [['loc', 'lastmod', 'changefreq', 'priority']];
		foreach ($items as $item) { $rows[] = [$item['loc'] ?? '', $item['lastmod'] ?? '', $item['changefreq'] ?? '', $item['priority'] ?? '']; }
		return ParserValueHelper::csvString($rows);
	}

	public static function svgToJson(mixed $value): string {
		$doc = self::dom($value);
		return ParserValueHelper::jsonEncode(self::nodeArray($doc->documentElement));
	}

	public static function svgToText(mixed $value): string {
		$doc = self::dom($value);
		return trim($doc->textContent);
	}

	public static function svgToHtmlPreview(mixed $value): string {
		return '<div class="svg-preview">' . ParserValueHelper::toString($value) . '</div>';
	}

	public static function svgMetadataToJson(mixed $value): string {
		$doc = self::dom($value);
		$root = $doc->documentElement;
		return ParserValueHelper::jsonEncode([
			'width' => $root?->getAttribute('width'),
			'height' => $root?->getAttribute('height'),
			'viewBox' => $root?->getAttribute('viewBox'),
			'title' => $doc->getElementsByTagName('title')->item(0)?->textContent,
			'description' => $doc->getElementsByTagName('desc')->item(0)?->textContent,
		]);
	}

	public static function gpxToGeoJson(mixed $value): string {
		$xml = self::simple($value);
		$features = [];
		foreach ($xml->wpt ?? [] as $wpt) {
			$features[] = self::pointFeature((float)$wpt['lon'], (float)$wpt['lat'], ['name' => (string)$wpt->name]);
		}
		return ParserValueHelper::jsonEncode(['type' => 'FeatureCollection', 'features' => $features]);
	}

	public static function gpxToCsv(mixed $value): string {
		$xml = self::simple($value);
		$rows = [['name', 'lat', 'lon']];
		foreach ($xml->wpt ?? [] as $wpt) { $rows[] = [(string)$wpt->name, (string)$wpt['lat'], (string)$wpt['lon']]; }
		return ParserValueHelper::csvString($rows);
	}

	public static function kmlToGeoJson(mixed $value): string {
		$xml = self::simple($value);
		$features = [];
		foreach ($xml->xpath('//*[local-name()="Placemark"]') ?: [] as $placemark) {
			$name = (string)($placemark->name ?? '');
			$coords = trim((string)($placemark->Point->coordinates ?? ''));
			if ($coords !== '') {
				[$lon, $lat] = array_pad(explode(',', $coords), 2, 0);
				$features[] = self::pointFeature((float)$lon, (float)$lat, ['name' => $name]);
			}
		}
		return ParserValueHelper::jsonEncode(['type' => 'FeatureCollection', 'features' => $features]);
	}

	public static function kmlToCsv(mixed $value): string {
		$geo = json_decode(self::kmlToGeoJson($value), true);
		$rows = [['name', 'lat', 'lon']];
		foreach (($geo['features'] ?? []) as $feature) {
			$rows[] = [$feature['properties']['name'] ?? '', $feature['geometry']['coordinates'][1] ?? '', $feature['geometry']['coordinates'][0] ?? ''];
		}
		return ParserValueHelper::csvString($rows);
	}

	public static function geoJsonToKml(mixed $value): string {
		$data = ParserValueHelper::toJsonArray($value);
		$doc = new DOMDocument('1.0', 'UTF-8');
		$kml = $doc->createElement('kml');
		$document = $doc->createElement('Document');
		$kml->appendChild($document);
		$doc->appendChild($kml);
		foreach (($data['features'] ?? []) as $feature) {
			$coordinates = $feature['geometry']['coordinates'] ?? null;
			if (($feature['geometry']['type'] ?? '') === 'Point' && is_array($coordinates)) {
				$placemark = $doc->createElement('Placemark');
				$placemark->appendChild($doc->createElement('name', htmlspecialchars((string)($feature['properties']['name'] ?? ''), ENT_XML1)));
				$point = $doc->createElement('Point');
				$point->appendChild($doc->createElement('coordinates', $coordinates[0] . ',' . $coordinates[1] . ',0'));
				$placemark->appendChild($point);
				$document->appendChild($placemark);
			}
		}
		$doc->formatOutput = true;
		return $doc->saveXML() ?: '';
	}

	public static function geoJsonToCsv(mixed $value): string {
		$data = ParserValueHelper::toJsonArray($value);
		$rows = [['name', 'type', 'lat', 'lon']];
		foreach (($data['features'] ?? []) as $feature) {
			$coords = $feature['geometry']['coordinates'] ?? [];
			$rows[] = [$feature['properties']['name'] ?? '', $feature['geometry']['type'] ?? '', $coords[1] ?? '', $coords[0] ?? ''];
		}
		return ParserValueHelper::csvString($rows);
	}

	private static function simple(mixed $value): SimpleXMLElement {
		$previous = libxml_use_internal_errors(true);
		$xml = simplexml_load_string(ParserValueHelper::toString($value));
		libxml_clear_errors();
		libxml_use_internal_errors($previous);
		return $xml instanceof SimpleXMLElement ? $xml : new SimpleXMLElement('<root/>');
	}

	private static function dom(mixed $value): DOMDocument {
		$doc = new DOMDocument('1.0', 'UTF-8');
		$previous = libxml_use_internal_errors(true);
		$doc->loadXML(ParserValueHelper::toString($value), LIBXML_NOWARNING | LIBXML_NOERROR);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);
		return $doc;
	}

	private static function simpleXmlString(SimpleXMLElement $xml): string {
		return $xml->asXML() ?: '';
	}

	private static function xmlArray(SimpleXMLElement $xml): array {
		return json_decode(json_encode($xml) ?: '{}', true) ?: [];
	}

	private static function nodeArray(?DOMElement $node): array {
		if ($node === null) { return []; }
		$out = ['name' => $node->tagName];
		foreach ($node->attributes ?? [] as $attribute) { $out['attributes'][$attribute->nodeName] = $attribute->nodeValue; }
		foreach ($node->childNodes as $child) { if ($child instanceof DOMElement) { $out['children'][] = self::nodeArray($child); } }
		return $out;
	}

	private static function pointFeature(float $lon, float $lat, array $properties): array {
		return ['type' => 'Feature', 'geometry' => ['type' => 'Point', 'coordinates' => [$lon, $lat]], 'properties' => $properties];
	}
}
