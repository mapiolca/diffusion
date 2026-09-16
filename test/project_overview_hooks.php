<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

// CLI integration test: native HookManager and Project, simulated users/renderers,
// SQLite in memory. Never loads main.inc.php or connects to a Dolibarr database.
if (PHP_SAPI !== 'cli') {
	exit(1);
}
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
	throw new ErrorException($message, 0, $severity, $file, $line);
});
$coreRoot = realpath($argv[1] ?? dirname(__DIR__, 2).'/dolibarr/htdocs');
$advancedRoot = realpath($argv[2] ?? dirname(__DIR__, 2).'/lmdbadvancedproject');
if (!$coreRoot || !$advancedRoot || !in_array('sqlite', PDO::getAvailableDrivers(), true)) {
	fwrite(STDERR, "Usage: php -d extension=pdo_sqlite test/project_overview_hooks.php <Dolibarr htdocs> <LMDB Advanced Project root> [core Git ref]\n");
	exit(1);
}
define('DOL_DOCUMENT_ROOT', $coreRoot);
define('DOL_URL_ROOT', '');
define('MAIN_DB_PREFIX', 'test_');

function dol_syslog($message, $level = 7) {}
function isModEnabled($module) { global $enabledModules; return !empty($enabledModules[$module]); }
function getDolGlobalInt($name, $default = 0) { global $conf; return (int) ($conf->global->$name ?? $default); }
function dol_buildpath($path, $type = 0) {
	global $advancedRoot;
	return $type ? '/external'.$path : $advancedRoot.substr($path, strlen('/lmdbadvancedproject'));
}
function dol_include_once($path) { return 1; } // Diffusion presentation double below.
function getEntity($element) { global $visibleEntities; return implode(',', $visibleEntities); }
function dol_escape_htmltag($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function dol_print_date($value, $format) { return gmdate('Y-m-d H:i:s', $value); }
function newToken() { return 'test-only'; }

class User
{
	public $id = 1;
	public $socid = 0;
	public $admin = 0;
	public $permissions = array();
	public function hasRight($module, $level, $action = '') {
		return !empty($this->permissions[implode('.', array_filter(array($module, $level, $action)))]);
	}
}
class OverviewResult
{
	public $rows;
	public $position = 0;
	public function __construct($rows) { $this->rows = $rows; }
}
class OverviewDatabase
{
	public $pdo;
	public $queries = array();
	public function __construct() { $this->pdo = new PDO('sqlite::memory:'); }
	public function query($sql) {
		if (!preg_match('/^SELECT /', $sql)) { throw new RuntimeException('Unexpected business write'); }
		$this->queries[] = $sql;
		return new OverviewResult($this->pdo->query($sql)->fetchAll(PDO::FETCH_OBJ));
	}
	public function fetch_object($result) { return $result->rows[$result->position++] ?? false; }
	public function num_rows($result) { return count($result->rows); }
	public function free($result) {}
	public function idate($date) { return gmdate('Y-m-d H:i:s', $date); }
	public function jdate($date) { return strtotime($date); }
}
class Diffusion
{
	public $id;
	public $ref;
	public $status;
	public function __construct($db) {}
	public function getNomUrl($picto) { return 'diffusion-'.$this->id; }
	public function getLibStatut($mode) { return 'Draft'; }
}
class OtherOverviewHook
{
	public $results = array();
	public $resprints = '';
	public $error = '';
	public $errors = array();
	public $warnings = array();
	public function completeListOfReferent($parameters, &$object, &$action, $manager) {
		$this->results = array('other_module' => array('test' => true));
		return 0;
	}
}

// Select native hook dispatch and tab integration, not a full ERP instance.
function nativeOverviewSource($path) {
	global $argv, $coreRoot;
	if (empty($argv[3])) {
		return file_get_contents($coreRoot.'/'.$path);
	}
	$process = proc_open(array('git', '-C', dirname($coreRoot), 'show', $argv[3].':htdocs/'.$path), array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
	if (!is_resource($process)) { throw new RuntimeException('Cannot read native core source'); }
	$source = stream_get_contents($pipes[1]);
	$error = stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);
	if (proc_close($process) !== 0) { throw new RuntimeException($error); }
	return $source;
}
eval('?>'.nativeOverviewSource('core/class/hookmanager.class.php'));
// Extract the unmodified top-level function to avoid loading unrelated globals
// and core helpers already replaced by controlled doubles in this CLI test.
if (!preg_match('/^function complete_head_from_modules\(.*?^\}/ms', nativeOverviewSource('core/lib/functions.lib.php'), $nativeTabFunction)) {
	throw new RuntimeException('Native tab integration function not found');
}
eval($nativeTabFunction[0]);
require $advancedRoot.'/class/actions_lmdbadvancedproject.class.php';
require __DIR__.'/../core/class/actions_diffusion.class.php';

$enabledModules = array('diffusion' => true, 'lmdbadvancedproject' => true);
$visibleEntities = array(1);
$conf = (object) array('global' => (object) array(
	'LMDBADVANCEDPROJECT_ENABLE_CUSTOMER_INVOICE_SPLIT' => 1,
	'LMDBADVANCEDPROJECT_ENABLE_SUPPLIER_INVOICE_SPLIT' => 1,
));
$langs = new class {
	public function load($catalog) {}
	public function trans($key) { return $key; }
};
$form = new class {
	public function textwithpicto($text, $tooltip) { return $text; }
};
$_SERVER['PHP_SELF'] = '/projet/element.php';
$_SERVER['REQUEST_URI'] = '/projet/element.php?id=42';
$db = new OverviewDatabase();
$db->pdo->exec('CREATE TABLE test_diffusion (rowid INTEGER, entity INTEGER, fk_project INTEGER, fk_user_creat INTEGER, ref TEXT, label TEXT, date_expedition TEXT, fk_user_exped INTEGER, status INTEGER)');
$db->pdo->exec('CREATE TABLE test_user (rowid INTEGER, login TEXT, firstname TEXT, lastname TEXT)');
$db->pdo->exec("INSERT INTO test_diffusion VALUES (1,1,42,1,'A','Author A',NULL,NULL,0),(2,1,42,2,'B','Author B',NULL,NULL,0),(3,2,42,1,'C','Other entity',NULL,NULL,0),(4,1,43,1,'D','Other project',NULL,NULL,0)");
$project = (new ReflectionClass('Project'))->newInstanceWithoutConstructor();
$project->id = 42;
$project->public = 1;
$user = new User();
$checks = 0;
function check($actual, $expected, $label) {
	global $checks;
	$checks++;
	if ($actual !== $expected) {
		throw new RuntimeException($label.': '.json_encode($actual).' != '.json_encode($expected));
	}
}
function newManager($order) {
	global $db;
	$hooks = array(
		'diffusion' => (new ReflectionClass('ActionsDiffusion'))->newInstanceWithoutConstructor(),
		'advanced' => new ActionsLmdbadvancedproject($db),
		'other' => new OtherOverviewHook(),
	);
	$hooks['diffusion']->db = $db;
	$manager = new HookManager($db);
	$manager->contextarray = array('projectOverview');
	$manager->hooks = array('projectOverview' => $hooks);
	$manager->hooksSorted = array('projectOverview' => array());
	foreach ($order as $name) { $manager->hooksSorted['projectOverview']['50:'.$name] = $hooks[$name]; }
	return $manager;
}

$orders = array(array('diffusion','advanced','other'), array('advanced','diffusion','other'), array('other','diffusion','advanced'), array('other','advanced','diffusion'), array('diffusion','other','advanced'), array('advanced','other','diffusion'));
foreach ($orders as $order) {
	foreach (array(1, 2, 3) as $userId) { // Two authors and an unrelated reader.
		foreach (array(false, true) as $partsRead) {
			$user->id = $userId;
			$user->permissions = array('diffusion.diffusiondoc.read' => true, 'projet.lire' => true, 'facture.lire' => true, 'fournisseur.facture.lire' => true, 'lmdbadvancedproject.split.read' => $partsRead);
			$manager = newManager($order);
			$action = '';
			$result = $manager->executeHooks('completeListOfReferent', array(), $project, $action);
			check($result, 0, 'Referents are additive');
			check(isset($manager->resArray['diffusion']), true, 'All readers retain Diffusion');
			check(isset($manager->resArray['other_module']), true, 'Other module retained');
			check(isset($manager->resArray['lmdbadvancedproject_customer_invoice_parts']), $partsRead, 'Customer parts follow their rights');
			check(isset($manager->resArray['lmdbadvancedproject_supplier_invoice_parts']), $partsRead, 'Supplier parts follow their rights');
			check($manager->resArray['diffusion']['testnew'], false, 'Read does not grant creation');
			$params = array('key' => 'diffusion', 'value' => $manager->resArray['diffusion']);
			$manager->executeHooks('printOverviewDetail', $params, $project, $action);
			check(strpos($manager->resPrint, 'diffusion-1') !== false && strpos($manager->resPrint, 'diffusion-2') !== false, true, 'Both authors visible');
			check(strpos($manager->resPrint, 'diffusion-3') === false && strpos($manager->resPrint, 'diffusion-4') === false, true, 'Entity and project boundaries');
			check(strpos($manager->resPrint, 'fa-unlink') === false, true, 'Read does not grant unlink');
			$manager->executeHooks('printOverviewProfit', $params, $project, $action);
			check(strpos($manager->resPrint, '>2</td>') !== false, true, 'Summary count');
		}
	}
}

// Counters and rendering must also deny a direct hook call without read rights.
$params = array('key' => 'diffusion', 'value' => array('name' => 'Diffusion', 'datefieldname' => 'date_expedition', 'testnew' => true));
foreach (array(false, true) as $admin) {
	$user->admin = (int) $admin;
	$user->permissions = array('projet.lire' => true);
	$manager = newManager(array('diffusion'));
	foreach (array('completeListOfReferent', 'printOverviewDetail', 'printOverviewProfit') as $method) {
		check($manager->executeHooks($method, $params, $project, $action), 0, 'No read right: '.$method);
		check($manager->resArray, array(), 'No referent leaked');
		check($manager->resPrint, '', 'No output leaked');
	}
}
$user->admin = 0;
$user->permissions = array('diffusion.diffusiondoc.read' => true, 'projet.lire' => true);
// Reproduce the screenshot through the native tab pipeline: three core objects
// plus five readable diffusions, across all three passes used by project tabs.
$db->pdo->beginTransaction();
$db->pdo->exec("INSERT INTO test_diffusion VALUES (5,1,42,2,'E','Extra E',NULL,NULL,0),(6,1,42,2,'F','Extra F',NULL,NULL,0),(7,1,42,3,'G','Extra G',NULL,NULL,0)");
$coreTabs = array(
	array('/projet/element.php?id=42', 'Overview<span class="badge marginleftonlyshort">3</span>', 'element'),
	array('/projet/note.php?id=42', 'Notes<span class="badge marginleftonlyshort">1</span>', 'notes'),
);
foreach ($orders as $order) {
	$hookmanager = newManager($order);
	$head = $coreTabs;
	$h = count($head);
	$before = count($db->queries);
	foreach (array(array('add', 'core'), array('add', 'external'), array('remove', '')) as $pass) {
		complete_head_from_modules($conf, $langs, $project, $head, $h, 'project', $pass[0], $pass[1]);
		check(strip_tags($head[0][1]), 'Overview8', 'Native tab badge adds five diffusions exactly once');
		check(substr_count($head[0][1], '<span '), 1, 'One merged badge');
		check($head[1], $coreTabs[1], 'Other tab preserved');
		check($h, 2, 'No duplicated tabs');
	}
	check(count($db->queries) - $before, 1, 'One count query across repeated tab passes');
}
$head = array(array('/projet/element.php?id=42', 'Overview', 'element'));
$h = count($head);
complete_head_from_modules($conf, $langs, $project, $head, $h, 'project');
check(strip_tags($head[0][1]), 'Overview5', 'Badge created when core has no linked elements');
foreach (array(0, 1) as $admin) {
	$user->admin = $admin;
	$user->permissions = array('projet.lire' => true);
	$head = $coreTabs;
	$before = count($db->queries);
	complete_head_from_modules($conf, $langs, $project, $head, $h, 'project');
	check($head, $coreTabs, 'Unauthorized badge hidden, including administrators');
	check(count($db->queries), $before, 'No count query without read rights');
}
$user->admin = 0;
$user->permissions = array('diffusion.diffusiondoc.read' => true, 'projet.lire' => true);
$visibleEntities = array(1, 2);
$head = $coreTabs;
complete_head_from_modules($conf, $langs, $project, $head, $h, 'project');
check(strip_tags($head[0][1]), 'Overview9', 'Badge includes shared entity only when authorized');
$visibleEntities = array(1);
$emptyProject = clone $project;
$emptyProject->id = 99;
$head = $coreTabs;
complete_head_from_modules($conf, $langs, $emptyProject, $head, $h, 'project');
check($head, $coreTabs, 'Project without diffusions retains its native badge');
$head = array($coreTabs[1]);
$before = count($db->queries);
complete_head_from_modules($conf, $langs, $project, $head, $h, 'project');
check($head, array($coreTabs[1]), 'Absent overview tab is not recreated');
check(count($db->queries), $before, 'No count query without overview tab');
$otherObject = new stdClass();
$otherObject->id = 42;
$head = $coreTabs;
complete_head_from_modules($conf, $langs, $otherObject, $head, $h, 'thirdparty');
check($head, $coreTabs, 'Other object tabs are untouched');
check(count($db->queries), $before, 'No count query for another object');
$db->pdo->rollBack();

// Simulate the denial returned by the native project restriction. This does not
// validate real project assignments or external-user access on an ERP instance.
class UnassignedOverviewProject extends Project
{
	public function restrictedProjectArea(User $user, $mode = 'read') { return -1; }
}
$private = (new ReflectionClass('UnassignedOverviewProject'))->newInstanceWithoutConstructor();
$private->id = 42;
$user->permissions = array('diffusion.diffusiondoc.read' => true, 'projet.lire' => true);
$manager = newManager(array('diffusion'));
$before = count($db->queries);
foreach (array('completeListOfReferent', 'printOverviewDetail', 'printOverviewProfit') as $method) {
	$manager->executeHooks($method, $params, $private, $action);
	check($manager->resPrint, '', 'Project denial: '.$method);
}
check(count($db->queries), $before, 'No diffusion query for an inaccessible project');
$head = $coreTabs;
complete_head_from_modules($conf, $langs, $private, $head, $h, 'project');
check($head, $coreTabs, 'Inaccessible project: no badge contribution');
check(count($db->queries), $before, 'Inaccessible project: no count query');

$visibleEntities = array(1, 2);
$manager->executeHooks('printOverviewDetail', $params, $project, $action);
check(strpos($manager->resPrint, 'diffusion-3') !== false, true, 'Shared entity included');
check(strpos($manager->resPrint, 'fa-plus-circle') === false, true, 'Referent cannot elevate creation rights');

$user->permissions['diffusion.diffusiondoc.write'] = true;
$manager->executeHooks('printOverviewDetail', $params, $project, $action);
check(strpos($manager->resPrint, 'fa-plus-circle') !== false && strpos($manager->resPrint, 'fa-unlink') !== false, true, 'Writer retains native actions');
$enabledModules['diffusion'] = false;
$before = count($db->queries);
foreach (array('completeListOfReferent', 'printOverviewDetail', 'printOverviewProfit') as $method) {
	$manager->executeHooks($method, $params, $project, $action);
	check($manager->resArray, array(), 'Disabled module: no referent');
	check($manager->resPrint, '', 'Disabled module: no output');
}
check(count($db->queries), $before, 'Disabled module: no query');
$head = $coreTabs;
complete_head_from_modules($conf, $langs, $project, $head, $h, 'project');
check($head, $coreTabs, 'Disabled module: no badge contribution');
check(count($db->queries), $before, 'Disabled module: no count query');
$enabledModules['diffusion'] = true;
$manager = newManager(array('diffusion', 'other'));
$manager->executeHooks('completeListOfReferent', array(), $project, $action);
check(array_keys($manager->resArray), array('diffusion', 'other_module'), 'Works without Advanced Project hook');
$conf->global->LMDBADVANCEDPROJECT_ENABLE_CUSTOMER_INVOICE_SPLIT = 0;
$conf->global->LMDBADVANCEDPROJECT_ENABLE_SUPPLIER_INVOICE_SPLIT = 0;
$user->permissions['lmdbadvancedproject.split.read'] = true;
$user->permissions['facture.lire'] = true;
$user->permissions['fournisseur.facture.lire'] = true;
$manager = newManager(array('diffusion', 'advanced', 'other'));
$manager->executeHooks('completeListOfReferent', array(), $project, $action);
check(array_keys($manager->resArray), array('diffusion', 'other_module'), 'Disabled split options do not remove Diffusion');
echo $checks.' assertions passed; native HookManager and complete_head_from_modules '.($argv[3] ?? 'working tree').'; PHP '.PHP_VERSION."; simulated permissions and in-memory SQLite.\n";
