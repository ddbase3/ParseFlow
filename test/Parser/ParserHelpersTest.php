<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of ParseFlow for BASE3 Framework.
 *
 * ParseFlow supplies graph-based parser services.
 * It discovers parser capabilities and plans conversion routes.
 * The parser workflow runs through existing module contracts.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 ***********************************************************************/


namespace Base3\ParseFlow\Test\Parser;

use Base3\ParseFlow\Parser\Common\ParserValueHelper;
use Base3\ParseFlow\Parser\Csv\CsvParserHelper;
use Base3\ParseFlow\Parser\Html\HtmlParserHelper;
use Base3\ParseFlow\Parser\Image\ImageParserHelper;
use Base3\ParseFlow\Parser\IniEnv\IniEnvParserHelper;
use Base3\ParseFlow\Parser\Json\JsonParserHelper;
use Base3\ParseFlow\Parser\Markdown\MarkdownParserHelper;
use Base3\ParseFlow\Parser\Office\OfficeOpenXmlParserHelper;
use Base3\ParseFlow\Parser\Xml\XmlParserHelper;
use Base3\ParseFlow\Test\Fixture\ParserSampleFixture;
use Base3\ParseFlow\Dto\ParserState;
use PHPUnit\Framework\TestCase;

final class ParserHelpersTest extends TestCase {

	public function testCommonValueConversionsHandleArraysFilesResourcesAndMalformedJson(): void {
		self::assertSame('hello', ParserValueHelper::toString('hello'));
		self::assertSame(['x' => 1], ParserValueHelper::toJsonArray(['x' => 1]));
		self::assertSame(['x' => 1], ParserValueHelper::toJsonArray('{"x":1}'));
		self::assertSame([], ParserValueHelper::toJsonArray('invalid'));
		self::assertSame('null', ParserValueHelper::jsonEncode("\xB1"));
		self::assertSame(['one', 'two', 'three'], ParserValueHelper::lines("one\r\ntwo\nthree"));
		self::assertSame("name,age\nAda,42\n", ParserValueHelper::csvString([['name', 'age'], ['Ada', 42]]));
		self::assertSame('[1,2]', ParserValueHelper::toString([1, 2]));
		$stream = fopen('php://temp', 'w+');
		try {
			fwrite($stream, 'stream data');
			rewind($stream);
			self::assertSame('stream data', ParserValueHelper::toString($stream));
		} finally { fclose($stream); }
		$path = tempnam(sys_get_temp_dir(), 'parseflow_value_');
		self::assertIsString($path);
		try {
			file_put_contents($path, 'file data');
			self::assertSame('file data', ParserValueHelper::toString($path));
		} finally { unlink($path); }
	}


	public function testCsvReadAndWritePreserveLegacyEscapeAndQuotation(): void {
		$rows = [
			['name', 'value'],
			['quoted', 'a "quoted" field'],
			['separated', 'left,right'],
			['backslash', 'left\\right'],
			['multiline', "first\nsecond"]
		];

		$csv = ParserValueHelper::csvString($rows);
		self::assertStringContainsString("backslash,\"left\\right\"\n", $csv);
		self::assertSame($rows, json_decode(CsvParserHelper::toJson($csv), true));
	}

	public function testCsvConvertersRetainRowsAndEscapeHtml(): void {
		$csv = "name,value\nAlice,2\nBob,<unsafe>\n";
		self::assertSame([['name', 'value'], ['Alice', '2'], ['Bob', '<unsafe>']], json_decode(CsvParserHelper::toJson($csv), true));
		self::assertStringContainsString('&lt;unsafe&gt;', CsvParserHelper::toHtmlTable($csv));
		self::assertStringContainsString('| --- | --- |', CsvParserHelper::toMarkdownTable($csv));
		self::assertCount(3, explode("\n", CsvParserHelper::toNdjson($csv)));
		self::assertStringContainsString('[row_1]', CsvParserHelper::toIni($csv));
		self::assertSame('', CsvParserHelper::toMarkdownTable(''));
		if (class_exists(\DOMDocument::class)) {
			self::assertStringContainsString('<rows>', CsvParserHelper::toXml($csv));
		} else {
			self::assertFalse(class_exists(\DOMDocument::class));
		}
	}

	public function testJsonConvertersRetainStructuredValues(): void {
		$data = [['name' => 'Ada', 'age' => 42], ['name' => 'Bob', 'age' => 39]];
		self::assertStringContainsString('name,age', JsonParserHelper::toCsv($data));
		self::assertStringContainsString('<table>', JsonParserHelper::toHtmlTable($data));
		self::assertStringContainsString('| --- |', JsonParserHelper::toMarkdownTable($data));
		self::assertCount(2, explode("\n", JsonParserHelper::toNdjson($data)));
		self::assertStringContainsString('<?php return ', JsonParserHelper::toPhpArray($data));
		self::assertStringContainsString('- ', JsonParserHelper::toYamlText($data));
		if (class_exists(\DOMDocument::class)) {
			self::assertStringContainsString('<json>', JsonParserHelper::toXml($data));
		} else { self::assertFalse(class_exists(\DOMDocument::class)); }
	}

	public function testIniAndEnvConvertersHandleSectionsCommentsAndQuotedValues(): void {
		$ini = "user=ada\n[section]\nvalue=7\n";
		self::assertSame('ada', json_decode(IniEnvParserHelper::iniToJson($ini), true)['user']);
		self::assertStringContainsString('SECTION_VALUE=', IniEnvParserHelper::iniToEnv($ini));
		$env = "# comment\nNAME=\"Ada\"\nPATH=/tmp\ninvalid\n";
		self::assertSame('Ada', json_decode(IniEnvParserHelper::envToJson($env), true)['NAME']);
		self::assertStringContainsString('PATH="/tmp"', IniEnvParserHelper::envToIni($env));
		if (class_exists(\DOMDocument::class)) {
			self::assertStringContainsString('<ini>', IniEnvParserHelper::iniToXml($ini));
		} else { self::assertFalse(class_exists(\DOMDocument::class)); }
	}

	public function testMarkdownConvertersHandleTablesFrontmatterLinksAndCode(): void {
		$md = ParserSampleFixture::value(new ParserState('string', 'markdown'));
		self::assertStringContainsString('Heading', MarkdownParserHelper::toText($md));
		self::assertStringContainsString('<h1>Heading</h1>', MarkdownParserHelper::toHtml($md));
		self::assertSame('heading', json_decode(MarkdownParserHelper::toJsonAst($md), true)[3]['type']);
		self::assertStringContainsString('Ada,42', MarkdownParserHelper::tablesToCsv($md));
		self::assertNotEmpty(json_decode(MarkdownParserHelper::tablesToJson($md), true));
		self::assertSame('Site', json_decode(MarkdownParserHelper::linksToJson($md), true)[0]['text']);
		self::assertSame('Picture', json_decode(MarkdownParserHelper::imagesToJson($md), true)[0]['alt']);
		self::assertSame('Heading', json_decode(MarkdownParserHelper::headingsToJson($md), true)[0]['text']);
		self::assertSame('Example', json_decode(MarkdownParserHelper::frontMatterToJson($md), true)['title']);
		self::assertSame("echo 1;\n", MarkdownParserHelper::codeBlocksToFiles($md)['code-1.php']);
	}

	public function testHtmlAndXmlConvertersProduceStructuredContentWhenExtensionsAvailable(): void {
		if (!class_exists(\DOMDocument::class) || !function_exists('simplexml_load_string')) {
			self::assertTrue(!class_exists(\DOMDocument::class) || !function_exists('simplexml_load_string'));
			return;
		}
		$html = ParserSampleFixture::value(new ParserState('document', 'html'));
		self::assertStringContainsString('Heading', HtmlParserHelper::toText($html));
		self::assertStringContainsString('Heading', HtmlParserHelper::toMarkdown($html));
		self::assertIsArray(json_decode(HtmlParserHelper::toJsonDom($html), true));
		self::assertStringContainsString('<', HtmlParserHelper::toXmlDom($html));
		self::assertStringContainsString('Ada', HtmlParserHelper::tablesToCsv($html));
		self::assertIsArray(json_decode(HtmlParserHelper::tablesToJson($html), true));
		self::assertSame('link', json_decode(HtmlParserHelper::linksToJson($html), true)[0]['text']);
		self::assertSame('Photo', json_decode(HtmlParserHelper::imagesToJson($html), true)[0]['alt']);
		self::assertIsArray(json_decode(HtmlParserHelper::metaToJson($html), true));
		self::assertIsArray(json_decode(HtmlParserHelper::headingsToJson($html), true));
		self::assertIsArray(json_decode(HtmlParserHelper::formsToJson($html), true));
		self::assertIsArray(json_decode(HtmlParserHelper::jsonLd($html), true));
		self::assertStringContainsString('Paragraph', HtmlParserHelper::articleToText($html));
		self::assertIsArray(json_decode(HtmlParserHelper::listsToJson($html), true));
		self::assertIsArray(HtmlParserHelper::codeBlocksToFiles($html));
		$xml = ParserSampleFixture::value(new ParserState('structured', 'xml'));
		self::assertIsArray(json_decode(XmlParserHelper::toJson($xml), true));
		self::assertStringContainsString('Ada', XmlParserHelper::toText($xml));
		self::assertStringContainsString('&lt;root', XmlParserHelper::toHtml($xml));
		self::assertStringContainsString('Ada', XmlParserHelper::toCsv($xml));
		self::assertStringContainsString('```xml', XmlParserHelper::toMarkdown($xml));
	}

	public function testOfficeAndImageHelpersUseActualFileFixtures(): void {
		$png = ParserSampleFixture::image();
		self::assertSame(1, json_decode(ImageParserHelper::imageMetadataToJson($png), true)['width']);
		self::assertSame('{}', ImageParserHelper::jpegExifToJson($png));
		if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
			self::assertNotSame('', ImageParserHelper::imageToThumbnailPng($png));
			self::assertNotSame('', ImageParserHelper::imageToThumbnailJpeg($png));
		} else { self::assertTrue(!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')); }
		if (!class_exists(\ZipArchive::class) || !class_exists(\DOMDocument::class)) {
			self::assertTrue(!class_exists(\ZipArchive::class) || !class_exists(\DOMDocument::class));
			return;
		}
		$archive = ParserSampleFixture::officeFile();
		try {
			self::assertSame('Hello', OfficeOpenXmlParserHelper::docxToText($archive));
			self::assertStringContainsString('<p>Hello</p>', OfficeOpenXmlParserHelper::docxToHtml($archive));
			self::assertStringContainsString('Hello', OfficeOpenXmlParserHelper::docxToMarkdown($archive));
			self::assertSame(['paragraphs' => ['Hello']], json_decode(OfficeOpenXmlParserHelper::docxToJsonStructure($archive), true));
			self::assertSame('Example', json_decode(OfficeOpenXmlParserHelper::docxMetadataToJson($archive), true)['title']);
			self::assertArrayHasKey('image1.png', OfficeOpenXmlParserHelper::docxImagesToFiles($archive));
			self::assertStringContainsString('Ada,42', OfficeOpenXmlParserHelper::xlsxToCsv($archive));
			self::assertCount(1, json_decode(OfficeOpenXmlParserHelper::xlsxToJson($archive), true));
			self::assertStringContainsString('<table>', OfficeOpenXmlParserHelper::xlsxToHtmlTables($archive));
			self::assertSame('Example', json_decode(OfficeOpenXmlParserHelper::xlsxMetadataToJson($archive), true)['title']);
			self::assertStringContainsString('Slide title', OfficeOpenXmlParserHelper::pptxToText($archive));
			self::assertCount(1, json_decode(OfficeOpenXmlParserHelper::pptxToJsonOutline($archive), true));
			self::assertStringContainsString('<section>', OfficeOpenXmlParserHelper::pptxToHtmlOutline($archive));
			self::assertArrayHasKey('image1.png', OfficeOpenXmlParserHelper::pptxImagesToFiles($archive));
			self::assertSame('Example', json_decode(OfficeOpenXmlParserHelper::pptxMetadataToJson($archive), true)['title']);
		} finally { unlink($archive); }
	}
}
