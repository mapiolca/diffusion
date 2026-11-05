<?php
/* Copyright (C) 2026 Pierre Ardoin; SPDX-License-Identifier: GPL-3.0-or-later */
// CLI-only native package validation and isolated bootstrap layout fixtures.
if (PHP_SAPI !== 'cli') { exit(1); }
$core = realpath($argv[1] ?? '');
if (!$core || !class_exists('ZipArchive')) { fwrite(STDERR, "Requires core sources and PHP zip extension.\n"); exit(1); }
define('DOL_DOCUMENT_ROOT', $core);
require_once $core.'/core/lib/functions.lib.php';
require_once $core.'/core/lib/functions2.lib.php';
require_once $core.'/core/lib/files.lib.php';
$conf = (object) array('global' => (object) array(), 'modules' => array());
class PackageLanguage { public function trans($key, ...$args) { return $key.' '.implode(' ', $args); } }
$langs = new PackageLanguage();
$root = dirname(__DIR__);
$work = sys_get_temp_dir().'/diffusion-package-'.bin2hex(random_bytes(8));
mkdir($work, 0700, true);
$checks = 0;
function packageCheck($ok, $label) { global $checks; if (!$ok) { throw new RuntimeException($label); } $checks++; }
function packageRun(array $command, $cwd) {
	$process = proc_open($command, array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes, $cwd);
	if (!is_resource($process)) { throw new RuntimeException('Cannot launch fixture'); }
	fclose($pipes[0]); $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]);
	fclose($pipes[1]); fclose($pipes[2]);
	return array(proc_close($process), $out, $err);
}
$extract = '';
try {
	if (!empty($argv[2])) {
		$zipfile = realpath($argv[2]);
		packageCheck(is_string($zipfile), 'ZIP unavailable');
	} else {
		$zipfile = $work.'/'.basename($work).'.zip';
		list($status, $files, $error) = packageRun(array('git', 'ls-files', '-z'), $root);
		packageCheck($status === 0, 'Cannot enumerate tracked package files: '.$error);
		$zip = new ZipArchive(); packageCheck($zip->open($zipfile, ZipArchive::CREATE) === true, 'Cannot create ZIP');
		foreach (explode("\0", $files) as $file) {
			if ($file === '' || strpos($file, '.github/') === 0 || $file === 'test/packaging.php') { continue; }
			packageCheck($zip->addFile($root.'/'.$file, 'diffusion/'.$file), 'Cannot add '.$file);
		}
		$zip->close();
	}
	$zip = new ZipArchive(); packageCheck($zip->open($zipfile) === true, 'Cannot open final ZIP');
	$source = $zip->getFromName('diffusion/test/attachment_index.php');
	packageCheck(is_string($source), 'Packaged bootstrap unavailable');
	if (function_exists('validateZipFile')) {
		$extractCandidate = sys_get_temp_dir().'/unzip-dir-'.basename($zipfile);
		packageCheck(!file_exists($extractCandidate), 'Native extraction directory already exists; refusing to overwrite it');
		$extract = $extractCandidate;
		$result = validateZipFile($zip, 'module_diffusion-1.4.3.zip', $zipfile, $langs);
		packageCheck($result['error'] === 0 && $result['upload'] === 0, 'Native ZIP rejected: '.($result['errormsg'] ?? ''));
		echo "Native ZIP validation passed.\n";
	} else {
		$zip->close();
		echo "Native ZIP validation unavailable in this core (no validateZipFile); bootstrap fixtures still run.\n";
	}
	$end = strpos($source, '/** @var User'); packageCheck($end !== false, 'Bootstrap boundary missing');
	$bootstrap = substr($source, 0, $end);
	foreach (array('htdocs/diffusion/test', 'htdocs/custom/diffusion/test', 'missing/diffusion/test') as $layout) {
		$fixture = $work.'/'.$layout; mkdir($fixture, 0700, true);
		file_put_contents($fixture.'/probe.php', $bootstrap);
		if (strpos($layout, 'htdocs/') === 0) {
			file_put_contents($work.'/htdocs/main.inc.php', '<?php echo "BOOTSTRAP_OK"; return 1;');
		}
		list($status, $output, $error) = packageRun(array(PHP_BINARY, $fixture.'/probe.php'), $work);
		packageCheck($error === '', 'Bootstrap emitted errors for '.$layout.': '.$error);
		packageCheck($output === (strpos($layout, 'htdocs/') === 0 ? 'BOOTSTRAP_OK' : 'Dolibarr bootstrap unavailable'), 'Wrong bootstrap result for '.$layout.': '.$output);
	}
	echo "Root/custom/missing bootstrap fixtures passed; ".$checks." checks.\n";
} finally {
	// These directories are disposable fixtures created by this process only.
	foreach (array($extract, $work) as $directory) {
		if ($directory === '' || !is_dir($directory)) { continue; }
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($files as $file) { $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
		rmdir($directory);
	}
}
