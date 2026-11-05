<?php
/* Copyright (C) 2026 Pierre Ardoin; SPDX-License-Identifier: GPL-3.0-or-later */
// Disposable fixtures and native FormMail; no email or live ERP session.
require __DIR__.'/attachments.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
class MailLetterFixture extends Diffusion
{
	public $failtrigger = false;
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		return file_put_contents(diffusionGetDocumentUploadDir($this).'/'.dol_sanitizeFileName($this->ref).'.pdf', 'Generated letter') === false ? -1 : 1;
	}
}
$startchecks = $checks;
$_SESSION = array();
$_SERVER['REQUEST_METHOD'] = 'POST';
$formmail = new FormMail($db);
$card = file_get_contents(__DIR__.'/../diffusion_card.php');
$start = strpos($card, "\t// Mail attachment removal changes");
$end = strpos($card, "\t// End mail attachment removal.", $start);
$removal = substr($card, $start, $end - $start);
$removal = str_replace("include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';", "throw new RuntimeException('Removal reached native destructive handler');", $removal);
$template = file_get_contents(__DIR__.'/../tpl/card_presend.tpl.php');
$start = strpos($template, '$ref = dol_sanitizeFileName');
$end = strpos($template, "\t// Define output language", $start);
$lookup = substr($template, $start, $end - $start);
$start = strpos($template, 'if ($forcebuilddoc)');
$end = strpos($template, "\tprint ", $start);
$build = substr($template, $start, $end - $start);
foreach (array(1, 2) as $entity) {
	$fixture = attachmentFixture($entity);
	$object = (new ReflectionClass('MailLetterFixture'))->newInstanceWithoutConstructor();
	foreach (get_object_vars($fixture) as $property => $value) {
		$object->$property = $value;
	}
	$directory = diffusionGetDocumentUploadDir($object);
	$pdf = $directory.'/'.dol_sanitizeFileName($object->ref).'.pdf';
	file_put_contents($pdf, 'Letter belonging to entity '.$entity);
	$zip = $directory.'/'.dol_sanitizeFileName($object->ref).'-archive.zip';
	file_put_contents($zip, 'Archive');
	$before = hash_file('sha256', $pdf);
	$object->last_main_doc = 'unusable/old.pdf';
	$fileparams = array();
	eval($lookup);
	checkAttachment(str_replace('\\', '/', (string) $file), str_replace('\\', '/', $pdf), 'Mail finds the owner letter despite a stale last_main_doc');
	$trackid = 'diffusion'.$object->id;
	$formmail->trackid = $trackid;
	$formmail->add_attached_files($pdf);
	$formmail->add_attached_files($zip);
	$_SESSION['listofpaths-diffusion999'] = 'other-object.pdf';
	$_GET = array();
	$_POST = $_REQUEST = array('removedfile' => '1', 'trackid' => 'diffusion999');
	eval($removal);
	checkAttachment($formmail->get_attached_files()['paths'], array($zip), 'Individual removal changes only the mail selection');
	checkAttachment(hash_file('sha256', $pdf), $before, 'Individual removal preserves PDF bytes');
	$formmail->add_attached_files($pdf);
	$_POST = $_REQUEST = array('removeAll' => '1', 'trackid' => 'diffusion999');
	eval($removal);
	checkAttachment($formmail->get_attached_files()['paths'], array(), 'Remove all empties the selection');
	checkAttachment(hash_file('sha256', $pdf), $before, 'Remove all preserves PDF bytes');
	checkAttachment(is_file($zip), true, 'Remove all preserves stored attachments');
	checkAttachment($_SESSION['listofpaths-diffusion999'], 'other-object.pdf', 'Forged trackid cannot change another selection');
	checkAttachment($action, 'presend', 'Removal returns to composer without sending');
	unlink($pdf);
	$fileparams = array();
	eval($lookup);
	checkAttachment(empty($file), true, 'A ZIP is never selected in place of the letter');
	$forcebuilddoc = true;
	$outputlangs = $langs;
	$hidedetails = $hidedesc = $hideref = 0;
	$_POST = $_REQUEST = array();
	eval($build);
	checkAttachment(str_replace('\\', '/', (string) $file), str_replace('\\', '/', $pdf), 'Mail finds the letter after generation in the owner directory');
	// The bundled ODT generator appends the template name and optional timestamp.
	foreach (array('_letter.odt', '_letter-20261105103000.odt', '_letter.ods') as $suffix) {
		unlink($pdf);
		$odt = $directory.'/'.dol_sanitizeFileName($object->ref).$suffix;
		file_put_contents($odt, 'Generated template letter');
		$fileparams = array();
		eval($lookup);
		checkAttachment(str_replace('\\', '/', (string) $file), str_replace('\\', '/', $odt), 'Composer finds template-suffixed office letters');
		// Exercise the same lookup after generation as well.
		$generatedlookupstart = strpos($build, "if (\$object->element === 'diffusiondoc')");
		$generatedlookupend = strpos($build, '$file = isset', $generatedlookupstart);
		eval(substr($build, $generatedlookupstart, $generatedlookupend - $generatedlookupstart));
		checkAttachment(str_replace('\\', '/', $fileparams['fullname']), str_replace('\\', '/', $odt), 'Post-generation lookup finds template-suffixed office letters');
		unlink($odt);
		file_put_contents($pdf, 'Generated letter');
	}
	clearAttachmentFixture();
}
echo ($checks - $startchecks)." mail attachment assertions passed; native FormMail and disposable files.\n";
