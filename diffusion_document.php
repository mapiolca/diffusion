<?php
/* Copyright (C) 2007-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2025-2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
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
 *  \file       diffusion_document.php
 *  \ingroup    diffusion
 *  \brief      Tab for documents linked to Diffusion
 */

// General defined Options
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');					// Force use of CSRF protection with tokens even for GET
//if (! defined('MAIN_AUTHENTICATION_MODE')) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined('MAIN_LANG_DEFAULT'))        define('MAIN_LANG_DEFAULT', 'auto');					// Force LANG (language) to a particular value
//if (! defined('MAIN_SECURITY_FORCECSP'))   define('MAIN_SECURITY_FORCECSP', 'none');				// Disable all Content Security Policies
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');					// Disable browser notification
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');						// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined('NOLOGIN'))                  define('NOLOGIN', '1');						// Do not use login - if this page is public (can be called outside logged session). This includes the NOIPCHECK too.
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  		// Do not load ajax.lib.php library
//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');					// Do not create database handler $db
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');					// Do not load html.form.class.php
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');					// Do not load and show top and left menu
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');					// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');					// Do not load object $langs
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');					// Do not load object $user
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');			// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');			// Do not check injection attack on POST parameters
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');					// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');					// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)


// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
dol_include_once('/diffusion/class/diffusion.class.php');
dol_include_once('/diffusion/lib/diffusion_diffusion.lib.php');
dol_include_once('/diffusion/core/modules/diffusion/modules_diffusion.php');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("diffusion@diffusion", "companies", "other", "mails"));

// Get parameters
$action  = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm');
$id  = (GETPOSTINT('socid') ? GETPOSTINT('socid') : GETPOSTINT('id'));
$ref = GETPOST('ref', 'alpha');

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "name";
}
//if (! $sortfield) $sortfield="position_name";

// Initialize a technical objects
$object = new Diffusion($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->diffusion->multidir_output[$conf->entity].'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array($object->element.'document', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals

$fk_element = !empty($object->fk_element) ? $object->fk_element : (!empty($object->element) ? 'fk_'.$object->element : 'fk_element');
if (empty($object->fk_element) && !empty($object->element)) {
	$object->fk_element = $fk_element;
}

if ($id > 0 || !empty($ref)) {
	$entityfordoc = !empty($object->entity) ? (int) $object->entity : 1;
	if (!isset($conf->diffusion) || !is_object($conf->diffusion)) {
		$conf->diffusion = new stdClass();
	}
	if (empty($conf->diffusion->multidir_output) || !is_array($conf->diffusion->multidir_output)) {
		$conf->diffusion->multidir_output = array();
	}
	$defaultdiffusionoutput = DOL_DATA_ROOT.($entityfordoc > 1 ? '/'.$entityfordoc : '').'/diffusion';
	$diffusionoutput = !empty($conf->diffusion->multidir_output[$entityfordoc]) ? $conf->diffusion->multidir_output[$entityfordoc] : (!empty($conf->diffusion->dir_output) ? $conf->diffusion->dir_output : $defaultdiffusionoutput);
	if ($entityfordoc > 1 && preg_match('/\/'.preg_quote((string) $entityfordoc, '/').'\//', (string) $diffusionoutput) === 0) {
		$diffusionoutput = $defaultdiffusionoutput;
	}
	$conf->diffusion->multidir_output[$entityfordoc] = $diffusionoutput;
	if (!isset($conf->diffusion->enabled)) {
		$conf->diffusion->enabled = 1;
	}

	$objref = dol_sanitizeFileName($object->ref);
	$upload_dir = $diffusionoutput.'/'.$object->element.'/'.$objref;
	dol_syslog(__METHOD__.' upload_dir entity='.(int) $entityfordoc.' diffusionoutput='.$diffusionoutput.' upload_dir='.$upload_dir, LOG_DEBUG);
}

// Permissions
// (There are several ways to check permission.)
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
$enablepermissioncheck = getDolGlobalInt('DIFFUSION_ENABLE_PERMISSION_CHECK');
if ($enablepermissioncheck) {
	$permissiontoread = (!empty($user->admin) || $user->hasRight('diffusion', 'diffusion', 'read') || $user->hasRight('diffusion', 'diffusion', 'read') || $user->hasRight('diffusion', 'read') || $user->hasRight('diffusion', 'read'));
	$permissiontoadd  = (!empty($user->admin) || $user->hasRight('diffusion', 'diffusion', 'write') || $user->hasRight('diffusion', 'diffusion', 'write') || $user->hasRight('diffusion', 'write') || $user->hasRight('diffusion', 'write')); // Used by the include of actions_addupdatedelete.inc.php and actions_linkedfiles.inc.php
} else {
	$permissiontoread = 1;
	$permissiontoadd  = 1;
}

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->module, $object->id, $object->table_element, $object->element, 'fk_soc', 'rowid', $isdraft);
if (!isModEnabled("diffusion")) {
	accessforbidden();
}
if (!$permissiontoread) {
	accessforbidden();
}
if (empty($object->id)) {
	accessforbidden();
}



/*
 * Actions
 */

if ($action == 'remove_file' && !empty($permissiontoadd) && $object->id > 0) {
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	$filetodelete = GETPOST('file', 'alpha');
	$filetodelete = ltrim((string) $filetodelete, '/');
	$fullpathtodelete = '';
	if ($filetodelete !== '' && preg_match('/\.\./', $filetodelete)) {
		$fullpathtodelete = '';
	} elseif (preg_match('/^'.preg_quote($object->element, '/').'\//', $filetodelete)) {
		$fullpathtodelete = $diffusionoutput.'/'.$filetodelete;
	} else {
		$fullpathtodelete = $upload_dir.'/'.basename($filetodelete);
	}
	dol_syslog(__METHOD__.' remove_file entity='.(int) $entityfordoc.' file_param='.$filetodelete.' fullpath='.$fullpathtodelete, LOG_DEBUG);
	$ret = (!empty($fullpathtodelete) ? dol_delete_file($fullpathtodelete, 0, 0, 0, $object) : 0);
	if ($ret) {
		setEventMessages($langs->trans("FileWasRemoved", $filetodelete), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("ErrorFailToDeleteFile", $filetodelete), null, 'errors');
	}
	$action = '';
}

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';

if (GETPOST('sendit', 'alpha') && !empty($permissiontoadd) && getDolGlobalInt('DIFFUSION_ALLOW_EXTERNAL_DOWNLOAD')) {
	$relUploadDir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $upload_dir);
	dol_syslog(__METHOD__.' sendit entity='.(int) $entityfordoc.' upload_dir='.$upload_dir.' relUploadDir='.$relUploadDir, LOG_DEBUG);
	if (!preg_match('/[\\/]temp[\\/]|[\\/]thumbs|\.meta$/', $relUploadDir)) {
		$relUploadDir = preg_replace('/[\\/]$/', '', $relUploadDir);
		$relUploadDir = preg_replace('/^[\\/]/', '', $relUploadDir);

		require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."ecm_files";
		$sql .= " WHERE src_object_type = '".$db->escape($object->table_element)."'";
		$sql .= " AND src_object_id = ".((int) $object->id);
		$sql .= " AND filepath = '".$db->escape($relUploadDir)."'";
		$sql .= " AND (share IS NULL OR share = '')";
		$resql = $db->query($sql);
		if ($resql) {
			while ($objFile = $db->fetch_object($resql)) {
				$ecmfile = new EcmFiles($db);
				if ($ecmfile->fetch((int) $objFile->rowid) > 0 && empty($ecmfile->share)) {
					$ecmfile->share = getRandomPassword(true);
					$ecmfile->update($user);
				}
			}
		}
	}
}

/*
 * View
 */

$form = new Form($db);

// Header
// ------
$title = $langs->trans("Diffusion")." - ".$langs->trans("Files");
//$title = $object->ref." - ".$langs->trans("Files");
$help_url = '';
//Example $help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-diffusion page-card_document');

// Show tabs
$head = diffusionPrepareHead($object);

print dol_get_fiche_head($head, 'document', $langs->trans("Diffusion"), -1, $object->picto);


// Build file list
$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
$totalsize = 0;
foreach ($filearray as $key => $file) {
	$totalsize += $file['size'];
}

// Object card
// ------------------------------------------------------------
$linkback = '<a href="'.dol_buildpath('/diffusion/diffusion_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<div class="refidno">';
/*
 // Ref customer
 $morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
 $morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
 // Thirdparty
 $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . (is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
 // Project
 if (isModEnabled('project')) {
 $langs->load("projects");
 $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
 if ($permissiontoadd)
 {
 if ($action != 'classify')
 //$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&token='.newToken().'&id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
 $morehtmlref.=' : ';
 if ($action == 'classify') {
 //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
 $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
 $morehtmlref.='<input type="hidden" name="action" value="classin">';
 $morehtmlref.='<input type="hidden" name="token" value="'.newToken().'">';
 $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
 $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
 $morehtmlref.='</form>';
 } else {
 $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
 }
 } else {
 if (!empty($object->fk_project)) {
 $proj = new Project($db);
 $proj->fetch($object->fk_project);
 $morehtmlref .= ': '.$proj->getNomUrl();
 } else {
 $morehtmlref .= '';
 }
 }
 }*/
$morehtmlref .= '</div>';

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';

// Number of files
print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';

// Total size
print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

print '</table>';

print '</div>';

print dol_get_fiche_end();

$modulepart = 'diffusion';
$param = '&id='.$object->id.'&entity='.(int) $entityfordoc;
//$relativepathwithnofile='diffusion/' . dol_sanitizeFileName($object->id).'/';
$relativepathwithnofile = $object->element.'/'.dol_sanitizeFileName($object->ref).'/';
dol_syslog(__METHOD__.' document_actions_post_headers entity='.(int) $entityfordoc.' relativepathwithnofile='.$relativepathwithnofile.' modulepart='.$modulepart, LOG_DEBUG);

$tmperrorreporting = error_reporting();
error_reporting($tmperrorreporting & ~E_WARNING);
include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
error_reporting($tmperrorreporting);

// End of page
llxFooter();
$db->close();
