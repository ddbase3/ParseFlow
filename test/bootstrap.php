<?php declare(strict_types=1);

$pluginRoot = dirname(__DIR__);
$modulesRoot = dirname($pluginRoot);
$frameworkSource = is_dir($modulesRoot . '/Base3Framework/src')
	? $modulesRoot . '/Base3Framework/src'
	: dirname($modulesRoot) . '/src';

spl_autoload_register(static function(string $class) use ($pluginRoot, $frameworkSource): void {
	if (str_starts_with($class, 'ParseFlow\\')) {
		$path = $pluginRoot . '/' . (str_starts_with($class, 'ParseFlow\\Test\\') ? 'test/' : 'src/')
			. str_replace('\\', '/', substr($class, str_starts_with($class, 'ParseFlow\\Test\\') ? 15 : 10)) . '.php';
		if (is_file($path)) { require $path; }
	} elseif (str_starts_with($class, 'Base3\\')) {
		$path = $frameworkSource . '/' . str_replace('\\', '/', substr($class, 6)) . '.php';
		if (is_file($path)) { require $path; }
	}
});
