<?php declare(strict_types=1);

$frameworkRoot = dirname(__DIR__, 3);
if (!defined('DIR_PLUGIN')) define('DIR_PLUGIN', $frameworkRoot . '/plugin/');
if (!defined('DIR_TEST')) define('DIR_TEST', $frameworkRoot . '/test/');

require_once $frameworkRoot . '/bootstrap/Autoloader.php';
\Base3\Bootstrap\Autoloader::register();
