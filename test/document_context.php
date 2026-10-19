<?php
/* Copyright (C) 2026 Pierre Ardoin; SPDX-License-Identifier: GPL-3.0-or-later */
// Reuse the disposable attachment fixtures and the actual native libraries.
require __DIR__.'/attachments.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';

class DocumentReader extends AttachmentUser
{
	public function hasRight($module, $level, $action = '')
	{
		return $this->allow && $module === 'diffusion' && $level === 'diffusiondoc' && $action === 'read';
	}
}
$startchecks = $checks;
$object = attachmentFixture();
$conf->global->MAIN_EXTRAFIELDS_DISABLED = 1;
$conf->file = (object) array('dol_document_root' => array('main' => DOL_DOCUMENT_ROOT, 'alt0' => dirname(__DIR__, 2)));
$conf->modules_parts['hooks'] = array('diffusion' => array('elementproperties'));
$conf->diffusion->dir_output = $conf->diffusion->multidir_output[1];
$db->query('CREATE TABLE fixture_rights_def(module TEXT, perms TEXT, subperms TEXT, libelle TEXT)');
foreach ($object->fields as $field => $definition) {
	if (!in_array($field, array('rowid', 'ref', 'status', 'entity', 'date_validation', 'fk_user_valid', 'last_main_doc'), true)) {
		$db->query('ALTER TABLE fixture_diffusion ADD COLUMN '.$field.' TEXT');
	}
}
$user = (new ReflectionClass('DocumentReader'))->newInstanceWithoutConstructor();
$user->id = 7; $user->socid = 0; $user->admin = 0;
$hookmanager = new HookManager($db);
$loaded = fetchObjectByElement(0, 'diffusion', '(PROV4)');
checkAttachment($loaded instanceof Diffusion && (int) $loaded->id === 4, true, 'Native resolver loads the document owner');
checkAttachment((int) $loaded->entity, 1, 'Ownership is loaded with Multicompany disabled');
// Before the correction the new v25 document-object check returns 0 here.
if (function_exists('checkUserAccessToDocumentObject')) {
	checkAttachment(checkUserAccessToDocumentObject($user, 'diffusion', 'diffusiondoc/(PROV4)/(PROV4)-électricité.pdf', 'read'), 1, 'Read-only user can preview and download the PDF');
} else {
	$access = dol_check_secure_access_document('diffusion', 'diffusiondoc/(PROV4)/(PROV4)-électricité.pdf', 1, $user);
	checkAttachment($access['accessallowed'], 1, 'Older native document permission accepts the object read right');
}
if (function_exists('checkUserAccessToDocumentObject')) {
	$user->allow = false; $user->admin = 1;
	checkAttachment(restrictedArea($user, 'diffusion', $loaded, '', '', 'fk_soc', 'rowid', 0, 1, 'read'), 0, 'Administrator without object read right denied');
	$user->allow = true; $user->socid = 42;
	checkAttachment(restrictedArea($user, 'diffusion', $loaded, '', '', 'fk_soc', 'rowid', 0, 1, 'read'), 0, 'External reader denied');
	$user->socid = 0; $loaded->entity = 2;
	checkAttachment(restrictedArea($user, 'diffusion', $loaded, '', '', 'fk_soc', 'rowid', 0, 1, 'read'), 0, 'Unshared owner denied');
	$loaded->entity = 1;
	checkAttachment(restrictedArea($user, 'diffusion', $loaded, '', '', 'fk_soc', 'rowid', 0, 1, 'read'), 1, 'No denial leaks into the next hook call');
	checkAttachment(restrictedArea($user, 'diffusion', $loaded, '', '', 'fk_soc', 'rowid', 0, 1, 'write'), 0, 'Read permission does not grant upload or generation');
	$unrelated = null; $action = '';
	checkAttachment($hookmanager->executeHooks('restrictedArea', array('features' => 'facture', 'mode' => 'read', 'object' => $loaded), $unrelated, $action), 0, 'Other modules keep their native area check');
	checkAttachment($hookmanager->resArray, array(), 'Other modules receive no stale result');
}

require_once __DIR__.'/../core/modules/diffusion/doc/pdf_standard_diffusion.modules.php';
class DocumentTranslations extends AttachmentTranslations
{
	public $defaultlang = 'fr_FR';
	public function loadLangs($names) {}
}
$langs = new DocumentTranslations();
$conf->global->MAIN_PDF_FORMAT = 'A4';
$db->query('CREATE TABLE fixture_c_paper_format(code TEXT, label TEXT, width INTEGER, height INTEGER, unit TEXT)');
$db->query('CREATE TABLE fixture_const(name TEXT, value TEXT, entity INTEGER)');
$mysoc = null;
$model = new pdf_standard_diffusion($db);
checkAttachment($model->write_file($loaded, $langs), -1, 'Missing native issuer returns a controlled generation error');
checkAttachment($model->error, 'DiffusionDocumentIssuerUnavailable', 'Missing issuer has a translated actionable error');
checkAttachment(file_get_contents(diffusionGetDocumentUploadDir($loaded).'/(PROV4)-électricité.pdf'), 'fixture plan content', 'Missing issuer preserves the upload');
$mysoc = new Societe($db);
$mysoc->name = 'Native issuer'; $mysoc->country_code = 'FR';
$model = new pdf_standard_diffusion($db);
checkAttachment($model->emetteur === $mysoc, true, 'PDF model receives the native issuer');
$db->query("INSERT INTO fixture_const VALUES ('MAIN_INFO_SOCIETE_NOM', 'Owner two', 2)");
$loaded->entity = 2;
$prepare = new ReflectionMethod($model, 'setEmitterFromObjectEntity');
$prepare->setAccessible(true);
$prepare->invoke($model, $loaded, $langs);
checkAttachment($model->emetteur->name, 'Owner two', 'PDF uses owner company in a shared consultation');
checkAttachment($mysoc->name, 'Native issuer', 'Preparing the owner company preserves the consultation context');
// Execute the real endpoint bootstrap flags without loading main.inc.php or an instance.
$endpoint = file_get_contents(__DIR__.'/../ajax/regeneratedocument.php');
$flags = substr($endpoint, strpos($endpoint, "if (!defined('NOTOKENRENEWAL'))"));
eval(substr($flags, 0, strpos($flags, '// Load Dolibarr environment')));
checkAttachment(defined('NOREQUIRESOC'), false, 'Ajax PDF generation permits native master.inc.php to load the issuer');
clearAttachmentFixture();
echo ($checks - $startchecks)." document-context assertions passed; native HookManager and security functions.\n";
