<?php
/* Copyright (C) 2026 Pierre Ardoin; SPDX-License-Identifier: GPL-3.0-or-later */
// Native Agenda file handling against disposable fixtures; no mail or live Agenda write.
require __DIR__.'/attachments.php';
require_once __DIR__.'/../class/actions_diffusion.class.php';
class MailAgendaDiffusion extends Diffusion
{
	public $failtrigger = false;
	/** @var array{paths:list<string>,names:list<string>,mimes:list<string>} */
	public $attachedfiles = array('paths' => array(), 'names' => array(), 'mimes' => array());
}
$startchecks = $checks;
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
	$db->query('CREATE TABLE fixture_rights_def (perms TEXT, module TEXT, subperms TEXT, libelle TEXT)');
	unset($conf->diffusiondoc); // No hook instance exists in a fresh mail request.
	$conf->diffusion->multidir_output[1] = DOL_DATA_ROOT.'/diffusion';
	$object = new MailAgendaDiffusion($db);
	foreach (get_object_vars($fixture) as $property => $value) { $object->$property = $value; }
	$conf->entity = 1; // Also exercise shared consultation of the owning entity 2.
	$conf->diffusion->multidir_output[1] = DOL_DATA_ROOT.'/diffusion';
	$base = $conf->diffusion->multidir_output;
	$directory = diffusionGetDocumentUploadDir($object);
	$native = getMultidirOutput($object, '', 1);
	checkAttachment(rtrim($native, '/'), $directory, 'Native Agenda destination equals the owner document directory');
	$name = dol_sanitizeFileName($object->ref).'.pdf';
	$pdf = $directory.'/'.$name;
	file_put_contents($pdf, 'Sent letter for entity '.$entity);
	$hash = hash_file('sha256', $pdf);
	// The native mail handler supplies this property after successful SMTP delivery.
	$object->attachedfiles = array('paths' => array($pdf), 'names' => array($name), 'mimes' => array('application/pdf'));
	$conf->agenda = (object) array('dir_output' => DOL_DATA_ROOT.'/agenda');
	$conf->global->MAIN_COPY_FILE_IN_EVENT_AUTO = '1';
	// Native email preparation may reload configuration after object construction.
	$conf->diffusiondoc->multidir_output = $base;
	$object->call_trigger('DIFFUSION_SENDMAIL', $user);
	$ret = 1; // Successful Agenda insertion; exercise its actual file-transfer block only.
	eval($transfer);
	checkAttachment(is_file($pdf), true, 'Successful post-mail Agenda handling preserves the letter');
	checkAttachment(hash_file('sha256', $pdf), $hash, 'Post-mail handling preserves exact sent PDF bytes');
	checkAttachment($conf->diffusion->multidir_output, $base, 'The module document roots remain unchanged');
	checkAttachment(getDolGlobalString('MAIN_COPY_FILE_IN_EVENT_AUTO'), '1', 'The global Agenda option remains enabled');
	$hooks = new ActionsDiffusion($db);
	checkAttachment(rtrim(getMultidirOutput($object, '', 1), '/'), $directory, 'Repeated hook initialization does not duplicate the subdirectory');
	clearAttachmentFixture();
}
echo ($checks - $startchecks)." post-mail Agenda assertions passed; native transfer block, simulated Agenda insertion.\n";
