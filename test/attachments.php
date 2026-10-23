<?php
/* Copyright (C) 2026 Pierre Ardoin; SPDX-License-Identifier: GPL-3.0-or-later */
// Isolated files and SQLite fixtures. No main.inc.php, ERP configuration or external effects.
if (PHP_SAPI !== 'cli') {
	exit(1);
}
$core = realpath($argv[1] ?? dirname(__DIR__, 2).'/dolibarr/htdocs');
if (!$core || !in_array('sqlite', PDO::getAvailableDrivers(), true)) {
	fwrite(STDERR, "Requires Dolibarr sources and PDO SQLite.\n");
	exit(1);
}
error_reporting(E_ALL);
define('DOL_DOCUMENT_ROOT', $core);
define('DOL_DATA_ROOT', str_replace('\\', '/', __DIR__).'/.attachments-'.bin2hex(random_bytes(5)));
define('DOL_URL_ROOT', '');
define('MAIN_DB_PREFIX', 'fixture_');
if (is_file($core.'/version.inc.php')) {
	require_once $core.'/version.inc.php';
} else {
	preg_match('/define\(\'DOL_VERSION\',\s*\'([^\']+)/', file_get_contents($core.'/filefunc.inc.php'), $version);
	define('DOL_VERSION', $version[1]);
}
require_once $core.'/core/lib/functions.lib.php';
require_once $core.'/core/lib/security.lib.php';
require_once __DIR__.'/../class/diffusion.class.php';
require_once __DIR__.'/../class/diffusionattachments.class.php';

class AttachmentResult
{
	public $rows;
	public $position = 0;
	public function __construct($rows) { $this->rows = $rows; }
}
class DoliDB
{
	public $pdo;
	public $failure = '';
	public $failcommit = false;
	public $failbegin = false;
	public $depth = 0;
	public $type = 'sqlite';
	public function __construct() { $this->pdo = new PDO('sqlite::memory:'); }
	public function begin() { if ($this->failbegin) { return 0; } if (!$this->depth++) { $this->pdo->beginTransaction(); } return 1; }
	public function commit() { if ($this->depth === 1 && $this->failcommit) { return 0; } if (--$this->depth === 0) { $this->pdo->commit(); } return 1; }
	public function rollback() { if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); } $this->depth = 0; return 1; }
	public function query($sql) {
		if (strpos($sql, 'FROM fixture_extrafields') !== false) { return new AttachmentResult(array()); }
		if ($this->failure && strpos($sql, $this->failure) !== false) { return false; }
		$sql = str_replace(' FOR UPDATE', '', $sql); // SQLite fixtures do not simulate database locks.
		try {
			$statement = $this->pdo->query($sql);
			return new AttachmentResult(strpos($sql, 'SELECT') === 0 ? $statement->fetchAll(PDO::FETCH_OBJ) : array());
		} catch (Throwable $e) { throw new RuntimeException($sql, 0, $e); }
	}
	public function fetch_object($result) { return $result->rows[$result->position++] ?? false; }
	public function num_rows($result) { return count($result->rows); }
	public function free($result) {}
	public function escape($value) { return str_replace("'", "''", $value); }
	public function escapeforlike($value) { return $value; }
	public function plimit($limit, $offset = 0) { return ' LIMIT '.((int) $limit).' OFFSET '.((int) $offset); }
	public function order($field, $order = 'ASC') { return ' ORDER BY '.$field.' '.$order; }
	public function prefix() { return MAIN_DB_PREFIX; }
	public function sanitize($value) { return $value; }
	public function encrypt($value, $withquote = 1) { return $withquote ? "'".$this->escape($value)."'" : $value; }
	public function decrypt($value) { return $value; }
	public function idate($value) { return $value ? gmdate('Y-m-d H:i:s', $value) : null; }
	public function jdate($value) { return $value ? strtotime($value) : null; }
	public function lasterror() { return 'Injected database failure'; }
	public function last_insert_id($table) { return (int) $this->pdo->lastInsertId(); }
}
class AttachmentUser extends User
{
	public $allow = true;
	public function hasRight($module, $level, $action = '') { return $this->allow; }
}
class AttachmentDiffusion extends Diffusion
{
	public $failtrigger = false;
	public function __construct($db) { $this->db = $db; }
	public function getNextNumRef() { return 'DIF2610-0004'; }
	public function call_trigger($name, $user) { return $this->failtrigger ? -1 : 1; }
}
class AttachmentTranslations
{
	public function load($name) {}
	public function trans($key, ...$values) { return $key; }
	public function transnoentities($key, ...$values) { return $key; }
}
$conf = (object) array('entity' => 1, 'modules' => array('diffusion' => 1), 'global' => (object) array('MAIN_EXTRAFIELDS_DISABLED' => 0), 'hooks_modules' => array(), 'modules_parts' => array('hooks' => array()), 'syslog' => (object) array('enabled' => 0), 'diffusion' => (object) array('enabled' => 1));
$langs = new AttachmentTranslations();
$user = (new ReflectionClass('AttachmentUser'))->newInstanceWithoutConstructor();
$user->id = 7;
$user->socid = 0;
$checks = 0;
function checkAttachment($actual, $expected, $message) {
	global $checks;
	$checks++;
	if ($actual !== $expected) { throw new RuntimeException($message.': '.var_export($actual, true)); }
}
function attachmentFixture($entity = 1, $flat = false) {
	global $conf, $db;
	$db = new DoliDB();
	$conf->entity = $entity;
	$conf->diffusion->multidir_output = array($entity => DOL_DATA_ROOT.($entity > 1 ? '/'.$entity : '').'/diffusion');
	$db->query('CREATE TABLE fixture_diffusion(rowid INTEGER PRIMARY KEY, ref TEXT, status INTEGER, entity INTEGER, date_validation TEXT, fk_user_valid INTEGER, last_main_doc TEXT)');
	$db->query('CREATE TABLE fixture_ecm_files(rowid INTEGER PRIMARY KEY, ref TEXT, label TEXT, share TEXT, entity INTEGER, filename TEXT, filepath TEXT, fullpath_orig TEXT, description TEXT, keywords TEXT, cover INTEGER, position INTEGER, gen_or_uploaded TEXT, extraparams TEXT, date_c TEXT, tms TEXT, fk_user_c INTEGER, fk_user_m INTEGER, note_private TEXT, note_public TEXT, acl TEXT, src_object_type TEXT, src_object_id INTEGER, agenda_id INTEGER, UNIQUE(entity, filepath, filename))');
	$db->query('CREATE TABLE fixture_ecm_files_extrafields(fk_object INTEGER)');
	$db->query("INSERT INTO fixture_diffusion VALUES (4, '(PROV4)', 0, $entity, NULL, NULL, '')");
	$object = new AttachmentDiffusion($db);
	$object->id = 4; $object->entity = $entity; $object->ref = '(PROV4)'; $object->status = 0; $object->last_main_doc = '';
	$directory = $flat ? diffusionGetFlatDocumentUploadDir($object) : diffusionGetDocumentUploadDir($object);
	if (!is_dir($directory)) { mkdir($directory, 0777, true); }
	$file = '(PROV4)-électricité.pdf';
	file_put_contents($directory.'/'.$file, 'fixture plan content');
	$relative = diffusionGetDocumentPathRelativeToDataRoot($directory);
	$hash = md5_file($directory.'/'.$file);
	$db->query("INSERT INTO fixture_ecm_files (rowid,entity,ref,filepath,filename,share,label,src_object_type,src_object_id,gen_or_uploaded,position) VALUES (1,$entity,'old-path-hash','$relative','$file','fixture-public-key','$hash','diffusion',4,'uploaded',1)");
	return $object;
}
function attachmentRow($db) { return $db->query('SELECT * FROM fixture_ecm_files WHERE rowid = 1')->rows[0]; }
function clearAttachmentFixture() {
	if (!is_dir(DOL_DATA_ROOT)) { return; }
	$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(DOL_DATA_ROOT, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
	foreach ($files as $file) { $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
	rmdir(DOL_DATA_ROOT);
}
register_shutdown_function('clearAttachmentFixture');
$tabsource = file_get_contents($core.'/core/lib/functions.lib.php');
if (is_file($core.'/core/lib/html.lib.php')) {
	$tabsource .= file_get_contents($core.'/core/lib/html.lib.php');
}
checkAttachment((bool) preg_match('/function\s+dol_get_fiche_head\([^{}]+\$dragdropfile\s*=\s*0/s', $tabsource), true, 'Native optional drag/drop parameter');
checkAttachment(function_exists('dragAndDropFileUpload'), true, 'Native uploader exists');
$uploadsource = file_get_contents($core.'/core/class/fileupload.class.php');
checkAttachment(strpos($uploadsource, "'overrideUploadOptions'") !== false && strpos($uploadsource, "'element' => ".'$element') !== false, true, 'Native upload extension exists');
foreach (array(1, 2) as $entity) {
	foreach (array(false, true) as $flat) {
		$object = attachmentFixture($entity, $flat);
		$object->last_main_doc = diffusionGetDocumentPathRelativeToDataRoot($flat ? diffusionGetFlatDocumentUploadDir($object) : diffusionGetDocumentUploadDir($object)).'/(PROV4)-électricité.pdf';
		$validation = $object->validate($user, 1);
		checkAttachment($validation, 1, 'Validate '.$object->error.' '.diffusionGetDocumentUploadDir($object));
		$row = attachmentRow($db);
		checkAttachment($row->share, 'fixture-public-key', 'Original public URL preserved');
		checkAttachment((int) $row->rowid, 1, 'Original ECM identity preserved');
		checkAttachment(file_get_contents(DOL_DATA_ROOT.'/'.$row->filepath.'/'.$row->filename), 'fixture plan content', 'Same URL resolves same bytes');
		checkAttachment($row->filename, 'DIF2610-0004-électricité.pdf', 'Native prefix rename');
		checkAttachment($row->ref !== 'old-path-hash', true, 'Native ECM path hash updated');
		checkAttachment($object->last_main_doc, $row->filepath.'/'.$row->filename, 'Main document updated');
		clearAttachmentFixture();
	}
}
foreach (array('begin', 'sql', 'trigger', 'commit', 'collision') as $failure) {
	$object = attachmentFixture(2);
	$oldpath = diffusionGetDocumentUploadDir($object);
	if ($failure === 'begin') { $db->failbegin = true; }
	if ($failure === 'sql') { $db->failure = 'UPDATE fixture_ecm_files'; }
	if ($failure === 'trigger') { $object->failtrigger = true; }
	if ($failure === 'commit') { $db->failcommit = true; }
	if ($failure === 'collision') { $future = clone $object; $future->ref = 'DIF2610-0004'; mkdir(diffusionGetDocumentUploadDir($future), 0777, true); }
	checkAttachment($object->validate($user, 0), -1, 'Validation failure: '.$failure);
	checkAttachment($object->ref, '(PROV4)', 'Reference restored');
	checkAttachment(file_exists($oldpath.'/(PROV4)-électricité.pdf'), true, 'Files restored');
	checkAttachment(attachmentRow($db)->share, 'fixture-public-key', 'Share survives rollback');
	clearAttachmentFixture();
}
foreach (array(false, true) as $sourcepresent) {
	foreach (array(false, true) as $flattarget) {
		$object = attachmentFixture(2);
		$source = diffusionGetDocumentUploadDir($object);
		if (!$sourcepresent) {
			unlink($source.'/(PROV4)-électricité.pdf');
			rmdir($source);
		}
		$future = clone $object; $future->ref = 'DIF2610-0004';
		$target = $flattarget ? diffusionGetFlatDocumentUploadDir($future) : diffusionGetDocumentUploadDir($future);
		mkdir($target, 0777, true);
		file_put_contents($target.'/reserved.pdf', 'reserved bytes');
		$relative = diffusionGetDocumentPathRelativeToDataRoot($target);
		$db->query("INSERT INTO fixture_ecm_files(rowid,entity,filepath,filename,share,src_object_type,src_object_id) VALUES(2,2,'$relative','reserved.pdf','reserved-key','diffusion',99)");
		$before = json_encode($db->query('SELECT * FROM fixture_ecm_files ORDER BY rowid')->rows);
		checkAttachment($object->validate($user, 1), -1, 'Occupied future directory is refused with or without a draft directory');
		checkAttachment($object->error, 'DiffusionAttachmentPathConflict', 'Canonical and legacy target conflicts are explicit');
		checkAttachment($object->ref, '(PROV4)', 'No future reference is adopted');
		checkAttachment(file_get_contents($target.'/reserved.pdf'), 'reserved bytes', 'Existing file content is untouched');
		checkAttachment(json_encode($db->query('SELECT * FROM fixture_ecm_files ORDER BY rowid')->rows), $before, 'Existing ECM owners and shares are untouched');
		checkAttachment($db->depth, 0, 'Target collision rolls back');
		clearAttachmentFixture();
	}
}
$object = attachmentFixture(2);
$source = diffusionGetDocumentUploadDir($object);
unlink($source.'/(PROV4)-électricité.pdf');
rmdir($source);
$db->query('DELETE FROM fixture_ecm_files');
checkAttachment($object->validate($user, 1), 1, 'An empty draft can validate when its future directory is free');
checkAttachment($object->ref, 'DIF2610-0004', 'Empty draft receives its own final reference');
checkAttachment((int) $db->query('SELECT status FROM fixture_diffusion WHERE rowid = 4')->rows[0]->status, 1, 'Empty draft validation is stored');
checkAttachment($db->num_rows($db->query('SELECT * FROM fixture_ecm_files')), 0, 'Empty draft does not acquire another attachment index');
clearAttachmentFixture();
foreach (array(false, true) as $mirror) {
	$object = attachmentFixture(2);
	$oldrelative = attachmentRow($db)->filepath;
	checkAttachment($object->validate($user, 1), 1, 'Prepare broken historical index');
	$row = attachmentRow($db);
	$db->query("UPDATE fixture_ecm_files SET filepath = '".$db->escape($oldrelative)."', filename = '(PROV4)-électricité.pdf' WHERE rowid = 1");
	if ($mirror) {
		$db->query("INSERT INTO fixture_ecm_files(rowid,entity,ref,filepath,filename,label,src_object_type,src_object_id,gen_or_uploaded,position) VALUES(2,2,'mirror','".$db->escape($row->filepath)."','".$db->escape($row->filename)."','".$row->label."','diffusiondoc',4,'unknown',1)");
	}
	$documents = new DiffusionAttachments($db);
	$repaired = $documents->repair($object, $user);
	checkAttachment($repaired, array('repaired' => 1, 'missing' => 0, 'conflicts' => 0), 'Repair original ECM row, including native mirror');
	checkAttachment(attachmentRow($db)->share, 'fixture-public-key', 'Recovery preserves exact old URL');
	checkAttachment($db->num_rows($db->query('SELECT rowid FROM fixture_ecm_files')), 1, 'No duplicate index');
	checkAttachment($documents->repair($object, $user), array('repaired' => 0, 'missing' => 0, 'conflicts' => 0), 'Repair idempotence');
	clearAttachmentFixture();
}
$object = attachmentFixture();
checkAttachment($object->validate($user, 1), 1, 'Prepare revoked share');
$db->query("UPDATE fixture_ecm_files SET share = NULL WHERE rowid = 1");
$documents = new DiffusionAttachments($db);
$documents->repair($object, $user);
checkAttachment(attachmentRow($db)->share, null, 'Repair does not reactivate revoked sharing');
$db->query('DELETE FROM fixture_ecm_files');
$missing = $documents->repair($object, $user);
checkAttachment($missing['missing'], 1, 'Missing index recreated without inventing an old URL '.json_encode($missing));
checkAttachment(attachmentRow($db)->share, null, 'Recreated index has no public key');
clearAttachmentFixture();
$object = attachmentFixture();
$oldrelative = attachmentRow($db)->filepath;
checkAttachment($object->validate($user, 1), 1, 'Prepare ambiguous public rows');
$row = attachmentRow($db);
$db->query("INSERT INTO fixture_ecm_files(rowid,entity,ref,filepath,filename,label,share,src_object_type,src_object_id,gen_or_uploaded) VALUES(2,1,'other','$oldrelative','(PROV4)-électricité.pdf','".$row->label."','different-key','diffusion',4,'uploaded')");
checkAttachment((new DiffusionAttachments($db))->repair($object, $user)['conflicts'], 1, 'Two public identities require manual review');
checkAttachment($db->num_rows($db->query('SELECT rowid FROM fixture_ecm_files')), 2, 'Ambiguous rows preserved');
clearAttachmentFixture();
$object = attachmentFixture(2, true);
$documents = new DiffusionAttachments($db);
checkAttachment($documents->repair($object, $user)['repaired'], 1, 'Repair legacy flat directory');
checkAttachment(file_exists(diffusionGetDocumentUploadDir($object).'/(PROV4)-électricité.pdf'), true, 'Legacy file moved into canonical directory');
clearAttachmentFixture();
$object = attachmentFixture();
$db->query("UPDATE fixture_diffusion SET ref = 'concurrent-change' WHERE rowid = 4");
checkAttachment($object->validate($user, 1), -1, 'Stale validation refused');
checkAttachment(file_exists(diffusionGetDocumentUploadDir($object).'/(PROV4)-électricité.pdf'), true, 'Stale validation leaves files untouched');
clearAttachmentFixture();
$object = attachmentFixture(2);
$conf->entity = 1;
$mc = new class { public function getEntity($element, $shared, $object) { return '1,2'; } };
checkAttachment(DiffusionAttachments::checkObjectScope($object, $user), true, 'Shared owner scope separate from consultation entity');
checkAttachment(strpos(diffusionGetDocumentUploadDir($object), '/2/diffusion/') !== false, true, 'Shared object uses owner directory');
$mc = null;
checkAttachment(DiffusionAttachments::checkObjectScope($object, $user), false, 'Unshared owner denied');
checkAttachment(diffusionResolveLinkedFilePath($object, diffusionGetDocumentUploadDir($object), '../another.pdf'), '', 'Traversal rejected');
$conf->entity = 2;
$conf->global->DIFFUSION_ALLOW_EXTERNAL_DOWNLOAD = 1;
$db->query('UPDATE fixture_ecm_files SET share = NULL WHERE rowid = 1');
checkAttachment(diffusionEnsureAttachedFilesIndexedAndShared($db, $object, diffusionGetDocumentUploadDir($object), $user, array(), true), 0, 'Empty upload result does not recreate revoked shares');
checkAttachment(attachmentRow($db)->share, null, 'Revoked key stays absent');
checkAttachment(diffusionEnsureAttachedFilesIndexedAndShared($db, $object, diffusionGetDocumentUploadDir($object), $user, array('(PROV4)-électricité.pdf'), true), 1, 'Newly uploaded file follows automatic-sharing setting');
checkAttachment(!empty(attachmentRow($db)->share), true, 'Native sharing key generated for new upload');
$conf->global->DIFFUSION_ALLOW_EXTERNAL_DOWNLOAD = 0;
clearAttachmentFixture();
$object = attachmentFixture();
$directory = diffusionGetDocumentUploadDir($object);
rename($directory.'/(PROV4)-électricité.pdf', $directory.'/(PROV4)-électricité.pdf.noexe');
checkAttachment(diffusionEnsureAttachedFilesIndexedAndShared($db, $object, $directory, $user, array('(PROV4)-électricité.pdf.noexe')), 0, 'Native noexe index reused');
checkAttachment($object->validate($user, 1), 1, 'Native noexe file relocated');
$row = attachmentRow($db);
checkAttachment(is_file(DOL_DATA_ROOT.'/'.$row->filepath.'/'.$row->filename.'.noexe'), true, 'Native physical suffix preserved');
clearAttachmentFixture();
$object = attachmentFixture();
$user->admin = 1; $user->allow = false;
checkAttachment($object->validate($user, 1), -1, 'Administrator without write right refused');
$user->allow = true; $user->socid = 42;
checkAttachment(DiffusionAttachments::checkObjectScope($object, $user), false, 'External user refused');
$user->socid = 0;
unset($conf->diffusion->multidir_output[1]);
checkAttachment(diffusionGetDocumentUploadDir($object), '', 'Missing owner directory has no fallback');
clearAttachmentFixture();
require_once __DIR__.'/attachment_index_case.class.php';
// The fixture table omits unrelated Diffusion fields; native ECM updates remain unchanged.
class MaintenanceAttachmentDiffusion extends AttachmentDiffusion
{
	public function fetch($id, $ref = null, $noextrafields = 0, $nolines = 0)
	{
		$result = $this->db->query('SELECT rowid, ref, status, entity FROM fixture_diffusion WHERE rowid = '.((int) $id));
		if (!$result) { return -1; }
		$row = $this->db->fetch_object($result);
		if (!is_object($row)) { return 0; }
		$this->id = (int) $row->rowid; $this->ref = $row->ref; $this->status = (int) $row->status; $this->entity = (int) $row->entity;
		return $this->id;
	}
}
foreach (array(1, 2) as $entity) {
	foreach (array('success', 'admin_without_right', 'source_exists', 'source_elsewhere', 'source_read_error', 'file_changed', 'checksum', 'other_owner', 'entity', 'duplicate', 'metadata_normalization', 'admin', 'begin', 'sql', 'commit') as $scenario) {
		$object = attachmentFixture($entity);
		$caseObject = new MaintenanceAttachmentDiffusion($db);
		$caseObject->fetch(4);
		$row = attachmentRow($db);
		$db->query("UPDATE fixture_ecm_files SET ref = '".$db->escape(dol_hash($row->filepath.'/'.$row->filename, '3'))."', date_c = '2026-10-19 12:00:00', description = 'Preserved description', note_public = 'Preserved note', src_object_id = 19 WHERE rowid = 1");
		$file = diffusionGetDocumentUploadDir($object).'/'.$row->filename;
		$digest = hash_file('sha256', $file);
		if ($scenario === 'source_exists' || $scenario === 'source_elsewhere') {
			$sourceEntity = $scenario === 'source_elsewhere' ? $entity + 1 : $entity;
			$db->query("INSERT INTO fixture_diffusion VALUES(19,'other-source',1,$sourceEntity,NULL,NULL,'')");
		}
		if ($scenario === 'source_read_error') { $db->failure = 'SELECT rowid, ref, status, entity FROM fixture_diffusion WHERE rowid = 19'; }
		if ($scenario === 'file_changed') { file_put_contents($file, 'Different content'); }
		if ($scenario === 'checksum') { $db->query("UPDATE fixture_ecm_files SET label = 'stale' WHERE rowid = 1"); }
		if ($scenario === 'other_owner') { $db->query('UPDATE fixture_ecm_files SET src_object_id = 18 WHERE rowid = 1'); }
		if ($scenario === 'entity') { $db->query('UPDATE fixture_ecm_files SET entity = 99 WHERE rowid = 1'); }
		if ($scenario === 'duplicate') {
			$db->query('ALTER TABLE fixture_ecm_files RENAME TO fixture_original_index');
			$db->query('CREATE TABLE fixture_ecm_files AS SELECT * FROM fixture_original_index');
			$db->query("INSERT INTO fixture_ecm_files(rowid,entity,ref,filepath,filename,label,share,src_object_type,src_object_id) SELECT 2,entity,'duplicate',filepath,filename,label,'other-public-key',src_object_type,src_object_id FROM fixture_original_index");
		}
		if ($scenario === 'metadata_normalization') { $db->query("UPDATE fixture_ecm_files SET description = '  preserve whitespace  ' WHERE rowid = 1"); }
		if ($scenario === 'admin') { $user->admin = 0; }
		if ($scenario === 'admin_without_right') { $user->allow = false; }
		if ($scenario === 'begin') { $db->failbegin = true; }
		if ($scenario === 'sql') { $db->failure = 'UPDATE fixture_ecm_files'; }
		if ($scenario === 'commit') { $db->failcommit = true; }
		$before = json_encode(attachmentRow($db));
		$bytes = file_get_contents($file);
		$case = new DiffusionAttachmentIndexCase();
		$actual = $case->repair($caseObject, $user, 1, 19, $row->filename, $digest);
		$success = in_array($scenario, array('success', 'admin_without_right'), true);
		checkAttachment($actual, $success ? 1 : -1, 'Reviewed orphan repair '.$entity.' '.$scenario);
		if ($success) {
			checkAttachment((int) attachmentRow($db)->src_object_id, 4, 'Orphan reassigned to the reviewed target');
			checkAttachment(attachmentRow($db)->share, 'fixture-public-key', 'Reviewed repair preserves the exact public key');
			checkAttachment(attachmentRow($db)->description, 'Preserved description', 'Reviewed repair preserves metadata');
			checkAttachment($case->repair($caseObject, $user, 1, 19, $row->filename, $digest), 0, 'Reviewed repair is idempotent');
		} else {
			checkAttachment(json_encode(attachmentRow($db)), $before, 'Refused repair leaves the whole ECM row unchanged');
		}
		checkAttachment(file_get_contents($file), $bytes, 'Reviewed repair never rewrites the attachment');
		checkAttachment($db->depth, 0, 'Reviewed repair closes its transaction');
		$user->admin = 1;
		$user->allow = true;
		clearAttachmentFixture();
	}
}
echo $checks." assertions passed; native ECM/files ".DOL_VERSION.", SQLite and disposable filesystem; simulated rights, object fetch and numbering.\n";
