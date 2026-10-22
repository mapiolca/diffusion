<?php
/* Copyright (C) 2026 Pierre Ardoin; SPDX-License-Identifier: GPL-3.0-or-later */
require __DIR__.'/attachments.php';

// Exercise the real loader and numbering models, with isolated SQL and model providers.
class NumberingDatabase extends DoliDB
{
	public function query($sql)
	{
		// Translate the MySQL substring syntax for the disposable SQLite fixture only.
		$sql = preg_replace('/SUBSTRING\(ref FROM ([0-9]+)\)/', 'SUBSTRING(ref, $1)', $sql);
		return parent::query($sql);
	}
}
class NumberingDiffusion extends Diffusion
{
	public function __construct($db) { $this->db = $db; }
}
$startchecks = $checks;
$conf->entity = 1;
$conf->file = (object) array('dol_document_root' => array('main' => DOL_DOCUMENT_ROOT, 'alt0' => dirname(__DIR__, 2), 'alt1' => DOL_DATA_ROOT.'/providers'));
$conf->modules_parts['models'] = array('/partnership/', '/recruitment/', '/salaries/', '/diffusion/');
$db = new NumberingDatabase();
$db->query('CREATE TABLE fixture_diffusion(ref TEXT, entity INTEGER)');
$db->query("INSERT INTO fixture_diffusion VALUES ('LTR2610-0009', 1), ('LTR2610-0099', 2)");
$object = new NumberingDiffusion($db);
$object->entity = 1;
$object->ismultientitymanaged = 1;
$object->date_creation = gmmktime(12, 0, 0, 10, 22, 2026);
// Dolibarr's handler can report warnings even for @include_once: do not ignore suppressed errors.
set_error_handler(static function ($severity, $message, $file, $line) {
	throw new ErrorException($message, 0, $severity, $file, $line);
}, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE);
ob_start();
try {
	checkAttachment($object->getNextNumRef(), 'LTR2610-0010', 'Default model loads after absent core and third-party candidates without warnings');
	checkAttachment(isset($conf->global->DIFFUSION_DIFFUSION_ADDON), false, 'Default selection does not alter configuration');
	$conf->global->DIFFUSION_DIFFUSION_ADDON = 'mod_diffusion_standard';
	checkAttachment($object->getNextNumRef(), 'LTR2610-0010', 'Already loaded model can be called again');
	$conf->modules_parts['models'] = array();
	checkAttachment($object->getNextNumRef(), 'LTR2610-0010', 'Bundled model loads without a persisted models registration');
	$conf->global->DIFFUSION_DIFFUSION_ADDON = '';
	checkAttachment($object->getNextNumRef(), 'LTR2610-0010', 'Historical empty selection retains the standard default');
	checkAttachment($conf->global->DIFFUSION_DIFFUSION_ADDON, '', 'Empty configuration is preserved');
	$conf->global->DIFFUSION_DIFFUSION_ADDON = 'mod_diffusion_advanced';
	checkAttachment($object->getNextNumRef(), '', 'Advanced model reports its missing mask');
	checkAttachment($object->error, 'NotConfigured', 'Advanced model error is preserved');
	$provider = DOL_DATA_ROOT.'/providers/numbering/core/modules/diffusion';
	mkdir($provider, 0777, true);
	file_put_contents($provider.'/mod_diffusion_fixture.php', '<?php class mod_diffusion_fixture extends ModeleNumRefDiffusion { public function getExample() { return "CUSTOM-0042"; } public function getNextValue($object) { return "CUSTOM-0042"; } }');
	file_put_contents($provider.'/mod_diffusion_invalid.php', '<?php class mod_diffusion_invalid {}');
	$conf->modules_parts['models'] = array('/absent/', '/numbering/', '/diffusion/');
	$conf->global->DIFFUSION_DIFFUSION_ADDON = 'mod_diffusion_fixture';
	checkAttachment($object->getNextNumRef(), 'CUSTOM-0042', 'A registered third-party model resolves in an alternate document root');
	foreach (array('mod_diffusion_missing', '../mod_diffusion_standard', 'mod_diffusion_invalid') as $invalid) {
		$conf->global->DIFFUSION_DIFFUSION_ADDON = $invalid;
		checkAttachment($object->getNextNumRef(), '', 'Unavailable or invalid model is refused: '.$invalid);
		checkAttachment($object->error, 'DiffusionNumberingModelUnavailable', 'Unavailable model has a controlled error');
	}
	$conf->global->DIFFUSION_DIFFUSION_ADDON = 'mod_diffusion_standard';
	$conf->entity = 2; $object->entity = 2;
	checkAttachment($object->getNextNumRef(), 'LTR2610-0100', 'Real standard model retains its entity counter');
	checkAttachment($object->error, '', 'A later successful call has no stale error');
	checkAttachment($db->num_rows($db->query('SELECT ref FROM fixture_diffusion')), 2, 'Getting the next reference does not write business data');
	$draft = attachmentFixture();
	$object = new NumberingDiffusion($db);
	$object->id = $draft->id; $object->entity = $draft->entity;
	$object->ref = $draft->ref; $object->status = $draft->status;
	$conf->global->DIFFUSION_DIFFUSION_ADDON = 'mod_diffusion_missing';
	checkAttachment($object->validate($user, 1), -1, 'Validation refuses a missing configured model');
	checkAttachment($object->error, 'DiffusionNumberingModelUnavailable', 'Validation retains the specific numbering error');
	checkAttachment($db->depth, 0, 'Failed validation rolls back');
	checkAttachment($object->ref, '(PROV4)', 'Failed validation retains the draft reference');
	checkAttachment(is_file(diffusionGetDocumentUploadDir($object).'/(PROV4)-électricité.pdf'), true, 'Failed numbering leaves attachments unchanged');
	checkAttachment(ob_get_contents(), '', 'Loader errors do not emit HTML or physical paths');
} finally {
	ob_end_clean();
	restore_error_handler();
	clearAttachmentFixture();
}
echo ($checks - $startchecks)." numbering assertions passed; native loader and models, SQLite fixture.\n";
