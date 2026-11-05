<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
// CLI-only isolated SQL fixture. No main.inc.php, instance or external effects.
if (PHP_SAPI !== 'cli') {
	exit(1);
}
require_once __DIR__.'/../core/class/actions_diffusion.class.php';
$core = realpath($argv[1] ?? dirname(__DIR__, 2).'/dolibarr/htdocs');
$schema = $core ? file_get_contents($core.'/install/mysql/tables/llx_actioncomm.sql') : false;
if ($schema === false || !preg_match('/^\s*note\s+mediumtext/m', $schema) || preg_match('/^\s*note_private\s/m', $schema)) {
	throw new RuntimeException('Requires native Agenda schema with SQL note column; note_private is only a PHP property.');
}
define('MAIN_DB_PREFIX', 'fixture_long_prefix_');
class AgendaLabelDatabase
{
	public $pdo;
	public $fail = false;
	public $queries = 0;
	public function __construct() { $this->pdo = new PDO('sqlite::memory:'); }
	public function escape($value) { return str_replace("'", "''", $value); }
	public function query($sql) {
		$this->queries++;
		if ($this->fail) { return false; }
		// MySQL unary BINARY becomes SQLite's exact binary collation.
		$sql = preg_replace('/BINARY (note|label)/', '$1 COLLATE BINARY', $sql);
		return $this->pdo->exec($sql) !== false;
	}
}
class AgendaLabelLanguage
{
	public $locale;
	public $values = array();
	public $missing = false;
	public function load($domain) {
		if ($domain !== 'diffusion@diffusion') { throw new RuntimeException('Wrong domain'); }
		foreach (file(__DIR__.'/../langs/'.$this->locale.'/diffusion.lang', FILE_IGNORE_NEW_LINES) as $line) {
			if (strpos($line, '=') !== false && strpos(ltrim($line), '#') !== 0) {
				list($key, $value) = explode('=', $line, 2);
				$this->values[trim($key)] = trim($value);
			}
		}
	}
	public function transnoentities($key) { return $this->missing ? $key : ($this->values[$key] ?? $key); }
}
function check($ok, $message) { if (!$ok) { throw new RuntimeException($message); } }
foreach (array('fr_FR' => "Modification d'un contact de diffusion", 'en_US' => 'Diffusion contact updated') as $locale => $expected) {
	$db = new AgendaLabelDatabase();
	$db->pdo->exec('CREATE TABLE '.MAIN_DB_PREFIX.'actioncomm (id INTEGER PRIMARY KEY, entity INTEGER, label TEXT, note TEXT, code TEXT, elementtype TEXT, fk_element INTEGER)');
	$key = 'Notify_DIFFUSIONCONTACT_UPDATELINE';
	$base = array(1, $key, $key, 'DIFFUSIONCONTACT_UPDATELINE', 'diffusioncontact@diffusion', 42);
	$rows = array($base);
	$row = $base; $row[4] = 'diffusion@diffusion'; $row[2] = 'Custom note'; $rows[] = $row;
	$row = $base; $row[0] = 2; $rows[] = $row;
	$row = $base; $row[1] = 'Custom title'; $rows[] = $row;
	$row = $base; $row[3] = 'OTHER_UPDATE'; $rows[] = $row;
	$row = $base; $row[4] = 'other@other'; $rows[] = $row;
	$row = $base; $row[1] = strtolower($key); $rows[] = $row;
	$row = $base; $row[2] = null; $rows[] = $row;
	$insert = $db->pdo->prepare('INSERT INTO '.MAIN_DB_PREFIX.'actioncomm (entity,label,note,code,elementtype,fk_element) VALUES (?,?,?,?,?,?)');
	foreach ($rows as $row) { $insert->execute($row); }
	$read = static function () use ($db) { return $db->pdo->query('SELECT * FROM '.MAIN_DB_PREFIX.'actioncomm ORDER BY id')->fetchAll(PDO::FETCH_ASSOC); };
	$before = $read();
	$langs = new AgendaLabelLanguage(); $langs->locale = $locale;
	check(ActionsDiffusion::repairContactAgendaLabels($db, $langs, 1) === 1, 'Repair failed');
	$wanted = $before;
	$wanted[0]['label'] = $expected; $wanted[0]['note'] = $expected;
	$wanted[1]['label'] = $expected; $wanted[7]['label'] = $expected;
	check($read() === $wanted, 'Unexpected row or field changed');
	check(ActionsDiffusion::repairContactAgendaLabels($db, $langs, 1) === 1 && $read() === $wanted, 'Replay changed data');
	$queries = $db->queries;
	check(ActionsDiffusion::repairContactAgendaLabels($db, $langs, 0) === -1 && $db->queries === $queries, 'Invalid entity accepted');
	$langs->missing = true;
	check(ActionsDiffusion::repairContactAgendaLabels($db, $langs, 1) === -1 && $db->queries === $queries, 'Missing translation accepted');
	$langs->missing = false; $db->fail = true;
	check(ActionsDiffusion::repairContactAgendaLabels($db, $langs, 1) === -1 && $read() === $wanted, 'SQL failure ignored');
	echo $locale.': repair, scope, preservation, replay and failures passed'.PHP_EOL;
}
