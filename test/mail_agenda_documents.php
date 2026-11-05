<?php
/* Copyright (C) 2026 Pierre Ardoin; SPDX-License-Identifier: GPL-3.0-or-later */
// Native Agenda file handling against disposable fixtures; no mail or live Agenda write.
require __DIR__.'/attachments.php';
require_once __DIR__.'/../class/actions_diffusion.class.php';
class MailAgendaDiffusion extends AttachmentDiffusion
{
	public $failtrigger = false;
	/** @var array{paths:list<string>,names:list<string>,mimes:list<string>} */
	public $attachedfiles = array('paths' => array(), 'names' => array(), 'mimes' => array());
}
$startchecks = $checks;
require_once DOL_DOCUMENT_ROOT.'/core/class/conf.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
$fixtureconf = $conf;
$conf = new Conf();
foreach (get_object_vars($fixtureconf) as $property => $value) { $conf->$property = $value; }
$conf->file->dol_document_root = array(DOL_DOCUMENT_ROOT);
$langs = new Translate('', $conf);
$langs->setDefaultLang('en_US');
function setEventMessages($message, $errors = null, $style = '') { throw new RuntimeException($message.' '.json_encode($errors)); }
$conf->modules_parts['triggers'] = array(); // Real dispatcher, no instance trigger modules in the fixture.
$source = file_get_contents(DOL_DOCUMENT_ROOT.'/core/triggers/interface_50_modAgenda_ActionsAuto.class.php');
$constant = strpos($source, 'MAIN_COPY_FILE_IN_EVENT_AUTO');
if ($constant === false) { throw new RuntimeException('Native Agenda transfer block missing'); }
$start = strrpos(substr($source, 0, $constant), 'if (');
$tokens = token_get_all('<?php '.substr($source, $start));
$transfer = '';
$depth = 0;
$opened = false;
foreach ($tokens as $token) {
	if (is_array($token)) {
		if ($token[0] !== T_OPEN_TAG) { $transfer .= $token[1]; }
	} else {
		$transfer .= $token;
		if ($token === '{') { $depth++; $opened = true; }
		if ($token === '}' && --$depth === 0 && $opened) { break; }
	}
}
foreach (array(1, 2) as $entity) {
	$fixture = attachmentFixture($entity);
	$conf->diffusion->multidir_output[1] = DOL_DATA_ROOT.'/diffusion';
	$object = new MailAgendaDiffusion($db);
	foreach (get_object_vars($fixture) as $property => $value) { $object->$property = $value; }
	$conf->entity = 1; // Also exercise shared consultation of the owning entity 2.
	$conf->diffusion->multidir_output[1] = DOL_DATA_ROOT.'/diffusion';
	$base = $conf->diffusion->multidir_output;
	$directory = diffusionGetDocumentUploadDir($object);
	$conf->diffusiondoc = (object) array('multidir_output' => $base); // Legacy/reset configuration.
	$conf->user = (object) array('dir_output' => DOL_DATA_ROOT.'/users');
	dol_mkdir($conf->user->dir_output);
	$user->id = 7;
	$name = dol_sanitizeFileName($object->ref).'.pdf';
	$pdf = $directory.'/'.$name;
	file_put_contents($pdf, 'Sent letter for entity '.$entity);
	$hash = hash_file('sha256', $pdf);
	// The native mail handler supplies this property after successful SMTP delivery.
	$object->attachedfiles = array('paths' => array($pdf), 'names' => array($name), 'mimes' => array('application/pdf'));
	$conf->agenda = (object) array('dir_output' => DOL_DATA_ROOT.'/agenda');
	$conf->global->MAIN_COPY_FILE_IN_EVENT_AUTO = '1';
	$copies = DiffusionAttachments::stageMailFiles($object, $user, $object->attachedfiles);
	checkAttachment(is_array($copies) && $copies['paths'][0] !== $pdf, true, 'Agenda receives a disposable copy');
	checkAttachment(hash_file('sha256', $copies['paths'][0]), $hash, 'The sent copy has identical bytes');
	checkAttachment($copies['names'], array($name), 'Recipient sees the original filename');
	checkAttachment(DiffusionAttachments::stageMailFiles($object, $user, $copies), $copies, 'A failed mail retry reuses the selected copies');
	$object->attachedfiles = $copies;
	$ret = 1; // Successful Agenda insertion; exercise its actual file-transfer block only.
	eval($transfer);
	checkAttachment(is_file($pdf), true, 'Successful post-mail Agenda handling preserves the letter');
	checkAttachment(hash_file('sha256', $pdf), $hash, 'Post-mail handling preserves exact sent PDF bytes');
	checkAttachment($conf->diffusion->multidir_output, $base, 'The module document roots remain unchanged');
	checkAttachment(getDolGlobalString('MAIN_COPY_FILE_IN_EVENT_AUTO'), '1', 'The global Agenda option remains enabled');
	$formmail = new FormMail($db);
	$formmail->trackid = 'diffusion'.$object->id;
	$formmail->add_attached_files($copies['paths'][0], $name, 'application/pdf');
	$formmail->clear_attached_files();
	checkAttachment(is_file($pdf) && !is_file($copies['paths'][0]), true, 'Native temporary cleanup preserves the stored letter');
	$conf->user->dir_output = '';
	checkAttachment(DiffusionAttachments::stageMailFiles($object, $user, array('paths' => array($pdf), 'names' => array($name), 'mimes' => array('application/pdf'))), null, 'Unavailable user temp directory blocks sending originals');
	clearAttachmentFixture();
}
echo ($checks - $startchecks)." post-mail Agenda assertions passed; native transfer block, simulated Agenda insertion.\n";
