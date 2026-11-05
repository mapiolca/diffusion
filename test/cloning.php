<?php
/* Copyright (C) 2026 Pierre Ardoin; SPDX-License-Identifier: GPL-3.0-or-later */
// Native clone/fetch/create/validation against disposable SQLite; no ERP or email.
require __DIR__.'/attachments.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/conf.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
class CloneDatabase extends DoliDB
{
	public function query($sql) {
		// No linked contacts in this reference/provenance fixture.
		if (strpos($sql, 'element_contact') !== false) { return new AttachmentResult(array()); }
		return parent::query($sql);
	}
}
$startchecks = $checks;
$fixtureconf = $conf;
$conf = new Conf();
foreach (get_object_vars($fixtureconf) as $property => $value) { $conf->$property = $value; }
$conf->file->dol_document_root = array('main' => DOL_DOCUMENT_ROOT, 'alt0' => dirname(__DIR__, 2), 'alt1' => DOL_DATA_ROOT.'/providers');
$provider = DOL_DATA_ROOT.'/providers/clone/core/modules/diffusion';
mkdir($provider, 0777, true);
file_put_contents($provider.'/mod_diffusion_clonefixture.php', '<?php class mod_diffusion_clonefixture extends ModeleNumRefDiffusion { public function getExample() { return "CLONE-example"; } public function getNextValue($object) { return "CLONE-".$object->entity."-".$object->id; } }');
$conf->modules_parts['models'] = array('/clone/');
$conf->global->DIFFUSION_DIFFUSION_ADDON = 'mod_diffusion_clonefixture';
$conf->modules_parts['triggers'] = array();
$langs = new Translate('', $conf);
$langs->setDefaultLang('en_US');
if (!function_exists('setEventMessages')) {
	function setEventMessages($message, $errors = null, $style = '') { throw new RuntimeException($message.' '.json_encode($errors)); }
}
foreach (array(1, 2) as $entity) {
	$conf->entity = $entity;
	$conf->diffusion->multidir_output[$entity] = DOL_DATA_ROOT.'/'.$entity.'/diffusion';
	if (!is_dir($conf->diffusion->multidir_output[$entity])) { mkdir($conf->diffusion->multidir_output[$entity], 0777, true); }
	$db = new CloneDatabase();
	$db->query('CREATE TABLE fixture_rights_def(module TEXT, perms TEXT, subperms TEXT, libelle TEXT)');
	$source = new Diffusion($db);
	$columns = array();
	foreach ($source->fields as $field => $definition) {
		$columns[] = $field === 'rowid' ? 'rowid INTEGER PRIMARY KEY AUTOINCREMENT' : $field.' '.(strpos($definition['type'], 'integer') === 0 ? 'INTEGER' : 'TEXT');
	}
	$db->query('CREATE TABLE fixture_diffusion ('.implode(',', $columns).')');
	$db->query('CREATE TABLE fixture_ecm_files(rowid INTEGER, entity INTEGER, src_object_type TEXT, src_object_id INTEGER, filepath TEXT, filename TEXT)');
	$db->query("INSERT INTO fixture_diffusion(rowid,entity,ref,label,status,is_template,model_source,model_pdf,description,date_creation,date_expedition,fk_user_creat,fk_user_exped,last_main_doc) VALUES (1,$entity,'TPL-1','Template',0,1,NULL,'standard_diffusion','Template text','2026-10-01',NULL,7,NULL,'')");
	foreach (array(null, 1) as $template) {
		$fromid = $template ? 100 : 2;
		$db->query("INSERT INTO fixture_diffusion(rowid,entity,ref,label,status,is_template,model_source,model_pdf,description,date_creation,date_expedition,fk_user_creat,fk_user_exped,last_main_doc) VALUES ($fromid,$entity,'LTR2611-000$fromid','Source',6,0,".($template ?: 'NULL').",'standard_diffusion','Frozen source text','2026-10-01','2026-10-02',7,7,'source/letter.pdf')");
		$source = new Diffusion($db);
		checkAttachment($source->fetchCommon($fromid) > 0, true, 'Source loaded natively');
		$before = $db->query('SELECT * FROM fixture_diffusion WHERE rowid = '.$fromid)->rows[0];
		$count = $db->num_rows($db->query('SELECT rowid FROM fixture_diffusion WHERE model_source = 1 AND entity = '.$entity));
		$copy = $source->createFromClone($user, $fromid);
		checkAttachment(is_object($copy), true, 'Native clone succeeded: '.$source->error.' '.json_encode($source->errors));
		checkAttachment($copy->ref, '(PROV'.$copy->id.')', 'Clone receives its own provisional reference');
		checkAttachment((int) $copy->status, Diffusion::STATUS_DRAFT, 'Clone is a draft');
		checkAttachment((int) $copy->entity, $entity, 'Clone belongs to creation entity');
		checkAttachment((int) $copy->model_source, (int) $template, 'Template provenance retained');
		checkAttachment($copy->model_pdf, 'standard_diffusion', 'Document model retained');
		checkAttachment($copy->description, 'Frozen source text', 'Source description retained');
		checkAttachment($copy->last_main_doc, '', 'Clone does not reference source document');
		checkAttachment($copy->date_expedition, null, 'Clone has no expedition date');
		checkAttachment($copy->fk_user_exped, null, 'Clone has no sender');
		$stored = $db->query('SELECT * FROM fixture_diffusion WHERE rowid = '.$copy->id)->rows[0];
		checkAttachment($stored->ref, $copy->ref, 'Provisional reference persisted');
		checkAttachment((int) $stored->model_source, (int) $template, 'Template foreign key persisted');
		checkAttachment($db->num_rows($db->query('SELECT rowid FROM fixture_diffusion WHERE model_source = 1 AND entity = '.$entity)), $count + ($template ? 1 : 0), 'Template count includes clone only when linked');
		$validated = $copy;
		$result = $validated->validate($user, 1);
		checkAttachment($result, 1, 'Clone validation uses existing numbering transition: '.$validated->error);
		checkAttachment($validated->ref, 'CLONE-'.$entity.'-'.$copy->id, 'Configured numbering replaces provisional reference');
		checkAttachment((int) $validated->model_source, (int) $template, 'Validation retains provenance');
		checkAttachment((array) $db->query('SELECT * FROM fixture_diffusion WHERE rowid = '.$fromid)->rows[0], (array) $before, 'Source unchanged');
	}
	$rows = $db->num_rows($db->query('SELECT rowid FROM fixture_diffusion'));
	checkAttachment($source->createFromClone($user, 999), -1, 'Missing source refused');
	$db->failure = "SET ref = '(PROV";
	checkAttachment($source->createFromClone($user, 2), -1, 'Failure persisting provisional reference rolls back clone');
	$db->failure = '';
	checkAttachment($db->num_rows($db->query('SELECT rowid FROM fixture_diffusion')), $rows, 'No partial clone after failed reference update');
	checkAttachment($source->createFromClone($user, 1), -1, 'Template itself cannot be cloned');
	$user->allow = false;
	checkAttachment($source->createFromClone($user, 2), -1, 'Write permission required');
	$user->allow = true;
	$conf->entity = $entity === 1 ? 2 : 1;
	checkAttachment($source->createFromClone($user, 2), -1, 'Inaccessible owner entity refused');
	checkAttachment($db->num_rows($db->query('SELECT rowid FROM fixture_diffusion')), $rows, 'Refused clones create no rows');
	checkAttachment($db->depth, 0, 'Transactions closed');
}
echo ($checks - $startchecks)." cloning assertions passed; native CommonObject, SQLite, simulated rights/contacts, configured numbering provider.\n";
