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
 * Return the document directory of a diffusion for its owning entity.
 *
 * @param Diffusion $object Diffusion object
 * @return string           Absolute document directory
 */
function diffusionGetDocumentUploadDir($object)
{
	global $conf;

	if (!is_object($object) || empty($object->ref)) {
		return '';
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$entityfordoc = !empty($object->entity) ? (int) $object->entity : (int) $conf->entity;
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
	if (empty($upload_dir) || strpos((string) $upload_dir, 'error-diroutput-not-defined') === 0) {
		$upload_dir = $conf->diffusion->multidir_output[$entityfordoc].'/'.dol_sanitizeFileName($object->ref);
	}

	return rtrim((string) $upload_dir, '/\\');
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
	global $conf;

	if (!is_object($object) || empty($object->id) || empty($object->ref) || !empty($object->is_template)) {
		return '';
	}
	if (getDolGlobalString('MAIN_DISABLE_PDF_THUMBS')) {
		return '';
	}
	if (!class_exists('Imagick')) {
		return '';
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	if (empty($upload_dir)) {
		$upload_dir = diffusionGetDocumentUploadDir($object);
	}
	if (empty($upload_dir)) {
		return '';
	}
	$upload_dir = rtrim((string) $upload_dir, '/\\');
	$objectref = dol_sanitizeFileName($object->ref);

	$pdfbasename = $objectref.'.pdf';
	if (!empty($object->last_main_doc) && preg_match('/\.pdf$/i', (string) $object->last_main_doc)) {
		$candidate = basename(str_replace('\\', '/', (string) $object->last_main_doc));
		if (is_file($upload_dir.'/'.$candidate)) {
			$pdfbasename = $candidate;
		}
	}

	$filepdf = $upload_dir.'/'.$pdfbasename;
	if (!is_file($filepdf)) {
		$pdfbasename = $objectref.'.pdf';
		$filepdf = $upload_dir.'/'.$pdfbasename;
	}
	if (!is_file($filepdf)) {
		return '';
	}

	$fileimage = $filepdf.'_preview.png';
	if (!is_file($fileimage) || filemtime($fileimage) < filemtime($filepdf)) {
		$ret = dol_convert_file($filepdf, 'png', $fileimage, '0');
		if ($ret < 0 || !is_file($fileimage)) {
			return '';
		}
	}

	$heightforphotref = !empty($conf->dol_optimize_smallscreen) ? 60 : 80;
	$relativepathimage = $objectref.'/'.$pdfbasename.'_preview.png';
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
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"').$proj->getNomUrl(1);
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
 * @return int<-1,1>               1 if OK, -1 if a warning was raised
 */
function diffusionRegenerateDocumentAfterLinkedFileChange($db, $object, $upload_dir, $user, $langs, $reason = '')
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
	$modulebasedir = dirname($upload_dir);
	$objectref = dol_sanitizeFileName($object->ref);
	if (preg_match('/^'.preg_quote($objectref, '/').'\//', $filename) || preg_match('/^'.preg_quote((string) $object->element, '/').'\//', $filename)) {
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
