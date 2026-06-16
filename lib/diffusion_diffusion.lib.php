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
		$upload_dir = diffusionGetDocumentUploadDir($object);
		if (!empty($upload_dir)) {
			diffusionMigrateFlatDocumentDirectory($db, $object);
		}
		$nbFiles = (!empty($upload_dir) && is_dir(dol_osencode($upload_dir))) ? count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta$|\.tmp$|_preview.*\.png$|\.preview\.png$)')) : 0;
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
 * Normalize submitted linked file names.
 *
 * @param array<int,string>|string $filenames File names or paths
 * @return array<int,string>                  Normalized base file names
 */
function diffusionNormalizeLinkedFileNames($filenames)
{
	if (!is_array($filenames)) {
		$filenames = array($filenames);
	}

	$normalized = array();
	foreach ($filenames as $filename) {
		$basename = basename(str_replace('\\', '/', (string) $filename));
		$basename = dol_string_nohtmltag($basename);
		if ($basename === '') {
			continue;
		}
		$normalized[$basename] = $basename;
	}

	return array_values($normalized);
}

/**
 * Tell if an attached file is a technical file that must not be indexed/shared.
 *
 * @param Diffusion $object   Diffusion object
 * @param string    $filename File name or path
 * @return bool               True if the file is technical
 */
function diffusionIsTechnicalAttachedFile($object, $filename)
{
	$filename = str_replace('\\', '/', (string) $filename);
	$basename = basename($filename);

	return $basename === ''
		|| $basename === '.'
		|| $basename === '..'
		|| preg_match('/(^|\/)thumbs(\/|$)/i', $filename)
		|| preg_match('/\.meta$/i', $basename)
		|| preg_match('/\.tmp$/i', $basename)
		|| preg_match('/_preview.*\.png$/i', $basename)
		|| preg_match('/\.preview\.png$/i', $basename)
		|| diffusionIsGeneratedDocumentFile($object, $basename);
}

/**
 * Ensure ECM index and public shares for files attached to a diffusion.
 *
 * @param DoliDB                  $db         Database handler
 * @param Diffusion               $object     Diffusion object
 * @param string                  $upload_dir Absolute upload directory
 * @param User                    $user       Current user
 * @param array<int,string>|string $filenames Optional list of uploaded file names
 * @return int                                Number of indexed/updated files, <0 on error
 */
function diffusionEnsureAttachedFilesIndexedAndShared($db, $object, $upload_dir, $user, $filenames = array())
{
	global $conf;

	if (empty($object->id) || empty($upload_dir)) {
		return 0;
	}

	$relUploadDir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', (string) $upload_dir);
	if (preg_match('/[\\/]temp[\\/]|[\\/]thumbs|\.meta$/', $relUploadDir)) {
		return 0;
	}

	$relUploadDir = preg_replace('/[\\/]$/', '', $relUploadDir);
	$relUploadDir = preg_replace('/^[\\/]/', '', $relUploadDir);
	$upload_dir = rtrim((string) $upload_dir, '/\\');
	$objectentity = !empty($object->entity) ? (int) $object->entity : (int) $conf->entity;
	$setsharekey = getDolGlobalInt('DIFFUSION_ALLOW_EXTERNAL_DOWNLOAD') ? 1 : 0;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	if ($setsharekey) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
	}

	$candidates = diffusionNormalizeLinkedFileNames($filenames);
	if (empty($candidates)) {
		$fileList = dol_dir_list($upload_dir, 'files', 0, '', '(\.meta$|\.tmp$|_preview.*\.png$|\.preview\.png$)', 'name', SORT_ASC, 1);
		foreach ($fileList as $fileinfo) {
			if (!empty($fileinfo['name'])) {
				$candidates[] = $fileinfo['name'];
			}
		}
		$candidates = diffusionNormalizeLinkedFileNames($candidates);
	}

	$count = 0;
	foreach ($candidates as $filename) {
		if (diffusionIsTechnicalAttachedFile($object, $filename)) {
			continue;
		}

		$fullpath = $upload_dir.'/'.$filename;
		if (!is_file(dol_osencode($fullpath))) {
			continue;
		}

		$sql = 'SELECT rowid, share, src_object_type, src_object_id, entity';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'ecm_files';
		$sql .= " WHERE filepath = '".$db->escape($relUploadDir)."'";
		$sql .= " AND filename = '".$db->escape($filename)."'";
		$sql .= ' AND entity = '.$objectentity;
		$resql = $db->query($sql);
		if (!$resql) {
			return -1;
		}
		$objFile = $db->fetch_object($resql);
		$db->free($resql);
		if (empty($objFile)) {
			$result = addFileIntoDatabaseIndex($upload_dir, $filename, $filename, 'uploaded', $setsharekey, $object);
			if ($result < 0) {
				return -1;
			}
			if ($result > 0) {
				$count++;
			}
			continue;
		}

		$updates = array();
		if ((string) $objFile->src_object_type !== (string) $object->table_element) {
			$updates[] = "src_object_type = '".$db->escape((string) $object->table_element)."'";
		}
		if ((int) $objFile->src_object_id !== (int) $object->id) {
			$updates[] = 'src_object_id = '.((int) $object->id);
		}
		if ((int) $objFile->entity !== $objectentity) {
			$updates[] = 'entity = '.$objectentity;
		}
		if ($setsharekey && empty($objFile->share)) {
			$updates[] = "share = '".$db->escape(getRandomPassword(true))."'";
		}
		if (!empty($updates)) {
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'ecm_files SET '.implode(', ', $updates);
			$sql .= ' WHERE rowid = '.((int) $objFile->rowid);
			if (!$db->query($sql)) {
				return -1;
			}
			$count++;
		}
	}

	return $count;
}

/**
 * Complete missing public ECM shares for files attached to a diffusion.
 *
 * @param DoliDB       $db         Database handler
 * @param Diffusion    $object     Diffusion object
 * @param string       $upload_dir Absolute upload directory
 * @param User         $user       Current user
 * @return int                     Number of indexed/updated files, <0 on error
 */
function diffusionCompleteAttachedFileShares($db, $object, $upload_dir, $user)
{
	return diffusionEnsureAttachedFilesIndexedAndShared($db, $object, $upload_dir, $user);
}

/**
 * Return the Dolibarr document modulepart used by Diffusion files.
 *
 * @return string Modulepart
 */
function diffusionGetDocumentModulepart()
{
	return 'diffusion';
}

/**
 * Return the document permission segment used below the modulepart directory.
 *
 * @return string Document element
 */
function diffusionGetDocumentElement()
{
	return 'diffusiondoc';
}

/**
 * Return the output base directory of the diffusion module for the object owning entity.
 *
 * @param Diffusion $object Diffusion object
 * @return string           Absolute module document directory
 */
function diffusionGetDocumentBaseOutputDir($object)
{
	global $conf;

	if (!is_object($object)) {
		return '';
	}

	$entityfordoc = !empty($object->entity) ? (int) $object->entity : (int) $conf->entity;
	if ($entityfordoc <= 0) {
		$entityfordoc = 1;
	}

	if (!isset($conf->diffusion) || !is_object($conf->diffusion)) {
		$conf->diffusion = new stdClass();
	}
	if (empty($conf->diffusion->multidir_output) || !is_array($conf->diffusion->multidir_output)) {
		$conf->diffusion->multidir_output = array();
	}

	$defaultoutput = DOL_DATA_ROOT.($entityfordoc > 1 ? '/'.$entityfordoc : '').'/'.diffusionGetDocumentModulepart();
	if (empty($conf->diffusion->multidir_output[$entityfordoc])) {
		$conf->diffusion->multidir_output[$entityfordoc] = ($entityfordoc > 1 ? $defaultoutput : (!empty($conf->diffusion->dir_output) ? $conf->diffusion->dir_output : $defaultoutput));
	}
	if (!isset($conf->diffusion->enabled)) {
		$conf->diffusion->enabled = 1;
	}

	return rtrim((string) $conf->diffusion->multidir_output[$entityfordoc], '/\\');
}

/**
 * Return the relative canonical document path for document.php and FormFile links.
 *
 * @param Diffusion $object Diffusion object
 * @return string           Relative path with trailing slash
 */
function diffusionGetDocumentRelativePath($object)
{
	if (!is_object($object) || empty($object->ref)) {
		return '';
	}

	return diffusionGetDocumentElement().'/'.dol_sanitizeFileName($object->ref).'/';
}

/**
 * Return the canonical document directory of a diffusion for its owning entity.
 *
 * @param Diffusion $object Diffusion object
 * @return string           Absolute document directory
 */
function diffusionGetDocumentUploadDir($object)
{
	if (!is_object($object) || empty($object->ref)) {
		return '';
	}

	$baseoutput = diffusionGetDocumentBaseOutputDir($object);
	$relativepath = diffusionGetDocumentRelativePath($object);
	if (empty($baseoutput) || empty($relativepath)) {
		return '';
	}

	return rtrim($baseoutput.'/'.trim($relativepath, '/'), '/\\');
}

/**
 * Return the old flat Diffusion document directory used by previous 1.3.0 builds.
 *
 * @param Diffusion $object Diffusion object
 * @return string           Absolute flat document directory
 */
function diffusionGetFlatDocumentUploadDir($object)
{
	if (!is_object($object) || empty($object->ref)) {
		return '';
	}

	$baseoutput = diffusionGetDocumentBaseOutputDir($object);
	if (empty($baseoutput)) {
		return '';
	}

	return rtrim($baseoutput.'/'.dol_sanitizeFileName($object->ref), '/\\');
}

/**
 * Return the old flat relative path used by previous 1.3.0 builds.
 *
 * @param Diffusion $object Diffusion object
 * @return string           Relative path with trailing slash
 */
function diffusionGetFlatDocumentRelativePath($object)
{
	if (!is_object($object) || empty($object->ref)) {
		return '';
	}

	return dol_sanitizeFileName($object->ref).'/';
}

/**
 * Return the old flat Diffusion document directory kept for migration compatibility.
 *
 * @param Diffusion $object     Diffusion object
 * @param string    $upload_dir Current absolute upload directory, unused
 * @return string               Absolute flat document directory
 */
function diffusionGetLegacyDocumentUploadDir($object, $upload_dir = '')
{
	return diffusionGetFlatDocumentUploadDir($object);
}

/**
 * Return the old flat relative path kept for migration compatibility.
 *
 * @param Diffusion $object Diffusion object
 * @return string           Relative path with trailing slash
 */
function diffusionGetLegacyDocumentRelativePath($object)
{
	return diffusionGetFlatDocumentRelativePath($object);
}

/**
 * Return a path relative to DOL_DATA_ROOT.
 *
 * @param string $path Absolute path
 * @return string      Relative path without trailing slash
 */
function diffusionGetDocumentPathRelativeToDataRoot($path)
{
	$relativepath = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', (string) $path);
	$relativepath = preg_replace('/[\\/]$/', '', (string) $relativepath);
	$relativepath = preg_replace('/^[\\/]/', '', (string) $relativepath);

	return (string) $relativepath;
}

/**
 * Move files from the old flat directory to the canonical permission-aware directory.
 *
 * @param DoliDB    $db     Database handler
 * @param Diffusion $object Diffusion object
 * @return int              Number of moved entries, <0 on error
 */
function diffusionMigrateFlatDocumentDirectory($db, $object)
{
	global $conf;

	if (!is_object($db) || !is_object($object) || empty($object->id) || empty($object->ref)) {
		return 0;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$canonicaldir = diffusionGetDocumentUploadDir($object);
	$flatdir = diffusionGetFlatDocumentUploadDir($object);
	if (empty($canonicaldir) || empty($flatdir) || rtrim($canonicaldir, '/\\') === rtrim($flatdir, '/\\')) {
		return 0;
	}
	if (!is_dir(dol_osencode($flatdir))) {
		return 0;
	}
	if (!is_dir(dol_osencode($canonicaldir)) && dol_mkdir($canonicaldir) < 0) {
		dol_syslog(__METHOD__.' failed to create canonical document directory '.$canonicaldir, LOG_ERR);
		return -1;
	}

	$moved = 0;
	$error = 0;
	$movedentries = array();
	$entries = scandir(dol_osencode($flatdir));
	if (!is_array($entries)) {
		return 0;
	}

	foreach ($entries as $entry) {
		if ($entry === '.' || $entry === '..') {
			continue;
		}

		$source = rtrim($flatdir, '/\\').'/'.$entry;
		$target = rtrim($canonicaldir, '/\\').'/'.$entry;
		if (file_exists(dol_osencode($target))) {
			dol_syslog(__METHOD__.' keeps flat document because canonical target already exists source='.$source.' target='.$target, LOG_WARNING);
			continue;
		}
		if (!@rename(dol_osencode($source), dol_osencode($target))) {
			dol_syslog(__METHOD__.' failed to move flat document source='.$source.' target='.$target, LOG_WARNING);
			$error++;
			continue;
		}
		$moved++;
		$movedentries[] = $entry;
	}

	if ($moved > 0) {
		$oldrelpath = diffusionGetDocumentPathRelativeToDataRoot($flatdir);
		$newrelpath = diffusionGetDocumentPathRelativeToDataRoot($canonicaldir);
		$objectentity = !empty($object->entity) ? (int) $object->entity : (int) $conf->entity;

		if ($oldrelpath !== '' && $newrelpath !== '') {
			$escapedfilenames = array();
			foreach ($movedentries as $movedentry) {
				$escapedfilenames[] = "'".$db->escape($movedentry)."'";
			}

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'ecm_files';
			$sql .= " SET filepath = '".$db->escape($newrelpath)."'";
			$sql .= " WHERE filepath = '".$db->escape($oldrelpath)."'";
			$sql .= ' AND entity = '.$objectentity;
			if (!empty($escapedfilenames)) {
				$sql .= ' AND filename IN ('.implode(', ', $escapedfilenames).')';
			}
			if (!$db->query($sql)) {
				dol_syslog(__METHOD__.' failed to update ECM filepath from '.$oldrelpath.' to '.$newrelpath.': '.$db->lasterror(), LOG_WARNING);
				$error++;
			}

			if (!empty($object->last_main_doc) && strpos((string) $object->last_main_doc, $oldrelpath.'/') === 0) {
				$lastmaindocname = basename(str_replace('\\', '/', (string) $object->last_main_doc));
				if (in_array($lastmaindocname, $movedentries, true)) {
					$newlastmaindoc = $newrelpath.substr((string) $object->last_main_doc, strlen($oldrelpath));
					$sql = 'UPDATE '.MAIN_DB_PREFIX.$object->table_element;
					$sql .= " SET last_main_doc = '".$db->escape($newlastmaindoc)."'";
					$sql .= ' WHERE rowid = '.((int) $object->id);
					if ($db->query($sql)) {
						$object->last_main_doc = $newlastmaindoc;
					} else {
						dol_syslog(__METHOD__.' failed to update last_main_doc from '.$object->last_main_doc.' to '.$newlastmaindoc.': '.$db->lasterror(), LOG_WARNING);
						$error++;
					}
				}
			}
		}
	}

	$remaining = scandir(dol_osencode($flatdir));
	if (is_array($remaining) && count(array_diff($remaining, array('.', '..'))) === 0) {
		@rmdir(dol_osencode($flatdir));
	}

	return $error ? -1 : $moved;
}

/**
 * Tell if a legacy file must be hidden from attached document lists.
 *
 * @param string $filename File name or path
 * @return bool            True when the file is technical
 */
function diffusionIsIgnoredLegacyDocumentFile($filename)
{
	$filename = str_replace('\\', '/', (string) $filename);
	$basename = basename($filename);

	return $basename === ''
		|| $basename === '.'
		|| $basename === '..'
		|| preg_match('/(^|\/)thumbs(\/|$)/i', $filename)
		|| preg_match('/\.meta$/i', $basename)
		|| preg_match('/\.tmp$/i', $basename)
		|| preg_match('/_preview.*\.png$/i', $basename)
		|| preg_match('/\.preview\.png$/i', $basename);
}

/**
 * Return real legacy files that should be shown as a fallback.
 *
 * @param Diffusion $object     Diffusion object
 * @param string    $upload_dir Current absolute upload directory
 * @param string    $sortfield  Sort field for dol_dir_list
 * @param int       $sortorder  Sort order for dol_dir_list
 * @return array<int,array<string,mixed>> File array compatible with FormFile::list_of_documents()
 */
function diffusionGetLegacyDocumentFileArray($object, $upload_dir = '', $sortfield = 'name', $sortorder = SORT_ASC)
{
	if (!is_object($object) || empty($object->ref)) {
		return array();
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	if (empty($upload_dir)) {
		$upload_dir = diffusionGetDocumentUploadDir($object);
	}
	if (empty($upload_dir)) {
		return array();
	}

	$upload_dir = rtrim((string) $upload_dir, '/\\');
	$legacy_upload_dir = diffusionGetLegacyDocumentUploadDir($object, $upload_dir);
	if (empty($legacy_upload_dir) || !is_dir(dol_osencode($legacy_upload_dir))) {
		return array();
	}

	$ignoredpattern = '(\.meta$|\.tmp$|_preview.*\.png$|\.preview\.png$)';
	$currentbasenames = array();
	if (is_dir(dol_osencode($upload_dir))) {
		$currentfilearray = dol_dir_list($upload_dir, 'files', 0, '', $ignoredpattern, 'name', SORT_ASC, 1);
		foreach ($currentfilearray as $fileinfo) {
			if (empty($fileinfo['name']) || diffusionIsIgnoredLegacyDocumentFile($fileinfo['name'])) {
				continue;
			}
			$currentbasenames[strtolower((string) $fileinfo['name'])] = true;
		}
	}

	$legacyfilearray = dol_dir_list($legacy_upload_dir, 'files', 0, '', $ignoredpattern, $sortfield, $sortorder, 1);
	$filtered = array();
	foreach ($legacyfilearray as $fileinfo) {
		$name = !empty($fileinfo['name']) ? (string) $fileinfo['name'] : basename((string) $fileinfo['fullname']);
		if (diffusionIsIgnoredLegacyDocumentFile($name)) {
			continue;
		}
		if (!empty($currentbasenames[strtolower($name)])) {
			continue;
		}
		$filtered[] = $fileinfo;
	}

	return $filtered;
}

/**
 * Print the native fallback list for legacy Diffusion documents.
 *
 * @param FormFile  $formfile        FormFile helper
 * @param Diffusion $object          Diffusion object
 * @param string    $upload_dir      Current absolute upload directory
 * @param string    $modulepart      Dolibarr document modulepart
 * @param string    $param           URL parameters
 * @param int|bool  $permissiontoadd Write permission
 * @param string    $sortfield       Sort field
 * @param string    $sortorder       Sort order
 * @return int                       Number of shown legacy files
 */
function diffusionPrintLegacyDocumentList($formfile, $object, $upload_dir, $modulepart, $param = '', $permissiontoadd = 0, $sortfield = 'name', $sortorder = 'ASC')
{
	global $langs;

	if (!is_object($formfile)) {
		return 0;
	}

	$legacy_upload_dir = diffusionGetLegacyDocumentUploadDir($object, $upload_dir);
	$legacy_relativepath = diffusionGetLegacyDocumentRelativePath($object);
	$legacyfilearray = diffusionGetLegacyDocumentFileArray($object, $upload_dir, $sortfield, (strtolower((string) $sortorder) == 'desc' ? SORT_DESC : SORT_ASC));
	if (empty($legacyfilearray) || empty($legacy_upload_dir) || empty($legacy_relativepath)) {
		return 0;
	}

	$formfile->list_of_documents(
		$legacyfilearray,
		$object,
		$modulepart,
		$param,
		0,
		$legacy_relativepath,
		$permissiontoadd,
		0,
		'',
		0,
		$langs->trans('DiffusionHistoricalDocuments'),
		'',
		0,
		0,
		$legacy_upload_dir,
		$sortfield,
		(strtolower((string) $sortorder) == 'desc' ? 'DESC' : 'ASC'),
		1
	);

	return count($legacyfilearray);
}

/**
 * Tell if a changed file is the generated diffusion document or one of its own preview files.
 *
 * @param Diffusion $object   Diffusion object
 * @param string    $filename File name or path
 * @return bool               True if this file must not trigger automatic regeneration
 */
function diffusionIsGeneratedDocumentFile($object, $filename)
{
	if (!is_object($object) || empty($object->ref) || empty($filename)) {
		return false;
	}

	$basename = basename(str_replace('\\', '/', (string) $filename));
	$objectref = dol_sanitizeFileName($object->ref);
	$mainpdf = $objectref.'.pdf';
	if (!empty($object->last_main_doc) && preg_match('/\.pdf$/i', (string) $object->last_main_doc)) {
		$mainpdf = basename(str_replace('\\', '/', (string) $object->last_main_doc));
	}

	return in_array($basename, array($mainpdf, $mainpdf.'_preview.png', $objectref.'.pdf', $objectref.'.pdf_preview.png'), true)
		|| preg_match('/_preview.*\.png$/i', $basename)
		|| preg_match('/\.meta$/i', $basename);
}

/**
 * Return the left banner HTML showing the generated PDF preview.
 *
 * @param Diffusion $object     Diffusion object
 * @param string    $upload_dir Absolute upload directory
 * @return string               HTML preview or empty string
 */
function diffusionGetGeneratedDocumentPreviewHtml($object, $upload_dir = '')
{
	global $conf, $db;

	if (!is_object($object) || empty($object->id) || empty($object->ref) || !empty($object->is_template)) {
		return '';
	}
	if (getDolGlobalString('MAIN_DISABLE_PDF_THUMBS')) {
		return '';
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	if (is_object($db)) {
		diffusionMigrateFlatDocumentDirectory($db, $object);
	}

	if (empty($upload_dir)) {
		$upload_dir = diffusionGetDocumentUploadDir($object);
	}
	if (empty($upload_dir)) {
		return '';
	}
	$upload_dir = rtrim((string) $upload_dir, '/\\');
	$objectref = dol_sanitizeFileName($object->ref);

	$pdfdir = $upload_dir;
	$pdfrelativebase = diffusionGetDocumentRelativePath($object);
	$pdfbasename = $objectref.'.pdf';
	if (!empty($object->last_main_doc) && preg_match('/\.pdf$/i', (string) $object->last_main_doc)) {
		$candidate = basename(str_replace('\\', '/', (string) $object->last_main_doc));
		if (is_file($pdfdir.'/'.$candidate)) {
			$pdfbasename = $candidate;
		}
	}

	$filepdf = $pdfdir.'/'.$pdfbasename;
	if (!is_file($filepdf)) {
		$pdfbasename = $objectref.'.pdf';
		$filepdf = $pdfdir.'/'.$pdfbasename;
	}
	if (!is_file($filepdf)) {
		return '';
	}

	$fileimage = $filepdf.'_preview.png';
	if (!is_file($fileimage) || filemtime($fileimage) < filemtime($filepdf)) {
		if (!class_exists('Imagick')) {
			return '';
		}
		$ret = dol_convert_file($filepdf, 'png', $fileimage, '0');
		if ($ret < 0 || !is_file($fileimage)) {
			return '';
		}
	}

	$heightforphotref = !empty($conf->dol_optimize_smallscreen) ? 60 : 80;
	$relativepathimage = $pdfrelativebase.$pdfbasename.'_preview.png';
	$entity = !empty($object->entity) ? (int) $object->entity : (int) $conf->entity;
	$url = DOL_URL_ROOT.'/viewimage.php?modulepart=diffusion&amp;entity='.$entity.'&amp;file='.urlencode($relativepathimage).'&amp;cache='.(int) filemtime($fileimage);

	return '<div class="floatleft inline-block valignmiddle divphotoref diffusion-pdf-preview"><div class="photoref"><img height="'.$heightforphotref.'" class="photo photowithborder" src="'.$url.'" alt="'.dol_escape_htmltag($object->ref).'"></div></div>';
}

/**
 * Print the native object banner for all Diffusion tabs.
 *
 * @param Diffusion $object          Diffusion object
 * @param Form      $form            Form helper
 * @param string    $linkback        Back link
 * @param int|bool  $permissiontoadd Write permission
 * @param string    $action          Current action
 * @return void
 */
function diffusionPrintObjectBanner($object, $form, $linkback, $permissiontoadd, $action = '')
{
	global $db, $conf, $langs;

	$iscardpage = (basename((string) $_SERVER['PHP_SELF']) === 'diffusion_card.php');
	$inlineEditable = ($iscardpage && !empty($permissiontoadd) && isset($object->status) && $object->status == $object::STATUS_DRAFT);
	$inlineEditableRef = ($iscardpage && !empty($permissiontoadd) && !empty($object->is_template));

	$morehtmlref = '<div class="refidno">';
	if (!empty($object->is_template)) {
		$morehtmlref .= $form->editfieldkey($langs->transnoentitiesnoconv('Ref'), 'ref', '', $object, $inlineEditableRef, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval($langs->transnoentitiesnoconv('Ref'), 'ref', $object->ref, $object, $inlineEditableRef, 'string', '', null, null, '', 1);
	}
	if (isset($object->fields['label'])) {
		if (!empty($object->is_template)) {
			$morehtmlref .= '<br>';
		}
		$morehtmlref .= $form->editfieldkey($object->fields['label']['label'], 'label', '', $object, $inlineEditable, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval($object->fields['label']['label'], 'label', $object->label, $object, $inlineEditable, 'string', '', null, null, '', 1);
	}
	if (isModEnabled('project')) {
		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
		$langs->load("projects");
		$morehtmlref .= '<br>';
		if (!empty($permissiontoadd) && $iscardpage) {
			$socidforproject = GETPOSTINT('socid');
			$socidforproject = ($socidforproject > 0 ? $socidforproject : -1);
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $socidforproject, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
		} elseif (!empty($object->fk_project)) {
			$proj = new Project($db);
			$proj->fetch($object->fk_project);
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"').$proj->getNomUrl(0);
			if (!empty($proj->title)) {
				$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
			}
		}
	}
	if (empty($object->is_template)) {
		require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

		$morehtmlref .= '<br>';
		$morehtmlref .= img_picto($langs->trans("DateEnvoi"), 'calendar', 'class="pictofixedwidth"').$langs->trans("DateEnvoi").' : ';
		$morehtmlref .= (!empty($object->date_expedition) ? dol_print_date($object->date_expedition, 'dayhour') : '<span class="opacitymedium">'.$langs->trans("None").'</span>');

		$morehtmlref .= '<br>';
		$morehtmlref .= img_picto($langs->trans("UserExpedition"), 'user', 'class="pictofixedwidth"').$langs->trans("UserExpedition").' : ';
		if (!empty($object->fk_user_exped)) {
			$userexped = new User($db);
			if ($userexped->fetch((int) $object->fk_user_exped) > 0) {
				$morehtmlref .= $userexped->getNomUrl(-1);
			} else {
				$morehtmlref .= '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>';
			}
		} else {
			$morehtmlref .= '<span class="opacitymedium">'.$langs->trans("None").'</span>';
		}
	}

	if (isModEnabled('multicompany') && !empty($object->entity) && (int) $object->entity !== (int) $conf->entity) {
		$entitylabel = (string) $object->entity;
		$sqlentity = 'SELECT label FROM '.MAIN_DB_PREFIX.'entity WHERE rowid = '.((int) $object->entity);
		$resqlentity = $db->query($sqlentity);
		if ($resqlentity) {
			$objentity = $db->fetch_object($resqlentity);
			if ($objentity && isset($objentity->label) && $objentity->label !== '') {
				$entitylabel = $objentity->label;
			}
		}

		$morehtmlref .= '<br>';
		$morehtmlref .= '<div class="refidno multicompany-entity-card-container"><span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.dol_escape_htmltag($entitylabel).'</span></div>';
	}
	$morehtmlref .= '</div>';

	$morehtmlstatus = (!empty($object->is_template) ? '&nbsp;' : '');
	$fieldrefbanner = (!empty($object->is_template) ? '' : 'ref');
	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', $fieldrefbanner, $morehtmlref, '', 0, '', $morehtmlstatus);
}

/**
 * Regenerate the generated diffusion document after a linked file change.
 *
 * @param DoliDB       $db         Database handler
 * @param Diffusion    $object     Diffusion object
 * @param string       $upload_dir Absolute upload directory
 * @param User         $user       Current user
 * @param Translate    $langs      Translation handler
 * @param string       $reason     Reason for logs
 * @param array<int,string>|string $filenames Optional list of uploaded file names
 * @return int<-1,1>               1 if OK, -1 if a warning was raised
 */
function diffusionRegenerateDocumentAfterLinkedFileChange($db, $object, $upload_dir, $user, $langs, $reason = '', $filenames = array())
{
	if (empty($object->id) || !empty($object->is_template)) {
		return 1;
	}

	$error = 0;

	$shareresult = diffusionEnsureAttachedFilesIndexedAndShared($db, $object, $upload_dir, $user, $filenames);
	if ($shareresult < 0) {
		$error++;
		$message = $db->lasterror() ?: $langs->trans('DiffusionDocumentRegenerationAfterFileChangeFailed');
		$object->errors[] = $message;
		setEventMessages($message, null, 'warnings');
	}

	$object->fetch((int) $object->id);
	dol_syslog(__METHOD__.' regenerate diffusion document after linked file change id='.(int) $object->id.($reason ? ' reason='.$reason : ''), LOG_DEBUG);
	$result = $object->generateDocument('', $langs);
	if ($result < 0) {
		$error++;
		$message = !empty($object->error) ? $object->error : $langs->trans('DiffusionDocumentRegenerationAfterFileChangeFailed');
		setEventMessages($message, $object->errors, 'warnings');
	}

	return $error ? -1 : 1;
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
	return diffusionRegenerateDocumentAfterLinkedFileChange($db, $object, $upload_dir, $user, $langs, 'upload');
}

/**
 * Resolve a linked file path inside the diffusion document tree.
 *
 * @param Diffusion $object     Diffusion object
 * @param string    $upload_dir Absolute object upload directory
 * @param string    $filename   Submitted file name
 * @return string               Absolute path or empty string
 */
function diffusionResolveLinkedFilePath($object, $upload_dir, $filename)
{
	if (empty($upload_dir) || empty($filename)) {
		return '';
	}

	$filename = ltrim(str_replace('\\', '/', (string) $filename), '/');
	if ($filename === '' || preg_match('/(^|\/)\.\.(\/|$)/', $filename)) {
		return '';
	}

	$upload_dir = rtrim((string) $upload_dir, '/\\');
	$modulebasedir = diffusionGetDocumentBaseOutputDir($object);
	$objectref = dol_sanitizeFileName($object->ref);
	$documentelement = diffusionGetDocumentElement();
	if (preg_match('/^'.preg_quote($objectref, '/').'\//', $filename) || preg_match('/^'.preg_quote($documentelement, '/').'\//', $filename)) {
		$fullpath = $modulebasedir.'/'.$filename;
	} else {
		$fullpath = $upload_dir.'/'.basename($filename);
	}

	$basedirreal = realpath($modulebasedir);
	$parentreal = realpath(dirname($fullpath));
	if (empty($basedirreal) || empty($parentreal) || strpos($parentreal, $basedirreal) !== 0) {
		return '';
	}

	return $fullpath;
}

/**
 * Delete one linked diffusion file and regenerate the generated document when relevant.
 *
 * @param DoliDB       $db         Database handler
 * @param Diffusion    $object     Diffusion object
 * @param string       $upload_dir Absolute upload directory
 * @param User         $user       Current user
 * @param Translate    $langs      Translation handler
 * @param string       $filename   Submitted file name
 * @return int<-1,1>               1 if OK, -1 if KO
 */
function diffusionDeleteLinkedFileAndRegenerate($db, $object, $upload_dir, $user, $langs, $filename)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$fullpath = diffusionResolveLinkedFilePath($object, $upload_dir, $filename);
	$ret = (!empty($fullpath) ? dol_delete_file($fullpath, 0, 0, 0, $object) : 0);
	if ($ret) {
		setEventMessages($langs->trans("FileWasRemoved", basename((string) $filename)), null, 'mesgs');
		if (diffusionIsGeneratedDocumentFile($object, $filename)) {
			if (is_file($fullpath.'_preview.png')) {
				dol_delete_file($fullpath.'_preview.png');
			}
		} else {
			diffusionRegenerateDocumentAfterLinkedFileChange($db, $object, $upload_dir, $user, $langs, 'delete');
		}
		return 1;
	}

	setEventMessages($langs->trans("ErrorFailToDeleteFile", basename((string) $filename)), null, 'errors');
	return -1;
}
