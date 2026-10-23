<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/** Authenticated diagnosis and reviewed repair of the historical LTR2610-0005 index. */
$res = 0;
if (!empty($_SERVER['CONTEXT_DOCUMENT_ROOT']) && is_file($_SERVER['CONTEXT_DOCUMENT_ROOT'].'/main.inc.php')) {
	$res = include $_SERVER['CONTEXT_DOCUMENT_ROOT'].'/main.inc.php';
}
foreach (array(__DIR__.'/../../main.inc.php', __DIR__.'/../../../main.inc.php', __DIR__.'/../../../../main.inc.php') as $bootstrap) {
	if (!$res && is_file($bootstrap)) {
		$res = include $bootstrap;
	}
}
if (!$res) {
	http_response_code(503);
	exit('Dolibarr bootstrap unavailable');
}

/** @var User $user */
/** @var DoliDB $db */
/** @var Translate $langs */
if (!$user->admin || !empty($user->socid) || !isModEnabled('diffusion')) {
	accessforbidden();
}
if (!in_array($_SERVER['REQUEST_METHOD'], array('GET', 'POST'), true)) {
	accessforbidden();
}

require_once __DIR__.'/../class/diffusion.class.php';
require_once __DIR__.'/../class/diffusionattachments.class.php';
$langs->loadLangs(array('diffusion@diffusion', 'other'));

$object = new Diffusion($db);
if ($object->fetch(21, null, 1, 1) <= 0 || $object->ref !== 'LTR2610-0005'
	|| (int) $object->entity !== 1 || !empty($object->is_template)
	|| !DiffusionAttachments::checkObjectScope($object, $user)) {
	accessforbidden();
}
$entry = new EcmFiles($db);
if ($entry->fetch(865) <= 0 || (int) $entry->entity !== (int) $object->entity) {
	accessforbidden();
}
$directory = diffusionGetDocumentUploadDir($object);
if ($directory === '' || !DiffusionAttachments::isSafeDirectory($directory, true)
	|| !is_string($entry->filename) || basename($entry->filename) !== $entry->filename) {
	accessforbidden();
}
$relative = diffusionGetDocumentPathRelativeToDataRoot($directory);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (GETPOST('action', 'aZ09') !== 'repaircase') {
		accessforbidden();
	}
	require_once __DIR__.'/attachment_index_case.class.php';
	$maintenance = new DiffusionAttachmentIndexCase();
	$result = $maintenance->repair($object, $user, 865, 19, 'LTR2610-0005-DEVIS 2026CB127 JP SUN Signe.pdf',
		'4053c73dbcfca066c6d8ea5accd90599621182b36ec5adfd297c2bb58a7d7e17');
	setEventMessages($langs->trans($result < 0 ? $maintenance->error : ($result > 0 ? 'DiffusionAttachmentCaseRepaired' : 'DiffusionAttachmentCaseAlreadyRepaired')), null, $result < 0 ? 'errors' : 'mesgs');
	header('Location: '.$_SERVER['PHP_SELF']);
	exit;
}
$filename = $directory.'/'.$entry->filename;
$exists = is_file($filename);
$checksum = $exists ? md5_file($filename) : false;
$sha256 = $exists ? hash_file('sha256', $filename) : false;
$source = new Diffusion($db);
$sourceResult = (int) $entry->src_object_id > 0 && in_array($entry->src_object_type, array($object->element, $object->table_element, 'diffusiondoc@diffusion'), true)
	? $source->fetch((int) $entry->src_object_id, null, 1, 1) : 0;
$sourceAccessible = $sourceResult > 0 && DiffusionAttachments::checkObjectScope($source, $user);

$matches = array();
$sql = 'SELECT rowid, src_object_type, src_object_id FROM '.MAIN_DB_PREFIX.'ecm_files';
$sql .= ' WHERE entity = '.((int) $object->entity);
$sql .= " AND filepath = '".$db->escape($relative)."' AND filename = '".$db->escape($entry->filename)."'";
$resql = $db->query($sql);
if (!$resql) {
	http_response_code(503);
	exit($langs->trans('Error'));
}
while (is_object($row = $db->fetch_object($resql))) {
	$matches[] = array('id' => (int) $row->rowid, 'source_type' => $row->src_object_type, 'source_id' => (int) $row->src_object_id);
}
$db->free($resql);

$diagnostic = array(
	'php' => PHP_VERSION,
	'dolibarr' => DOL_VERSION,
	'object_id' => (int) $object->id,
	'object_ref' => $object->ref,
	'entity' => (int) $object->entity,
	'ecm_id' => (int) $entry->id,
	'source_type' => $entry->src_object_type,
	'source_id' => (int) $entry->src_object_id,
	'relative_path' => $entry->filepath,
	'expected_path' => $relative,
	'filename' => $entry->filename,
	'has_public_key' => !empty($entry->share),
	'file_exists' => $exists,
	'checksum_matches' => $checksum !== false && $entry->label === $checksum,
	'label_is_md5' => is_string($entry->label) && preg_match('/^[a-f0-9]{32}$/iD', $entry->label) === 1,
	'file_sha256' => $sha256 !== false ? $sha256 : null,
	'source_fetch_result' => $sourceResult,
	'source_accessible' => $sourceAccessible,
	'source_entity' => $sourceAccessible ? (int) $source->entity : null,
	'source_ref' => $sourceAccessible ? $source->ref : null,
	'indexes_at_expected_path' => $matches,
);

header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
llxHeader('', $langs->trans('DiffusionAttachmentIndexDiagnostic'));
print load_fiche_titre($langs->trans('DiffusionAttachmentIndexDiagnostic'));
print '<p>'.$langs->trans('DiffusionAttachmentIndexDiagnosticHelp').'</p>';
print '<pre id="attachment-index-diagnostic">'.dol_escape_htmltag((string) json_encode($diagnostic, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 0, 1).'</pre>';
if ((int) $entry->src_object_id === 19 && $sourceResult === 0 && $entry->filepath === $relative
	&& $sha256 === '4053c73dbcfca066c6d8ea5accd90599621182b36ec5adfd297c2bb58a7d7e17'
	&& $entry->filename === 'LTR2610-0005-DEVIS 2026CB127 JP SUN Signe.pdf' && count($matches) === 1
	&& !empty($entry->share) && $checksum !== false && $entry->label === $checksum) {
	print '<form method="POST" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="repaircase">';
	print '<input class="button" type="submit" value="'.dol_escape_htmltag($langs->trans('DiffusionAttachmentCaseRepair')).'">';
	print '</form>';
}
llxFooter();
$db->close();
