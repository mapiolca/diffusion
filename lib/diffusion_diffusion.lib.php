<?php
/* Copyright (C) 2025-2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lib/diffusion_diffusion.lib.php
 * \ingroup diffusion
 * \brief   Library files with common functions for Diffusion
 */

/**
 * Prepare array of tabs for Diffusion
 *
 * @param	Diffusion	$object					Diffusion
 * @return 	array<array{string,string,string}>	Array of tabs
 */
function diffusionPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("diffusion@diffusion");

	$showtabofpagecontact = 0;
	$showtabofpagenote = 0;
	$showtabofpagedocument = (empty($object->is_template) ? 1 : 0);
	$showtabofpageagenda = 1;

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/diffusion/diffusion_card.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans((!empty($object->is_template) ? "DiffusionModele" : "Diffusion"));
	$head[$h][2] = 'card';
	$h++;

	if (!empty($object->is_template)) {
		$head[$h][0] = dol_buildpath("/diffusion/diffusion_generated_list.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans("DiffusionsGenerees");
		$head[$h][2] = 'generated';
		$h++;
	}

	if ($showtabofpagecontact) {
		$head[$h][0] = dol_buildpath("/diffusion/diffusion_contact.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans("Contacts");
		$head[$h][2] = 'contact';
		$h++;
	}

	if ($showtabofpagenote) {
		if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
			$nbNote = 0;
			if (!empty($object->note_private)) {
				$nbNote++;
			}
			if (!empty($object->note_public)) {
				$nbNote++;
			}
			$head[$h][0] = dol_buildpath('/diffusion/diffusion_note.php', 1).'?id='.$object->id;
			$head[$h][1] = $langs->trans('Notes');
			if ($nbNote > 0) {
				$head[$h][1] .= (!getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">'.$nbNote.'</span>' : '');
			}
			$head[$h][2] = 'note';
			$h++;
		}
	}

	if ($showtabofpagedocument) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
		$entityfordoc = !empty($object->entity) ? (int) $object->entity : 1;
		if (!isset($conf->diffusion) || !is_object($conf->diffusion)) {
			$conf->diffusion = new stdClass();
		}
		if (empty($conf->diffusion->multidir_output) || !is_array($conf->diffusion->multidir_output)) {
			$conf->diffusion->multidir_output = array();
		}
		if (empty($conf->diffusion->multidir_output[$entityfordoc])) {
			$conf->diffusion->multidir_output[$entityfordoc] = DOL_DATA_ROOT.($entityfordoc > 1 ? '/'.$entityfordoc : '').'/diffusion';
		}
		$upload_dir = function_exists('getMultidirOutput') ? getMultidirOutput($object, 'diffusion', 1) : '';
		if (empty($upload_dir)) {
			$upload_dir = $conf->diffusion->multidir_output[$entityfordoc]."/".dol_sanitizeFileName($object->ref);
		}
		$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
		$nbLinks = Link::count($db, $object->element, $object->id);
		$head[$h][0] = dol_buildpath("/diffusion/diffusion_document.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans('AttachedFiles');
		if (($nbFiles + $nbLinks) > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
		}
		$head[$h][2] = 'document';
		$h++;
	}

	if ($showtabofpageagenda) {
		$head[$h][0] = dol_buildpath("/diffusion/diffusion_agenda.php", 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans("EventsAgenda");
		$head[$h][2] = 'agenda';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@diffusion:/diffusion/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@diffusion:/diffusion/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'diffusion@diffusion');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'diffusion@diffusion', 'remove');

	return $head;
}

/**
 * Complete missing public ECM shares for files attached to a diffusion.
 *
 * @param DoliDB       $db         Database handler
 * @param Diffusion    $object     Diffusion object
 * @param string       $upload_dir Absolute upload directory
 * @param User         $user       Current user
 * @return int                     Number of completed shares, <0 on error
 */
function diffusionCompleteAttachedFileShares($db, $object, $upload_dir, $user)
{
	if (empty($object->id) || empty($upload_dir) || !getDolGlobalInt('DIFFUSION_ALLOW_EXTERNAL_DOWNLOAD')) {
		return 0;
	}

	$relUploadDir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', (string) $upload_dir);
	if (preg_match('/[\\/]temp[\\/]|[\\/]thumbs|\.meta$/', $relUploadDir)) {
		return 0;
	}

	$relUploadDir = preg_replace('/[\\/]$/', '', $relUploadDir);
	$relUploadDir = preg_replace('/^[\\/]/', '', $relUploadDir);

	require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

	$sql = "SELECT rowid";
	$sql .= " FROM ".MAIN_DB_PREFIX."ecm_files";
	$sql .= " WHERE src_object_type = '".$db->escape($object->table_element)."'";
	$sql .= " AND src_object_id = ".((int) $object->id);
	$sql .= " AND filepath = '".$db->escape($relUploadDir)."'";
	$sql .= " AND (share IS NULL OR share = '')";

	$resql = $db->query($sql);
	if (!$resql) {
		return -1;
	}

	$count = 0;
	while ($objFile = $db->fetch_object($resql)) {
		$ecmfile = new EcmFiles($db);
		if ($ecmfile->fetch((int) $objFile->rowid) > 0 && empty($ecmfile->share)) {
			$ecmfile->share = getRandomPassword(true);
			$updateresult = $ecmfile->update($user);
			if ($updateresult < 0) {
				$db->free($resql);
				return -1;
			}
			$count++;
		}
	}
	$db->free($resql);

	return $count;
}

/**
 * Run non-blocking post-upload processing on diffusion files.
 *
 * @param DoliDB       $db         Database handler
 * @param Diffusion    $object     Diffusion object
 * @param string       $upload_dir Absolute upload directory
 * @param User         $user       Current user
 * @param Translate    $langs      Translation handler
 * @return int<-1,1>               1 if OK, -1 if a warning was raised
 */
function diffusionPostProcessUploadedFiles($db, $object, $upload_dir, $user, $langs)
{
	if (empty($object->id) || !empty($object->is_template)) {
		return 1;
	}

	$error = 0;

	$shareresult = diffusionCompleteAttachedFileShares($db, $object, $upload_dir, $user);
	if ($shareresult < 0) {
		$error++;
		setEventMessages($db->lasterror(), null, 'warnings');
	}

	$object->fetch((int) $object->id);
	$result = $object->generateDocument('', $langs);
	if ($result < 0) {
		$error++;
		$message = !empty($object->error) ? $object->error : $langs->trans('DiffusionDocumentRegenerationAfterUploadFailed');
		setEventMessages($message, $object->errors, 'warnings');
	}

	return $error ? -1 : 1;
}
