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

namespace ParseFlow\Parser\Image;

use ParseFlow\Parser\Common\ParserValueHelper;

class ImageParserHelper {

	public static function pngToJpeg(mixed $value): string {
		return self::convert($value, 'jpeg');
	}

	public static function jpegToPng(mixed $value): string {
		return self::convert($value, 'png');
	}

	public static function webpToPng(mixed $value): string {
		return self::convert($value, 'png');
	}

	public static function pngToWebp(mixed $value): string {
		return self::convert($value, 'webp');
	}

	public static function imageMetadataToJson(mixed $value): string {
		$source = self::source($value);
		$info = is_file($source) ? @getimagesize($source) : @getimagesizefromstring($source);
		return ParserValueHelper::jsonEncode([
			'width' => $info[0] ?? null,
			'height' => $info[1] ?? null,
			'mime' => $info['mime'] ?? null,
			'bits' => $info['bits'] ?? null,
		]);
	}

	public static function jpegExifToJson(mixed $value): string {
		if (!function_exists('exif_read_data') || !is_string($value) || !is_file($value)) {
			return '{}';
		}
		$data = @exif_read_data($value) ?: [];
		return ParserValueHelper::jsonEncode($data);
	}

	public static function imageToThumbnailPng(mixed $value): string {
		return self::thumbnail($value, 'png');
	}

	public static function imageToThumbnailJpeg(mixed $value): string {
		return self::thumbnail($value, 'jpeg');
	}

	private static function convert(mixed $value, string $target): string {
		$image = self::image($value);
		if (!$image) { return ''; }
		ob_start();
		if ($target === 'jpeg') { imagejpeg($image, null, 90); }
		elseif ($target === 'webp') { imagewebp($image, null, 90); }
		else { imagepng($image); }
		$out = ob_get_clean();
		imagedestroy($image);
		return is_string($out) ? $out : '';
	}

	private static function thumbnail(mixed $value, string $target): string {
		$image = self::image($value);
		if (!$image) { return ''; }
		$width = imagesx($image);
		$height = imagesy($image);
		$max = 256;
		$ratio = min($max / max(1, $width), $max / max(1, $height), 1);
		$newWidth = max(1, (int)round($width * $ratio));
		$newHeight = max(1, (int)round($height * $ratio));
		$thumb = imagecreatetruecolor($newWidth, $newHeight);
		imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
		ob_start();
		if ($target === 'jpeg') { imagejpeg($thumb, null, 90); } else { imagepng($thumb); }
		$out = ob_get_clean();
		imagedestroy($image);
		imagedestroy($thumb);
		return is_string($out) ? $out : '';
	}

	private static function image(mixed $value): \GdImage|false {
		$source = self::source($value);
		return is_file($source) ? @imagecreatefromstring(file_get_contents($source) ?: '') : @imagecreatefromstring($source);
	}

	private static function source(mixed $value): string {
		return is_string($value) && is_file($value) ? $value : ParserValueHelper::toString($value);
	}
}
