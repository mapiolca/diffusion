<?php
/* Copyright (C) 2017       Laurent Destailleur     <eldy@users.sourceforge.net>
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
 *  \file       diffusion_agenda.php
 *  \ingroup    diffusion
 *  \brief      Tab of events on Diffusion
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined("MAIN_SECURITY_FORCECSP"))   define('MAIN_SECURITY_FORCECSP', 'none');	// Disable all Content Security Policies
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');				// Disable browser notification

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

require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
dol_include_once('/diffusion/class/diffusion.class.php');
dol_include_once('/diffusion/lib/diffusion_diffusion.lib.php');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("diffusion@diffusion", "other"));

// Get parameters
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')); // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$buttonsearch = (GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha'));
$buttonremovefilter = (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha'));

if (GETPOST('actioncode', 'array')) {
	$actioncode = GETPOST('actioncode', 'array', 3);
	if (!count($actioncode)) {
		$actioncode = '0';
	}
} else {
	$actioncode = GETPOST("actioncode", "alpha", 3) ? GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode") == '0' ? '0' : getDolGlobalString('AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT'));
}
$search_rowid = GETPOST('search_rowid', 'intcomma');
$search_agenda_label = GETPOST('search_agenda_label', 'alphanohtml');
$search_filtert = GETPOSTISSET('search_filtert') ? GETPOSTINT('search_filtert') : '';
$search_complete = GETPOST('search_complete', 'aZ09');
$dateevent_startyear = GETPOSTINT('dateevent_startyear');
$dateevent_startmonth = GETPOSTINT('dateevent_startmonth');
$dateevent_startday = GETPOSTINT('dateevent_startday');
$dateevent_endyear = GETPOSTINT('dateevent_endyear');
$dateevent_endmonth = GETPOSTINT('dateevent_endmonth');
$dateevent_endday = GETPOSTINT('dateevent_endday');

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
if ($buttonsearch || $buttonremovefilter) {
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield || !in_array($sortfield, array('a.id', 'a.datep,a.id', 'a.label', 'a.percent', 'c.libelle'), true)) {
	$sortfield = 'a.datep,a.id';
}
if (!$sortorder || !preg_match('/^(ASC|DESC)(,(ASC|DESC))*$/', $sortorder)) {
	$sortorder = 'DESC,DESC';
}
$sortorder = strtoupper($sortorder);

// Initialize a technical objects
$object = new Diffusion($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->diffusion->multidir_output[$conf->entity].'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array($object->element.'agenda', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
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
		$upload_dir = $conf->diffusion->multidir_output[$entityfordoc].'/'.dol_sanitizeFileName($object->ref);
	}
}

$permissiontoread = (!empty($user->admin) || $user->hasRight('diffusion', 'diffusiondoc', 'read'));
$permissiontoadd = (!empty($user->admin) || $user->hasRight('diffusion', 'diffusiondoc', 'write'));

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


/*
 *  Actions
 */

$parameters = array('id' => $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Cancel
	if (GETPOST('cancel', 'alpha') && !empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}

	// Purge search criteria
	if ($buttonremovefilter) {
		$actioncode = '';
		$search_agenda_label = '';
		$search_rowid = '';
		$search_filtert = '';
		$search_complete = '';
		$dateevent_startyear = $dateevent_startmonth = $dateevent_startday = 0;
		$dateevent_endyear = $dateevent_endmonth = $dateevent_endday = 0;
	}
}



/*
 *	View
 */

$form = new Form($db);

if ($object->id > 0) {
	$title = $langs->trans("Diffusion")." - ".$langs->trans('Agenda');
	//$title = $object->ref." - ".$langs->trans("Agenda");
	$help_url = 'EN:Module_Agenda_En|DE:Modul_Terminplanung';

	llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-diffusion page-card_agenda');

	if (isModEnabled('notification')) {
		$langs->load("mails");
	}
	$head = diffusionPrepareHead($object);


	print dol_get_fiche_head($head, 'agenda', $langs->trans("Diffusion"), -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/diffusion/diffusion_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	diffusionPrintObjectBanner($object, $form, $linkback, $permissiontoadd, $action);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	$object->info($object->id);
	dol_print_object_info($object, 1);

	print '</div>';

	print dol_get_fiche_end();



	// Actions buttons

	$objthirdparty = $object;
	$objcon = new stdClass();

	$out = '&origin='.urlencode($object->element.(property_exists($object, 'module') ? '@'.$object->module : '')).'&originid='.urlencode((string) $object->id);
	$urlbacktopage = $_SERVER['PHP_SELF'].'?id='.$object->id;
	$out .= '&backtopage='.urlencode($urlbacktopage);
	$permok = $user->hasRight('agenda', 'myactions', 'create');
	if ((!empty($objthirdparty->id) || !empty($objcon->id)) && $permok) {
		//$out.='<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create';
		if (get_class($objthirdparty) == 'Societe') {
			$out .= '&socid='.urlencode((string) $objthirdparty->id);
		}
		$out .= (!empty($objcon->id) ? '&contactid='.urlencode($objcon->id) : '');
		//$out.=$langs->trans("AddAnAction").' ';
		//$out.=img_picto($langs->trans("AddAnAction"),'filenew');
		//$out.="</a>";
	}

	$morehtmlright = '';

	//$messagingUrl = DOL_URL_ROOT.'/societe/messaging.php?socid='.$object->id;
	//$morehtmlright .= dolGetButtonTitle($langs->trans('ShowAsConversation'), '', 'fa fa-comments imgforviewmode', $messagingUrl, '', 1);
	//$messagingUrl = DOL_URL_ROOT.'/societe/agenda.php?socid='.$object->id;
	//$morehtmlright .= dolGetButtonTitle($langs->trans('MessageListViewType'), '', 'fa fa-bars imgforviewmode', $messagingUrl, '', 2);

	if (isModEnabled('agenda')) {
		if ($user->hasRight('agenda', 'myactions', 'create') || $user->hasRight('agenda', 'allactions', 'create')) {
			$morehtmlright .= dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/comm/action/card.php?action=create'.$out);
		} else {
			$morehtmlright .= dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/comm/action/card.php?action=create'.$out, '', 0);
		}
	}


	if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
		print '<br>';

		$param = '&id='.$object->id.(!empty($socid) ? '&socid='.$socid : '');
		if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
			$param .= '&contextpage='.urlencode($contextpage);
		}
		if ($limit > 0 && $limit != $conf->liste_limit) {
			$param .= '&limit='.((int) $limit);
		}
		if (is_array($actioncode)) {
			foreach ($actioncode as $code) {
				if ((string) $code !== '') {
					$param .= '&actioncode[]='.urlencode((string) $code);
				}
			}
		} elseif ((string) $actioncode !== '') {
			$param .= '&actioncode='.urlencode((string) $actioncode);
		}
		if ($search_rowid !== '') {
			$param .= '&search_rowid='.urlencode((string) $search_rowid);
		}
		if ($search_agenda_label !== '') {
			$param .= '&search_agenda_label='.urlencode($search_agenda_label);
		}
		if ($search_filtert !== '') {
			$param .= '&search_filtert='.urlencode((string) $search_filtert);
		}
		if ($search_complete !== '') {
			$param .= '&search_complete='.urlencode($search_complete);
		}
		if (!empty($dateevent_startyear) && !empty($dateevent_startmonth) && !empty($dateevent_startday)) {
			$param .= '&dateevent_startyear='.((int) $dateevent_startyear).'&dateevent_startmonth='.((int) $dateevent_startmonth).'&dateevent_startday='.((int) $dateevent_startday);
		}
		if (!empty($dateevent_endyear) && !empty($dateevent_endmonth) && !empty($dateevent_endday)) {
			$param .= '&dateevent_endyear='.((int) $dateevent_endyear).'&dateevent_endmonth='.((int) $dateevent_endmonth).'&dateevent_endday='.((int) $dateevent_endday);
		}

		$tms_start = '';
		$tms_end = '';
		if (!empty($dateevent_startyear) && !empty($dateevent_startmonth) && !empty($dateevent_startday)) {
			$tms_start = dol_mktime(0, 0, 0, $dateevent_startmonth, $dateevent_startday, $dateevent_startyear, 'tzuserrel');
		}
		if (!empty($dateevent_endyear) && !empty($dateevent_endmonth) && !empty($dateevent_endday)) {
			$tms_end = dol_mktime(23, 59, 59, $dateevent_endmonth, $dateevent_endday, $dateevent_endyear, 'tzuserrel');
		}

		$canfilteragendauser = (int) $user->hasRight('agenda', 'allactions', 'read');
		$filters = array(
			'search_agenda_label' => $search_agenda_label,
			'search_rowid' => $search_rowid,
			'search_filtert' => ($canfilteragendauser ? (string) $search_filtert : ''),
			'search_complete' => $search_complete,
		);

		$sqlselect = "SELECT DISTINCT a.id, a.label as label, a.datep as dp, a.datep2 as dp2, a.percent as percent, 'action' as type,";
		$sqlselect .= " a.fk_element, a.elementtype, a.fk_contact, a.code,";
		$sqlselect .= " c.code as acode, c.libelle as alabel, c.picto as apicto,";
		$sqlselect .= " u.rowid as user_id, u.login as user_login, u.photo as user_photo, u.firstname as user_firstname, u.lastname as user_lastname";
		$sqlfrom = " FROM ".MAIN_DB_PREFIX."actioncomm as a";
		$sqlfrom .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = a.fk_user_action";
		$sqlfrom .= " LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm as c ON a.fk_action = c.id";
		if (!$user->hasRight('agenda', 'allactions', 'read')) {
			$sqlfrom .= " LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_resources as ar ON ar.fk_actioncomm = a.id";
			$sqlfrom .= " AND ar.element_type = 'user' AND ar.fk_element = ".((int) $user->id);
		}
		$sqlwhere = " WHERE a.entity IN (".getEntity('agenda').")";
		$sqlwhere .= " AND a.fk_element = ".((int) $object->id);
		$sqlwhere .= " AND a.elementtype = 'diffusiondoc@diffusion'";
		if (!$user->hasRight('agenda', 'allactions', 'read')) {
			$sqlwhere .= " AND (a.fk_user_author = ".((int) $user->id)." OR a.fk_user_action = ".((int) $user->id)." OR ar.fk_element = ".((int) $user->id).")";
		}
		if (!empty($tms_start) && !empty($tms_end)) {
			$sqlwhere .= " AND ((a.datep BETWEEN '".$db->idate($tms_start)."' AND '".$db->idate($tms_end)."') OR (a.datep2 BETWEEN '".$db->idate($tms_start)."' AND '".$db->idate($tms_end)."'))";
		} elseif (empty($tms_start) && !empty($tms_end)) {
			$sqlwhere .= " AND ((a.datep <= '".$db->idate($tms_end)."') OR (a.datep2 <= '".$db->idate($tms_end)."'))";
		} elseif (!empty($tms_start) && empty($tms_end)) {
			$sqlwhere .= " AND ((a.datep >= '".$db->idate($tms_start)."') OR (a.datep2 >= '".$db->idate($tms_start)."'))";
		}
		if (is_array($actioncode) && !empty($actioncode)) {
			$tmpconditions = array();
			foreach ($actioncode as $code) {
				if ((string) $code === '-1' || (string) $code === '') {
					continue;
				}
				$tmpcondition = '';
				addEventTypeSQL($tmpcondition, (string) $code, '');
				if ($tmpcondition !== '') {
					$tmpconditions[] = trim($tmpcondition);
				}
			}
			if (!empty($tmpconditions)) {
				$sqlwhere .= ' AND ('.implode(' OR ', $tmpconditions).')';
			}
		} elseif (!empty($actioncode) && $actioncode != '-1') {
			addEventTypeSQL($sqlwhere, $actioncode);
		}
		addOtherFilterSQL($sqlwhere, '', dol_now('tzuser'), $filters);

		$nbEvent = 0;
		$sqlcount = "SELECT COUNT(DISTINCT a.id) as nb".$sqlfrom.$sqlwhere;
		$resqlcount = $db->query($sqlcount);
		if ($resqlcount) {
			$objCount = $db->fetch_object($resqlcount);
			$nbEvent = (int) $objCount->nb;
			$db->free($resqlcount);
		} else {
			dol_syslog(__METHOD__." failed to count actioncomm for diffusion id=".((int) $object->id)." error=".$db->lasterror(), LOG_ERR);
			setEventMessages($db->lasterror(), null, 'errors');
		}
		if ($page > 0 && $offset >= $nbEvent) {
			$page = 0;
			$offset = 0;
		}

		$sqllist = $sqlselect.$sqlfrom.$sqlwhere.$db->order($sortfield, $sortorder);
		if ($limit) {
			$sqllist .= $db->plimit($limit + 1, $offset);
		}
		$resql = $db->query($sqllist);
		$num = ($resql ? $db->num_rows($resql) : 0);

		$titlelist = $langs->trans("Actions").(is_numeric($nbEvent) ? '<span class="opacitymedium colorblack paddingleft">('.$nbEvent.')</span>' : '');
		if (!empty($conf->dol_optimize_smallscreen)) {
			$titlelist = $langs->trans("Actions").(is_numeric($nbEvent) ? '<span class="opacitymedium colorblack paddingleft">('.$nbEvent.')</span>' : '');
		}

		print_barre_liste($titlelist, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbEvent, 'object_action', 0, $morehtmlright, '', $limit, 0, 0, 1);

		if (!$resql) {
			dol_print_error($db);
		} else {
			$formactions = new FormActions($db);
			$actionstatic = new ActionComm($db);
			$userstatic = new User($db);
			$contactstatic = new Contact($db);
			$userlinkcache = array();
			$contactlinkcache = array();
			$elementlinkcache = array();
			$caction = new CActionComm($db);
			$arraylist = $caction->liste_array(1, 'code', '', (getDolGlobalString('AGENDA_USE_EVENT_TYPE') ? 0 : 1), '', 1);
			$percent = $search_complete !== '' ? $search_complete : -1;
			if ((string) $search_complete == '0') {
				$percent = '0';
			} elseif ((int) $search_complete == 100) {
				$percent = '100';
			}

			print '<form name="listactionsfilter" class="listactionsfilter" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="id" value="'.((int) $object->id).'">';
			print '<input type="hidden" name="sortfield" value="'.dol_escape_htmltag($sortfield).'">';
			print '<input type="hidden" name="sortorder" value="'.dol_escape_htmltag($sortorder).'">';
			print '<input type="hidden" name="limit" value="'.((int) $limit).'">';
			if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
				print '<input type="hidden" name="contextpage" value="'.dol_escape_htmltag($contextpage).'">';
			}
			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">';

			print '<tr class="liste_titre_filter">';
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<th class="liste_titre width50 middle">';
				print $form->showFilterAndCheckAddButtons(0, 'checkforselect', 1);
				print '</th>';
			}
			print '<td class="liste_titre"><input type="text" class="width50" name="search_rowid" value="'.dol_escape_htmltag((string) $search_rowid).'"></td>';
			print '<td class="liste_titre center">';
			print $form->selectDateToDate($tms_start, $tms_end, 'dateevent', 1);
			print '</td>';
			print '<td class="liste_titre">';
			print $form->select_dolusers(($canfilteragendauser && $search_filtert !== '' ? $search_filtert : (!$canfilteragendauser ? $user->id : '')), 'search_filtert', 1, null, (int) !$canfilteragendauser, '', '', '0', 0, 0, '', 2, '', 'minwidth100 maxwidth250 widthcentpercentminusx');
			print '</td>';
			print '<td class="liste_titre">';
			print $formactions->select_type_actions($actioncode, 'actioncode', '', getDolGlobalString('AGENDA_USE_EVENT_TYPE') ? -1 : 1, 0, (getDolGlobalString('AGENDA_USE_MULTISELECT_TYPE') ? 1 : 0), 1, 'selecttype combolargeelem minwidth100 maxwidth150', 1);
			print '</td>';
			print '<td class="liste_titre maxwidth100onsmartphone"><input type="text" class="maxwidth125" name="search_agenda_label" value="'.dol_escape_htmltag($search_agenda_label).'"></td>';
			print '<td class="liste_titre"></td>';
			print '<td class="liste_titre"></td>';
			print '<td class="liste_titre parentonrightofpage">';
			print $formactions->form_select_status_action('formaction', $percent, 1, 'search_complete', 1, 2, 'search_status width100 onrightofpage', 1);
			print '</td>';
			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="liste_titre center">';
				print $form->showFilterAndCheckAddButtons(0, 'checkforselect', 1);
				print '</td>';
			}
			print '</tr>';

			print '<tr class="liste_titre">';
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print getTitleFieldOfList('', 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'maxwidthsearch ');
			}
			print getTitleFieldOfList('Ref', 0, $_SERVER["PHP_SELF"], 'a.id', '', $param, '', $sortfield, $sortorder);
			print getTitleFieldOfList('Date', 0, $_SERVER["PHP_SELF"], 'a.datep,a.id', '', $param, '', $sortfield, $sortorder, 'center ');
			print getTitleFieldOfList('Owner');
			print getTitleFieldOfList('Type', 0, $_SERVER["PHP_SELF"], 'c.libelle', '', $param, '', $sortfield, $sortorder);
			print getTitleFieldOfList('Title', 0, $_SERVER["PHP_SELF"], 'a.label', '', $param, '', $sortfield, $sortorder);
			print getTitleFieldOfList('ActionOnContact', 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'tdoverflowmax125 ', 0, '', 0);
			print getTitleFieldOfList('LinkedObject', 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
			print getTitleFieldOfList('Status', 0, $_SERVER["PHP_SELF"], 'a.percent', '', $param, '', $sortfield, $sortorder, 'center ');
			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print getTitleFieldOfList('', 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'maxwidthsearch ');
			}
			print '</tr>';

			$i = 0;
			$imaxinloop = ($limit ? min($num, $limit) : $num);
			while ($i < $imaxinloop) {
				$obj = $db->fetch_object($resql);
				if (empty($obj)) {
					break;
				}

				$actionstatic = new ActionComm($db);
				$actionstatic->fetch($obj->id);
				$actionstatic->id = (int) $obj->id;
				$actionstatic->ref = (string) $obj->id;
				$actionstatic->label = $obj->label;
				$actionstatic->datep = $db->jdate($obj->dp);
				$actionstatic->datef = $db->jdate($obj->dp2);
				$actionstatic->percentage = (int) $obj->percent;
				$actionstatic->code = $obj->code;
				$actionstatic->type_code = $obj->acode;
				$actionstatic->type_label = $obj->alabel;
				$actionstatic->type_picto = $obj->apicto;
				$actionstatic->fetchResources();

				print '<tr class="oddeven">';
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td></td>';
				}

				print '<td class="nowraponall">'.$actionstatic->getNomUrl(1, -1).'</td>';

				print '<td class="center nowraponall nopaddingtopimp nopaddingbottomimp">';
				$tmpa = dol_getdate($actionstatic->datep);
				$tmpb = !empty($actionstatic->datef) ? dol_getdate($actionstatic->datef) : $tmpa;
				if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year']) {
					print '<div class="center inline-block lineheightsmall">';
					print dol_print_date($actionstatic->datep, 'dayreduceformat', 'tzuserrel');
					print '<br><span class="opacitymedium hourspan">';
					print dol_print_date($actionstatic->datep, 'hourreduceformat', 'tzuserrel');
					if (!empty($actionstatic->datef) && ($tmpa['hours'] != $tmpb['hours'] || $tmpa['minutes'] != $tmpb['minutes'])) {
						print '-'.dol_print_date($actionstatic->datef, 'hourreduceformat', 'tzuserrel');
					}
					print '</span></div>';
				} else {
					print '<div class="center inline-block lineheightsmall">';
					print dol_print_date($actionstatic->datep, 'dayreduceformat', 'tzuserrel');
					print '<br><span class="opacitymedium hourspan">'.dol_print_date($actionstatic->datep, 'hourreduceformat', 'tzuserrel').'</span>';
					print '</div> - <div class="center inline-block lineheightsmall">';
					print dol_print_date($actionstatic->datef, 'dayreduceformat', 'tzuserrel');
					print '<br><span class="opacitymedium hourspan">'.dol_print_date($actionstatic->datef, 'hourreduceformat', 'tzuserrel').'</span>';
					print '</div>';
				}
				if ($actionstatic->hasDelay() && $actionstatic->percentage >= 0 && $actionstatic->percentage < 100) {
					print img_warning($langs->trans("Late")).' ';
				}
				print '</td>';

				print '<td class="tdoverflowmax125">';
				if ($obj->user_id > 0) {
					if (!isset($userlinkcache[$obj->user_id])) {
						$userstatic = new User($db);
						if ($userstatic->fetch($obj->user_id) > 0) {
							$userlinkcache[$obj->user_id] = $userstatic->getNomUrl(-1, '', 0, 0, 16, 0, 'firstelselast', '');
						} else {
							$userlinkcache[$obj->user_id] = '';
						}
					}
					print $userlinkcache[$obj->user_id];
				}
				print '</td>';

				$labeltype = $actionstatic->type_code;
				if (!getDolGlobalString('AGENDA_USE_EVENT_TYPE') && empty($arraylist[$labeltype])) {
					$labeltype = 'AC_OTH';
				}
				if (!empty($actionstatic->code) && preg_match('/^TICKET_MSG/', $actionstatic->code)) {
					$labeltype = $langs->trans("Message");
				} else {
					if (!empty($arraylist[$labeltype])) {
						$labeltype = $arraylist[$labeltype];
					}
					if ($actionstatic->type_code == 'AC_OTH_AUTO' && ($actionstatic->type_code != $actionstatic->code) && $labeltype && !empty($arraylist[$actionstatic->code])) {
						$labeltype .= ' - '.$arraylist[$actionstatic->code];
					}
				}
				print '<td class="tdoverflowmax125" title="'.dol_escape_htmltag($labeltype).'">';
				print $actionstatic->getTypePicto().$labeltype;
				print '</td>';

				print '<td class="tdoverflowmax300" title="'.dol_escape_htmltag($actionstatic->label).'">';
				print dol_trunc($actionstatic->label, 120);
				print '</td>';

				print '<td class="valignmiddle">';
				if (!empty($actionstatic->socpeopleassigned) && is_array($actionstatic->socpeopleassigned)) {
					foreach ($actionstatic->socpeopleassigned as $cid => $cvalue) {
						$contactid = is_array($cvalue) && !empty($cvalue['id']) ? (int) $cvalue['id'] : (int) $cid;
						if (empty($contactid) && is_numeric($cvalue)) {
							$contactid = (int) $cvalue;
						}
						if ($contactid <= 0) {
							continue;
						}
						if (!isset($contactlinkcache[$contactid])) {
							$contactstatic = new Contact($db);
							$contactlinkcache[$contactid] = ($contactstatic->fetch($contactid) > 0 ? $contactstatic->getNomUrl(-2, '', 0, '', -1, 0, 'paddingright') : '');
						}
						print $contactlinkcache[$contactid];
					}
				}
				print '</td>';

				print '<td class="tdoverflowmax200 nowraponall">';
				if (!empty($obj->elementtype) && !empty($obj->fk_element)) {
					if (!isset($elementlinkcache[$obj->elementtype])) {
						$elementlinkcache[$obj->elementtype] = array();
					}
					if (!isset($elementlinkcache[$obj->elementtype][$obj->fk_element])) {
						$elementlinkcache[$obj->elementtype][$obj->fk_element] = dolGetElementUrl((int) $obj->fk_element, $obj->elementtype, 1);
					}
					print $elementlinkcache[$obj->elementtype][$obj->fk_element];
				}
				print '</td>';

				print '<td class="nowrap center">'.$actionstatic->LibStatut((int) $obj->percent, 2, 0, $actionstatic->datep).'</td>';

				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td></td>';
				}
				print '</tr>';

				$i++;
			}
			if ($num == 0) {
				print '<tr class="oddeven"><td colspan="9"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
			}

			print '</table>';
			print '</div>';
			print '</form>';

			$db->free($resql);
		}
	}
}

// End of page
llxFooter();
$db->close();
