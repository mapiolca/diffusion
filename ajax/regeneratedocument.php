<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    ajax/regeneratedocument.php
 * \ingroup diffusion
 * \brief   Regenerate a diffusion document after an Ajax file upload.
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('CSRFCHECK_WITH_TOKEN')) {
	define('CSRFCHECK_WITH_TOKEN', '1');
}

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

dol_include_once('/diffusion/class/diffusion.class.php');
dol_include_once('/diffusion/lib/diffusion_diffusion.lib.php');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array('diffusion@diffusion', 'errors'));

/**
 * Return a JSON response and stop execution.
 *
 * @param int                  $status  HTTP status
 * @param array<string,mixed>  $payload Response payload
 * @return never
 */
function diffusionAjaxRegenerateDocumentResponse($status, $payload)
{
	top_httphead();
	http_response_code($status);
	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode($payload);
	exit;
}

$action = GETPOST('action', 'aZ09');
$id = GETPOSTINT('id');
$files = GETPOST('files', 'array');
if (!is_array($files)) {
	$files = array();
}

if ($action !== 'regenerate' || $id <= 0) {
	diffusionAjaxRegenerateDocumentResponse(400, array('status' => 'error', 'errors' => array('Bad parameters')));
}
if (!isModEnabled('diffusion')) {
	diffusionAjaxRegenerateDocumentResponse(403, array('status' => 'error', 'errors' => array('Module disabled')));
}
if (empty($user->admin) && !$user->hasRight('diffusion', 'diffusiondoc', 'write')) {
	diffusionAjaxRegenerateDocumentResponse(403, array('status' => 'error', 'errors' => array('Not enough permissions')));
}

$object = new Diffusion($db);
$result = $object->fetch($id);
if ($result <= 0) {
	diffusionAjaxRegenerateDocumentResponse(404, array('status' => 'error', 'errors' => array('Diffusion not found')));
}

$allowedentities = array_map('intval', explode(',', (string) getEntity('diffusion')));
if (!empty($object->entity) && !in_array((int) $object->entity, $allowedentities, true)) {
	diffusionAjaxRegenerateDocumentResponse(403, array('status' => 'error', 'errors' => array('Entity not allowed')));
}

$upload_dir = diffusionGetDocumentUploadDir($object);
diffusionMigrateFlatDocumentDirectory($db, $object);
$result = diffusionRegenerateDocumentAfterLinkedFileChange($db, $object, $upload_dir, $user, $langs, 'ajaxupload', $files);
if ($result < 0) {
	$errors = array();
	if (!empty($object->error)) {
		$errors[] = $object->error;
	}
	if (!empty($object->errors)) {
		$errors = array_merge($errors, $object->errors);
	}
	if (empty($errors)) {
		$errors[] = $langs->trans('DiffusionDocumentRegenerationAfterFileChangeFailed');
	}
	diffusionAjaxRegenerateDocumentResponse(500, array('status' => 'error', 'errors' => $errors));
}

diffusionAjaxRegenerateDocumentResponse(200, array('status' => 'ok'));
