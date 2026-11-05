<?php
/* Copyright (C) 2026 Pierre Ardoin; SPDX-License-Identifier: GPL-3.0-or-later */
// Native helpers with disposable SQLite fixtures; no ERP session or email delivery.
if (PHP_SAPI !== 'cli') { exit(1); }
$core = realpath($argv[1] ?? dirname(__DIR__, 2).'/dolibarr/htdocs');
if (!$core || !in_array('sqlite', PDO::getAvailableDrivers(), true)) { fwrite(STDERR, "Requires Dolibarr sources and PDO SQLite.\n"); exit(1); }
define('DOL_DOCUMENT_ROOT', $core);
define('DOL_URL_ROOT', '');
define('MAIN_DB_PREFIX', 'fixture_');
require_once $core.'/core/lib/functions.lib.php';
require_once $core.'/core/lib/security.lib.php';
class DoliDB
{
	public $pdo;
	public function __construct() { $this->pdo = new PDO('sqlite::memory:'); }
	public function query($sql) { return $this->pdo->query($sql); }
	public function fetch_object($result) { return $result->fetchObject(); }
}
$checks = 0;
function checkAttachment($actual, $expected, $label) {
	global $checks;
	if ($actual !== $expected) { throw new RuntimeException($label.' '.var_export($actual, true)); }
	$checks++;
}
$conf = (object) array('entity' => 1, 'global' => (object) array(), 'modules' => array());
$startchecks = $checks;
$db = new DoliDB();
$db->query('CREATE TABLE fixture_socpeople(rowid INTEGER, firstname TEXT, lastname TEXT, email TEXT, fk_soc INTEGER, entity INTEGER)');
$db->query('CREATE TABLE fixture_societe(rowid INTEGER, nom TEXT)');
$db->query('CREATE TABLE fixture_diffusion_contact(fk_diffusion INTEGER, fk_contact INTEGER, contact_source TEXT, mail_status INTEGER, rowid INTEGER)');
$db->query("INSERT INTO fixture_socpeople VALUES (1, 'A', 'Alpha', 'a@example.test', NULL, 1), (2, 'B', 'Beta', 'b@example.test', NULL, 1), (3, 'C', 'Gamma', '', NULL, 1), (4, 'D', 'Delta', 'd@example.test', NULL, 2)");
$db->query("INSERT INTO fixture_diffusion_contact VALUES (42,1,'external',1,1), (42,1,'external',1,2), (42,2,'external',0,3), (42,3,'external',1,4), (42,4,'external',1,5), (43,2,'external',1,6), (42,2,'internal',1,7)");
$conf->entity = 1;
$conf->global->DIFFUSION_FULLACCESS_CONTACT = 0;
unset($mc);
$object = (object) array('id' => 42, 'element' => 'diffusiondoc');
$template = file_get_contents(__DIR__.'/../tpl/card_presend.tpl.php');
$start = strpos($template, "\t// Add entity contacts");
$end = strpos($template, "\t".'$formmail->withto =', $start);
$prepare = substr($template, $start, $end - $start);
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = array('mode' => 'init');
$_POST = $_REQUEST = array();
$liste = array();
eval($prepare);
checkAttachment(GETPOST('receiver', 'array'), array('1'), 'Only marked external contacts with an email in scope; duplicates collapse');
checkAttachment(array_keys($liste), array(1, 2), 'Entity and missing-email exclusions remain in force');
$_POST = $_REQUEST = array('receiver' => array('2'));
eval($prepare);
checkAttachment(GETPOST('receiver', 'array'), array('2'), 'Explicit selection survives');
$_POST = $_REQUEST = array('receiver' => array());
eval($prepare);
checkAttachment(GETPOST('receiver', 'array'), array(), 'Explicit empty selection survives');
$_POST = $_REQUEST = array('sendto' => 'manual@example.test');
eval($prepare);
checkAttachment(GETPOSTISSET('receiver'), false, 'Free recipient entry remains authoritative');
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = $_REQUEST = array();
eval($prepare);
checkAttachment(GETPOSTISSET('receiver'), false, 'A POST without recipients does not restore defaults');
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = array();
eval($prepare);
checkAttachment(GETPOSTISSET('receiver'), false, 'Attachment and template reloads do not restore defaults');
$_GET = array('mode' => 'init');
$object->id = 99;
eval($prepare);
checkAttachment(GETPOST('receiver', 'array'), array(''), 'No marked contacts does not trigger native sole-contact selection');
echo ($checks - $startchecks)." recipient assertions passed; native GETPOST and SQLite fixtures.\n";
