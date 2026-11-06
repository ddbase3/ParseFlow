<?php declare(strict_types=1);

namespace ParseFlow\Test\Fixture;

use ParseFlow\Dto\ParserState;
use ZipArchive;

final class ParserSampleFixture {

	public static function value(ParserState $state): mixed {
		return match ($state->format) {
			'csv' => "name,age\nAda,42\nBob,39\n",
			'ini' => "user=ada\n[config]\nenabled=1\n",
			'env' => "# comment\nUSER=ada\nPATH=\"/tmp/a\"\n",
			'json' => [['name' => 'Ada', 'age' => 42], ['name' => 'Bob', 'age' => 39]],
			'html' => '<!doctype html><html><head><title>Example</title><meta name="description" content="Demo"><script type="application/ld+json">{"name":"Ada"}</script></head><body><main><article><h1>Heading</h1><p>Paragraph <a href="https://example.org">link</a></p><img src="photo.png" alt="Photo"><ul><li>One</li><li>Two</li></ul><form><input name="email" type="email"></form><table><tr><th>Name</th></tr><tr><td>Ada</td></tr></table><pre><code class="language-php">echo 1;</code></pre></article></main></body></html>',
			'markdown' => "---\ntitle: Example\n---\n# Heading\n\n- One\n- Two\n\n[Site](https://example.org) ![Picture](photo.png)\n\n| Name | Age |\n| --- | --- |\n| Ada | 42 |\n\n```php\necho 1;\n```\n",
			'xml' => '<root><row><name>Ada</name><age>42</age></row><row><name>Bob</name><age>39</age></row></root>',
			'rss' => '<rss version="2.0"><channel><title>News</title><item><title>Title</title><link>https://example.org</link><description>Info</description><pubDate>today</pubDate></item></channel></rss>',
			'atom' => '<feed xmlns="http://www.w3.org/2005/Atom"><entry><title>Title</title><link href="https://example.org"/><summary>Info</summary><updated>today</updated></entry></feed>',
			'sitemap' => '<urlset><url><loc>https://example.org</loc><lastmod>today</lastmod><changefreq>daily</changefreq><priority>0.5</priority></url></urlset>',
			'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10"><title>Logo</title><desc>Sample</desc><text>Text</text></svg>',
			'gpx' => '<gpx><wpt lat="52.1" lon="8.2"><name>Place</name></wpt></gpx>',
			'kml' => '<kml><Document><Placemark><name>Place</name><Point><coordinates>8.2,52.1,0</coordinates></Point></Placemark></Document></kml>',
			'geojson' => ['type' => 'FeatureCollection', 'features' => [['type' => 'Feature', 'properties' => ['name' => 'Place'], 'geometry' => ['type' => 'Point', 'coordinates' => [8.2, 52.1]]]]],
			'png', 'jpeg', 'webp' => self::image($state->format),
			default => $state->type === 'image' ? self::image('png') : 'Hello world'
		};
	}

	public static function image(string $format = 'png'): string {
		$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVR4nGMQkdMAAACkAFt2Ll/jAAAAAElFTkSuQmCC', true);
		if ($format === 'png' || !function_exists('imagecreatefromstring')) { return $png; }
		$image = imagecreatefromstring($png);
		if ($image === false) { return $png; }
		ob_start();
		if ($format === 'jpeg' && function_exists('imagejpeg')) { imagejpeg($image); }
		elseif ($format === 'webp' && function_exists('imagewebp')) { imagewebp($image); }
		else { imagepng($image); }
		$bytes = ob_get_clean();
		imagedestroy($image);
		return is_string($bytes) ? $bytes : $png;
	}

	public static function officeFile(): string {
		$path = tempnam(sys_get_temp_dir(), 'parseflow_office_');
		if (!is_string($path)) { throw new \RuntimeException('Could not create test archive.'); }
		$zip = new ZipArchive();
		if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) { unlink($path); throw new \RuntimeException('Could not open test archive.'); }
		foreach ([
			'word/document.xml' => '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Hello</w:t></w:r></w:p></w:body></w:document>',
			'word/media/image1.png' => self::image(),
			'docProps/core.xml' => '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:title>Example</dc:title></cp:coreProperties>',
			'xl/sharedStrings.xml' => '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><si><t>Ada</t></si></sst>',
			'xl/worksheets/sheet1.xml' => '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c t="s"><v>0</v></c><c><v>42</v></c></row></sheetData></worksheet>',
			'ppt/slides/slide1.xml' => '<p:sld xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><a:t>Slide title</a:t><a:t>Point</a:t></p:sld>',
			'ppt/media/image1.png' => self::image()
		] as $name => $value) { $zip->addFromString($name, $value); }
		$zip->close();
		return $path;
	}
}
