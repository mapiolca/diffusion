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
 *    \file       diffusion_card.php
 *    \ingroup    diffusion
 *    \brief      Page to create/edit/view diffusion
 */

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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';


dol_include_once('/diffusion/class/diffusion.class.php');
dol_include_once('/diffusion/class/diffusioncontact.class.php');
dol_include_once('/diffusion/lib/diffusion_diffusion.lib.php');
dol_include_once('/diffusion/core/modules/diffusion/modules_diffusion.php');

/**
 * Resolve a contact type id compatible with current diffusion element/source.
 *
 * @param DoliDB	$db		Database handler
 * @param int		$typeid		Requested contact type id
 * @param string	$source		Contact source (internal|external)
 * @param string	$element	Object element key
 * @return int				Compatible contact type id, 0 if none
 */
function diffusionResolveContactTypeId(DoliDB $db, $typeid, $source, $element)
{
	$typeid = (int) $typeid;
	$source = ($source === 'internal' ? 'internal' : 'external');
	$element = preg_replace('/[^a-z0-9_]/i', '', (string) $element);
	$objtype = null;

	if ($typeid > 0) {
		$sql = 'SELECT rowid, code, libelle, source, element';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'c_type_contact';
		$sql .= ' WHERE rowid = '.$typeid;

		$resql = $db->query($sql);
		if ($resql) {
			$objtype = $db->fetch_object($resql);
			if (!empty($objtype) && $objtype->source === $source) {
				if ($objtype->element === $element) {
					return $typeid;
				}

				$sqlmap = 'SELECT rowid';
				$sqlmap .= ' FROM '.MAIN_DB_PREFIX.'c_type_contact';
				$sqlmap .= " WHERE source = '".$db->escape($source)."'";
				$sqlmap .= " AND element = '".$db->escape($element)."'";
				if (!empty($objtype->code)) {
					$sqlmap .= " AND code = '".$db->escape((string) $objtype->code)."'";
				}
				$sqlmap .= ' ORDER BY rowid ASC';
				$sqlmap .= ' LIMIT 1';

				$resqlmap = $db->query($sqlmap);
				if ($resqlmap) {
					$objmap = $db->fetch_object($resqlmap);
					if (!empty($objmap->rowid)) {
						return (int) $objmap->rowid;
					}
				}

				if (!empty($objtype->libelle)) {
					$sqlmap = 'SELECT rowid';
					$sqlmap .= ' FROM '.MAIN_DB_PREFIX.'c_type_contact';
					$sqlmap .= " WHERE source = '".$db->escape($source)."'";
					$sqlmap .= " AND element = '".$db->escape($element)."'";
					$sqlmap .= " AND libelle = '".$db->escape((string) $objtype->libelle)."'";
					$sqlmap .= ' ORDER BY rowid ASC';
					$sqlmap .= ' LIMIT 1';

					$resqlmap = $db->query($sqlmap);
					if ($resqlmap) {
						$objmap = $db->fetch_object($resqlmap);
						if (!empty($objmap->rowid)) {
							return (int) $objmap->rowid;
						}
					}
				}
			}
		}
	}

	$sql = 'SELECT rowid';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'c_type_contact';
	$sql .= " WHERE source = '".$db->escape($source)."'";
	$sql .= " AND element = '".$db->escape($element)."'";
	$sql .= ' ORDER BY rowid ASC';
	$sql .= ' LIMIT 1';

	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if (!empty($obj->rowid)) {
			return (int) $obj->rowid;
		}
	}

	$label = '';
	if (!empty($objtype) && !empty($objtype->libelle)) {
		$label = (string) $objtype->libelle;
	}

	return diffusionCreateContactType($db, $source, $element, $label);
}


/**
 * Create a diffusion contact type when no compatible type exists yet.
 *
 * @param DoliDB	$db		Database handler
 * @param string	$source		Contact source (internal|external)
 * @param string	$element	Object element key
 * @param string	$label		Preferred label
 * @return int				Created contact type id, 0 on failure
 */
function diffusionCreateContactType(DoliDB $db, $source, $element, $label = '')
{
	$source = ($source === 'internal' ? 'internal' : 'external');
	$element = preg_replace('/[^a-z0-9_]/i', '', (string) $element);
	$label = trim((string) $label);
	if ($label === '') {
		$label = ($source === 'internal' ? 'Intervenant diffusion' : 'Contact diffusion');
	}

	$basecode = 'DIFFUSION_'.strtoupper($source).'_AUTO';
	$code = $basecode;
	$index = 0;
	while ($index < 100) {
		$sqlcheck = 'SELECT rowid';
		$sqlcheck .= ' FROM '.MAIN_DB_PREFIX.'c_type_contact';
		$sqlcheck .= " WHERE element = '".$db->escape($element)."'";
		$sqlcheck .= " AND source = '".$db->escape($source)."'";
		$sqlcheck .= " AND code = '".$db->escape($code)."'";
		$rescheck = $db->query($sqlcheck);
		if ($rescheck && $db->num_rows($rescheck) == 0) {
			break;
		}
		$index++;
		$code = $basecode.'_'.$index;
	}

	$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'c_type_contact(';
	$sql .= 'element, source, code, libelle, active, position';
	$sql .= ') VALUES (';
	$sql .= "'".$db->escape($element)."', '".$db->escape($source)."', '".$db->escape($code)."', '".$db->escape($label)."', 1, 0";
	$sql .= ')';

	if ($db->query($sql)) {
		return (int) $db->last_insert_id(MAIN_DB_PREFIX.'c_type_contact');
	}

	return 0;
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("diffusion@diffusion", "other"));

// Get parameters
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$lineid   = GETPOSTINT('lineid');
$fromtemplateid = GETPOSTINT('fromtemplateid');

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')); // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');					// if not set, a default page will be used
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');	// if not set, $backtopage will be used
$backtopagejsfields = GETPOST('backtopagejsfields', 'alpha');
$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
$dol_openinpopup = GETPOST('dol_openinpopup', 'aZ09');


if (!empty($backtopagejsfields)) {
	$tmpbacktopagejsfields = explode(':', $backtopagejsfields);
	$dol_openinpopup = preg_replace('/[^a-z0-9_]/i', '', $tmpbacktopagejsfields[0]);
}

// Handle confirmation popup submit for project contacts import.
$hasprojectcontactsrequest = false;
if (is_array($_REQUEST)) {
	foreach ($_REQUEST as $requestkey => $requestvalue) {
		if (strpos((string) $requestkey, 'projectcontacts_') === 0 || $requestkey === 'projectcontacts' || $requestkey === 'projectcontacts[]') {
			$hasprojectcontactsrequest = true;
			break;
		}
	}
}
if (($action === 'confirm_importprojectcontacts' || $action === 'ask_import_project_contacts') && ($confirm === 'yes' || $hasprojectcontactsrequest)) {
	dol_syslog(__METHOD__.' remap action '.$action.' to importprojectcontacts (confirm='.$confirm.', hasprojectcontactsrequest='.(int) $hasprojectcontactsrequest.')', LOG_DEBUG);
	$action = 'importprojectcontacts';
}

// Initialize a technical objects
$object = new Diffusion($db);

$diffusion_project = new Project($db);

$extrafields = new ExtraFields($db);

$diroutputmassaction = $conf->diffusion->multidir_output[$conf->entity].'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array($object->element.'card', 'globalcard')); // Note that conf->hooks_modules contains array
$soc = null;

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);


$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criteria
$search_all = trim(GETPOST("search_all", 'alpha'));
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'.


$permissiontoread = (!empty($user->admin) || $user->hasRight('diffusion', 'diffusiondoc', 'read'));
$permissiontoadd = (!empty($user->admin) || $user->hasRight('diffusion', 'diffusiondoc', 'write')); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = ((!empty($user->admin) || $user->hasRight('diffusion', 'diffusiondoc', 'delete'))) || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissionnote = $permissiontoadd; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $permissiontoadd; // Used by the include of actions_dellink.inc.php


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
//include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

// EN: Manage attachment upload and deletion with Dolibarr helper to keep buttons functional.
// FR: Gère l'envoi et la suppression des pièces jointes avec l'aide Dolibarr pour garder les boutons fonctionnels.
// Delete file in doc form
	if ($action == 'remove_file' && $permissiontoadd) {
		if ($object->id > 0) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

			$langs->load("other");
			//$upload_dir = $conf->diffusion->multidir_output[isset($object->entity) ? $object->entity : 1].'/'.$object->element.'/'.$objref;
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
	}

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
$isdraft = (isset($object->status) && ($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->module, $object, $object->table_element, $object->element, 'fk_soc', 'rowid', $isdraft);
if (!isModEnabled('diffusion')) {
	accessforbidden("Module diffusion not enabled");
}
if (!$permissiontoread) {
	accessforbidden();
}

$error = 0;


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = dol_buildpath('/diffusion/diffusion_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/diffusion/diffusion_card.php', 1).'?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'DIFFUSION_DIFFUSION_MODIFY'; // Name of trigger action code to execute when we modify record

	$forbiddenactionsontemplate = array('ask_import_project_contacts', 'importprojectcontacts', 'confirm_validate', 'validate', 'clone', 'confirm_clone');
	if (!empty($object->id) && !empty($object->is_template) && in_array($action, $forbiddenactionsontemplate, true)) {
		setEventMessages($langs->trans('ErrorActionNotAllowedOnDiffusionTemplate'), null, 'errors');
		header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
		exit;
	}

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

// Action to move up and down lines of object
//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

// Action to build doc
include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOSTINT('fk_soc'), '', null, 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd) {
		dol_syslog(__METHOD__." action=classin objectid=".((int) $object->id)." requested_projectid=".GETPOSTINT('projectid'), LOG_DEBUG);
		$resultsetproject = $object->setProject(GETPOSTINT('projectid'));
		if ($resultsetproject > 0) {
			dol_syslog(__METHOD__." setProject success objectid=".((int) $object->id), LOG_DEBUG);
			$object->fetch($object->id);
			$resultsyncprojectlink = $object->syncProjectObjectLink((int) $object->fk_project);
			if ($resultsyncprojectlink < 0) {
				dol_syslog(__METHOD__." syncProjectObjectLink failed objectid=".((int) $object->id)." error=".$object->error, LOG_ERR);
				setEventMessages($object->error, $object->errors, 'errors');
			} else {
				dol_syslog(__METHOD__." syncProjectObjectLink success objectid=".((int) $object->id)." projectid=".((int) $object->fk_project), LOG_DEBUG);
			}
		} elseif ($resultsetproject < 0) {
			dol_syslog(__METHOD__." setProject failed objectid=".((int) $object->id)." error=".$object->error, LOG_ERR);
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Actions to send emails
	if ($action == 'send') {
		$receiverfrompost = GETPOST('receiver', 'array');
		if (!is_array($receiverfrompost)) {
			$receiverfrompost = array();
		}

		$receiveremails = array();
		$receiverremaining = array();
		foreach ($receiverfrompost as $receiverid) {
			if (is_numeric($receiverid)) {
				$sql = 'SELECT sp.email';
				$sql .= ' FROM '.MAIN_DB_PREFIX.'socpeople as sp';
				$sql .= ' WHERE sp.rowid = '.((int) $receiverid);
				$sql .= ' AND sp.email <> ""';
				$sql .= ' AND sp.entity IN ('.getEntity('socpeople', (int) getDolGlobalInt('DIFFUSION_FULLACCESS_CONTACT'), $object).')';

				$resql = $db->query($sql);
				if ($resql) {
					$objcontact = $db->fetch_object($resql);
					if (!empty($objcontact->email)) {
						$receiveremails[] = $objcontact->email;
					}
				}
			} else {
				$receiverremaining[] = $receiverid;
			}
		}

		if (!empty($receiveremails)) {
			$freesendto = trim((string) GETPOST('sendto', 'alphawithlgt'));
			if ($freesendto !== '') {
				$receiveremails[] = $freesendto;
			}
			$_POST['sendto'] = implode(',', array_unique($receiveremails));
			$_REQUEST['sendto'] = $_POST['sendto'];
			$_POST['receiver'] = $receiverremaining;
			$_REQUEST['receiver'] = $receiverremaining;
		}
	}

	$triggersendname = 'DIFFUSION_SENDMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_DIFFUSION_TO';
	$trackid = 'diffusion'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	if ($action == 'set_sent' && $permissiontoadd) {
		$result = $object->setSent($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
			exit;
		}
	}

	if ($action == 'convert_to_template' && $permissiontoadd) {
		if ((int) $object->id > 0 && (int) $object->status === (int) $object::STATUS_DRAFT && empty($object->is_template)) {
			$resultsettemplate = $object->setValueFrom('is_template', 1, '', null, 'int', '', $user, $triggermodname);
			if ($resultsettemplate > 0) {
				setEventMessages($langs->trans('DiffusionConvertedToTemplate'), null, 'mesgs');
				header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
				exit;
			}
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}

/*
 * Add a new contact
 */

if ($action == 'addcontact' && $permissiontoadd) {
	$contactid = (GETPOST('userid') ? GETPOSTINT('userid') : GETPOSTINT('contactid'));
	$typeid = (GETPOST('typecontact') ? GETPOST('typecontact') : GETPOST('type'));
	$contactSource = GETPOST('source', 'aZ09');
	$typeid = diffusionResolveContactTypeId($db, $typeid, $contactSource, $object->element);
	if ($typeid <= 0) {
		setEventMessages($langs->trans('ErrorNoCompatibleContactTypeForDiffusion'), null, 'errors');
		header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
		exit;
	}
	$result = $object->add_contact($contactid, $typeid, $contactSource);
        if ($result >= 0) {
                $diffusioncontactstatic = new DiffusionContact($db);
                // FR: Synchronise la table dédiée afin d'offrir au PDF toutes les métadonnées nécessaires.
                // EN: Synchronise the dedicated table so the PDF gets all required metadata.
                $syncResult = $diffusioncontactstatic->syncLink($object->id, $contactid, $contactSource, (int) $typeid);

                if ($syncResult < 0) {
                        setEventMessages($diffusioncontactstatic->error, $diffusioncontactstatic->errors, 'errors');
                } else {
                        header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
                        exit;
                }
        } else {
                if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                        $langs->load('errors');
                        // FR: Même en cas de doublon sur le lien Dolibarr, on s'assure que la table diffusion soit cohérente.
                        // EN: Even when Dolibarr reports a duplicate link, keep the diffusion table consistent.
                        $diffusioncontactstatic = new DiffusionContact($db);
                        $syncResult = $diffusioncontactstatic->syncLink($object->id, $contactid, $contactSource, (int) $typeid);
                        if ($syncResult < 0) {
                                setEventMessages($diffusioncontactstatic->error, $diffusioncontactstatic->errors, 'errors');
                        }
                        setEventMessages($langs->trans('ErrorThisContactIsAlreadyDefinedAsThisType'), null, 'errors');
                } else {
                        setEventMessages($object->error, $object->errors, 'errors');
                }
        }
} elseif ($action == 'swapstatut' && $permissiontoadd) {
	// Toggle the status of a contact
	$result = $object->swapContactStatus(GETPOSTINT('ligne'));
} elseif ($action == 'deletecontact' && $permissiontoadd) {	// Permission to add on object because this is an update of a link of object, not a deletion of data
	// Deletes a contact
	$contactid = (GETPOST('userid') ? GETPOSTINT('userid') : GETPOSTINT('contactid'));
	$typeid = (GETPOST('typecontact') ? GETPOST('typecontact') : GETPOST('type'));
		$contactSource = GETPOST('source', 'aZ09');
		$result = 0;
		if ($lineid > 0) {
			$result = $object->delete_contact($lineid);
		}

		if ($result >= 0) {
				$diffusioncontactstatic = new DiffusionContact($db);
				// FR: Nettoie également la table spécifique pour éviter des reliquats côté génération PDF.
				// EN: Also clean the specific table to avoid leftovers when generating the PDF.
                $removeResult = $diffusioncontactstatic->removeLink($object->id, $contactid, $contactSource);

                if ($removeResult < 0) {
                        setEventMessages($diffusioncontactstatic->error, $diffusioncontactstatic->errors, 'errors');
                } else {
                        header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
                        exit;
                }
		} else {
			dol_print_error($db);
		}
	} elseif ($action == 'importprojectcontacts' && $permissiontoadd) {
		dol_syslog(__METHOD__.' start importprojectcontacts for diffusion id='.(int) $object->id, LOG_DEBUG);
		if (empty($object->fk_project)) {
			dol_syslog(__METHOD__.' importprojectcontacts aborted: no linked project on diffusion id='.(int) $object->id, LOG_WARNING);
			setEventMessages($langs->trans('ErrorNoProjectLinkedToDiffusion'), null, 'errors');
		} else {
			$project = new Project($db);
			$projectresult = $project->fetch((int) $object->fk_project);
			dol_syslog(__METHOD__.' importprojectcontacts fetch project id='.(int) $object->fk_project.' result='.(int) $projectresult, LOG_DEBUG);
			if ($projectresult <= 0) {
				dol_syslog(__METHOD__.' importprojectcontacts aborted: failed to load project id='.(int) $object->fk_project, LOG_ERR);
				setEventMessages($langs->trans('ErrorFailedToLoadProject'), null, 'errors');
			} else {
				$selectedcontacts = GETPOST('projectcontacts', 'array');
				dol_syslog(__METHOD__.' importprojectcontacts selectedcontacts from projectcontacts='.count((array) $selectedcontacts), LOG_DEBUG);
				if (empty($selectedcontacts)) {
					$selectedcontacts = GETPOST('projectcontacts[]', 'array');
					dol_syslog(__METHOD__.' importprojectcontacts selectedcontacts from projectcontacts[]='.count((array) $selectedcontacts), LOG_DEBUG);
				}
				if (empty($selectedcontacts) && !empty($_REQUEST) && is_array($_REQUEST)) {
					$selectedcontacts = array();
					foreach ($_REQUEST as $postkey => $postvalue) {
						if (strpos((string) $postkey, 'projectcontacts_') === 0 && !empty($postvalue)) {
							if (strpos((string) $postvalue, ':') !== false) {
								$selectedcontacts[] = (string) $postvalue;
							} elseif (preg_match('/^projectcontacts_(internal|external)_([0-9]+)_([0-9]+)$/', (string) $postkey, $matches)) {
								$selectedcontacts[] = $matches[1].':'.$matches[2].':'.$matches[3];
							}
						}
					}
					dol_syslog(__METHOD__.' importprojectcontacts selectedcontacts from request scan='.count((array) $selectedcontacts), LOG_DEBUG);
				}
				if (empty($selectedcontacts)) {
					dol_syslog(__METHOD__.' importprojectcontacts aborted: no selected contacts after parsing request', LOG_WARNING);
					setEventMessages($langs->trans('ErrorNoContactSelectedForImport'), null, 'warnings');
				} else {
					$nbimported = 0;
					$nberrors = 0;
					$diffusioncontactstatic = new DiffusionContact($db);
					dol_syslog(__METHOD__.' importprojectcontacts processing '.count((array) $selectedcontacts).' contact(s)', LOG_DEBUG);

					foreach ((array) $selectedcontacts as $selectedcontact) {
						dol_syslog(__METHOD__.' importprojectcontacts parse selectedcontact='.$selectedcontact, LOG_DEBUG);
						$parts = explode(':', (string) $selectedcontact);
						if (count($parts) !== 3) {
							dol_syslog(__METHOD__.' importprojectcontacts skip malformed selectedcontact='.$selectedcontact, LOG_WARNING);
							continue;
						}

						$source = $parts[0];
						$contactid = (int) $parts[1];
						$typeid = (int) $parts[2];

						if (!in_array($source, array('internal', 'external'), true) || $contactid <= 0 || $typeid <= 0) {
							dol_syslog(__METHOD__.' importprojectcontacts skip invalid tuple source='.$source.' contactid='.$contactid.' typeid='.$typeid, LOG_WARNING);
							continue;
						}

						$typeid = diffusionResolveContactTypeId($db, $typeid, $source, $object->element);
						if ($typeid <= 0) {
							dol_syslog(__METHOD__.' importprojectcontacts skip: failed to build diffusion contact type for source='.$source.' contactid='.$contactid, LOG_WARNING);
							$nberrors++;
							continue;
						}

						dol_syslog(__METHOD__.' importprojectcontacts add_contact source='.$source.' contactid='.$contactid.' typeid='.$typeid, LOG_DEBUG);
						$addresult = $object->add_contact($contactid, $typeid, $source);
						if ($addresult >= 0 || $object->error === 'DB_ERROR_RECORD_ALREADY_EXISTS') {
							dol_syslog(__METHOD__.' importprojectcontacts add_contact result='.$addresult.' error='.$object->error, LOG_DEBUG);
							$syncresult = $diffusioncontactstatic->syncLink($object->id, $contactid, $source, $typeid);
							dol_syslog(__METHOD__.' importprojectcontacts syncLink result='.$syncresult.' for contactid='.$contactid.' source='.$source.' typeid='.$typeid, LOG_DEBUG);
							if ($syncresult >= 0) {
								$nbimported++;
							} else {
								$nberrors++;
							}
						} else {
							dol_syslog(__METHOD__.' importprojectcontacts add_contact failed for contactid='.$contactid.' source='.$source.' typeid='.$typeid.' error='.$object->error, LOG_ERR);
							$nberrors++;
						}
					}
					dol_syslog(__METHOD__.' importprojectcontacts summary imported='.$nbimported.' errors='.$nberrors, LOG_DEBUG);

					if ($nbimported > 0) {
						setEventMessages($langs->trans('ProjectContactsImportedCount', $nbimported), null, 'mesgs');
					}
					if ($nberrors > 0) {
						setEventMessages($langs->trans('ProjectContactsImportErrorCount', $nberrors), null, 'errors');
					}
				}
			}
		}

		header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
		exit;
} elseif ($action == 'add' && $usercancreate) {
	//$db->begin();
	$object->ref = GETPOST('ref');
	$object->label = GETPOST('label');
	$object->fk_project = GETPOSTINT('projectid');
	$object->description = GETPOST('description', 'none');

	//$id = $object->create($user, $db); 
}

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

//$title = $langs->trans("Diffusion")." - ".$langs->trans('Card');
$title = $object->ref." - ".$langs->trans('Card');
if ($action == 'create') {
	$title = $langs->trans("NewDiffusion", $langs->transnoentitiesnoconv("Diffusion"));
}
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-diffusion page-card');

// Example : Adding jquery code
// print '<script type="text/javascript">
// jQuery(document).ready(function() {
// 	function init_myfunc()
// 	{
// 		jQuery("#myid").removeAttr(\'disabled\');
// 		jQuery("#myid").attr(\'disabled\',\'disabled\');
// 	}
// 	init_myfunc();
// 	jQuery("#mybutton").click(function() {
// 		init_myfunc();
// 	});
// });
// </script>';


// Part to create
if ($action == 'create') {
	if (empty($permissiontoadd)) {
		accessforbidden('NotEnoughPermissions', 0, 1);
	}

	$templatedefaultlabel = '';
	$templatedefaultdescription = '';
	if ($fromtemplateid > 0) {
		$templateobject = new Diffusion($db);
		$resulttemplate = $templateobject->fetch($fromtemplateid);
		if ($resulttemplate > 0 && !empty($templateobject->is_template)) {
			$templatedefaultlabel = (string) $templateobject->label;
			$templatedefaultdescription = (string) $templateobject->description;
		} else {
			$fromtemplateid = 0;
		}
	}

	print load_fiche_titre($title, '', $object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="status" value="0">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}
	if ($backtopagejsfields) {
		print '<input type="hidden" name="backtopagejsfields" value="'.$backtopagejsfields.'">';
	}
	if ($dol_openinpopup) {
		print '<input type="hidden" name="dol_openinpopup" value="'.$dol_openinpopup.'">';
	}
	if ($fromtemplateid > 0) {
		print '<input type="hidden" name="fromtemplateid" value="'.$fromtemplateid.'">';
	}

	print dol_get_fiche_head(array(), '');


	print '<table class="border centpercent tableforfieldcreate">'."\n";


	// Reference
	print '<tr class="field_ref"><td class="titlefieldcreate fieldrequired">'.$langs->trans('Ref').'</td><td class="valuefieldcreate">'.$langs->trans("Draft").'</td></tr>';

	// Label
	print '<tr class="field_label"><td class="titlefieldcreate">'.$langs->trans('Label').'</td><td class="valuefieldcreate">';
	$inputlabel = GETPOST('label');
	if ($inputlabel === '' && $templatedefaultlabel !== '') {
		$inputlabel = $templatedefaultlabel;
	}
	print '<input type="text" name="label" value="'.dol_escape_htmltag($inputlabel).'"></td>';
	print '</tr>';

	// Project
	if (isModEnabled('project') && is_object($formproject)) {
		$langs->load("projects");
		$socidforproject = GETPOSTINT('socid');
		if (empty($socidforproject) && !empty($object->socid)) {
			$socidforproject = (int) $object->socid;
		}
		$fk_project = GETPOSTINT('fk_project');
		if (empty($fk_project) && !empty($object->fk_project)) {
			$fk_project = (int) $object->fk_project;
		}
		print '<tr class="field_fk_project">';
		print '<td class="titlefieldcreate">'.$langs->trans("Project").'</td><td class="valuefieldcreate">';
		print img_picto('', 'project', 'class="pictofixedwidth"').$formproject->select_projects(($socidforproject > 0 ? $socidforproject : -1), $fk_project, 'fk_project', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'maxwidth500 widthcentpercentminusxx');
		$projectcreateurl = DOL_URL_ROOT.'/projet/card.php?action=create&status=1';
		if ($socidforproject > 0) {
			$projectcreateurl .= '&socid='.$socidforproject;
		}
		$projectcreateurl .= '&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create'.($socidforproject > 0 ? '&socid='.$socidforproject : ''));
		print ' <a href="'.$projectcreateurl.'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddProject").'"></span></a>';
		print '</td>';
		print '</tr>';
	}

	// Description
	print '<tr class="field_description">';
	print '<td class="titlefieldcreate tdtop">'.$langs->trans('Description').'</td>';
	print '<td class="valuefieldcreate">';
	$description = GETPOST('description', 'none');
	if ($description === '') {
		$description = ($templatedefaultdescription !== '' ? $templatedefaultdescription : $object->getDefaultCreateValueFor('description'));
	}
	$doleditor = new DolEditor('description', $description, '', 160, 'dolibarr_details', '', false, true, getDolGlobalString('FCKEDITOR_ENABLE_DETAILS'), ROWS_4, '90%');
	print $doleditor->Create(1);
	print '</tr>';


	// Common attributes
	//include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("CreateDraft");

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("Diffusion"), '', $object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$head = diffusionPrepareHead($object);

	print dol_get_fiche_head($head, 'card', $langs->trans("Diffusion"), -1, $object->picto, 0, '', '', 0, '', 1);

	$formconfirm = '';

	// Confirmation to delete (using preloaded confirm popup)
	if ($action == 'delete' || ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile))) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteDiffusion'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 'action-delete');
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}

	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Reserved: add dedicated confirmation popups here for real business actions.

	if ($action == 'ask_import_project_contacts' && $permissiontoadd && !empty($object->fk_project)) {
		$project = new Project($db);
		$projectresult = $project->fetch((int) $object->fk_project);
		if ($projectresult > 0) {
			$formquestion = array();
			$internalcontacts = $project->liste_contact(-1, 'internal');
			$externalcontacts = $project->liste_contact(-1, 'external');

			$formquestion[] = array('type' => 'other', 'name' => 'project_contacts_help', 'label' => '', 'value' => '<span class="opacitymedium">'.$langs->trans('SelectProjectContactsToImport').'</span>');

			foreach (array('internal' => (array) $internalcontacts, 'external' => (array) $externalcontacts) as $source => $contacts) {
				foreach ($contacts as $contactline) {
					$contactlabel = '';
					if ($source === 'internal') {
						$usercontact = new User($db);
						if ($usercontact->fetch((int) $contactline['id']) > 0) {
							$contactlabel = $usercontact->getFullName($langs);
						}
					} else {
						$soccontact = new Contact($db);
						if ($soccontact->fetch((int) $contactline['id']) > 0) {
							$contactlabel = $soccontact->getFullName($langs);
						}
					}

					if (empty($contactlabel)) {
						$contactlabel = $langs->trans('Contact').' #'.((int) $contactline['id']);
					}

					$contacttype = dol_escape_htmltag($contactline['libelle']);
					$checkboxlabel = dol_escape_htmltag($contactlabel).' <span class="opacitymedium">('.$contacttype.')</span>';
					$formquestion[] = array(
						'type' => 'checkbox',
						'name' => 'projectcontacts_'.$source.'_'.((int) $contactline['id']).'_'.((int) $contactline['fk_c_type_contact']),
						'label' => $checkboxlabel,
						'value' => $source.':'.((int) $contactline['id']).':'.((int) $contactline['fk_c_type_contact'])
					);
				}
			}

			if (count($formquestion) === 1) {
				$formquestion[] = array('type' => 'other', 'name' => 'project_contacts_empty', 'label' => '', 'value' => '<span class="opacitymedium">'.$langs->trans('NoProjectContactsToImport').'</span>');
			}
			$contactlinecount = max(0, count($formquestion) - 1);
			$popupheight = 280 + (35 * $contactlinecount);
			$popupheight = min(760, max(360, $popupheight));
			$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('ImportProjectContacts'), $langs->trans('ConfirmImportProjectContacts'), 'importprojectcontacts', $formquestion, 'yes', 1, $popupheight);
		} else {
			setEventMessages($langs->trans('ErrorFailedToLoadProject'), null, 'errors');
		}
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/diffusion/diffusion_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$inlineEditable = ($permissiontoadd && $object->status == $object::STATUS_DRAFT);

	$morehtmlref = '<div class="refidno">';
	if (isset($object->fields['label'])) {
		$morehtmlref .= $form->editfieldkey($object->fields['label']['label'], 'label', '', $object, $inlineEditable, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval($object->fields['label']['label'], 'label', $object->label, $object, $inlineEditable, 'string', '', null, null, '', 1);
	}
	if (isModEnabled('project')) {
		$langs->load("projects");
		$morehtmlref .= '<br>';
		if ($permissiontoadd) {
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
		} elseif (!empty($object->fk_project)) {
			$proj = new Project($db);
			$proj->fetch($object->fk_project);
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"').$proj->getNomUrl(1);
			if (!empty($proj->title)) {
				$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
			}
		}
	}
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


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
        print '<table class="border centpercent tableforfield">'."\n";

        $fieldsBackup = $object->fields;
        $labelFieldDef = isset($object->fields['label']) ? $object->fields['label'] : null;
        $descriptionFieldDef = isset($object->fields['description']) ? $object->fields['description'] : null;
        if ($labelFieldDef !== null) {
                unset($object->fields['label']);
        }
        if ($descriptionFieldDef !== null) {
                unset($object->fields['description']);
        }
        // Common attributes
	//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
	unset($object->fields['fk_project']);				// Hide field already shown in banner
	unset($object->fields['date_expedition']);		// Hide field already shown in banner
	unset($object->fields['fk_user_exped']);			// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner
        include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

        $object->fields = $fieldsBackup;

        // Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	if ($descriptionFieldDef !== null) {
		print '<div class="clearboth"></div>';
		print '<table class="border centpercent tableforfield">';
		print '<tr class="field_description">';
		print '<td>'.$descriptionFieldDef['label'].'</td>';
		print '<td class="valuefield wordbreak">';
		if ($inlineEditable) {
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
			$doleditor = new DolEditor('description', $object->description, '', 160, 'dolibarr_details', '', false, true, getDolGlobalString('FCKEDITOR_ENABLE_DETAILS'), ROWS_4, '100%');
			print $doleditor->Create(1);
			print '<div class="center">';
			print '<input type="submit" class="button button-save" value="'.$langs->trans('Save').'">';
			print '</div>';
			print '</form>';
		} elseif (getDolGlobalString('FCKEDITOR_ENABLE_DETAILS')) {
			if (function_exists('dol_print_html')) {
				print dol_print_html($object->description, '1');
			} else {
				print $object->description;
			}
		} else {
			print dol_nl2br(dol_escape_htmltag($object->description));
		}
		print '</td>';
		print '</tr>';
		print '</table>';
	}

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	/*
	 * Lines
	 */

	include './tpl/diffusion_contacts.tpl.php';

	if (!empty($object->table_element_line)) {
		// Show object lines
		$result = $object->getLinesArray();

		print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOSTINT('lineid')).'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
		<input type="hidden" name="id" value="' . $object->id.'">
		';

		if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			$fk_element = !empty($object->fk_element) ? $object->fk_element : 'fk_'.$object->element;
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '<table id="tablelines" class="noborder noshadow" width="100%">';
		}

		if (!empty($object->lines)) {
			$object->printObjectLines($action, $mysoc, null, GETPOSTINT('lineid'), 1);
		}
/*
		// Form to add new line
		if ($object->status == 0 && $permissiontoadd && $action != 'selectlines') {
			if ($action != 'editline') {
				// Add products/services form

				$parameters = array();
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				if ($reshook < 0) {
					setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				}
				if (empty($reshook)) {
					$object->formAddObjectLine(1, $mysoc, $soc);
				}
			}
		}
*/
		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '</table>';
		}
		print '</div>';

		print "</form>\n";
	}


	// Buttons for actions

	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Send
			if (empty($user->socid) && $object->status == $object::STATUS_VALIDATED) {
				print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&token='.newToken().'&mode=init#formmailbeforetitle');
			}

			if (empty($user->socid) && $permissiontoadd && $object->status == $object::STATUS_DRAFT && !empty($object->fk_project) && empty($object->is_template)) {
				print dolGetButtonAction('', $langs->trans('ImportProjectContacts'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=ask_import_project_contacts&token='.newToken());
			}

			if (empty($user->socid) && $permissiontoadd && $object->status == $object::STATUS_DRAFT && empty($object->is_template)) {
				print dolGetButtonAction('', $langs->trans('ConvertirEnModele'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=convert_to_template&token='.newToken(), '', $permissiontoadd);
			}

			if (empty($user->socid) && $permissiontoadd && !empty($object->is_template)) {
				print dolGetButtonAction('', $langs->trans('CreerUneDiffusionDepuisModele'), 'default', $_SERVER['PHP_SELF'].'?action=create&fromtemplateid='.$object->id.'&token='.newToken(), '', $permissiontoadd);
			}

			// Back to draft
			if ($object->status == $object::STATUS_VALIDATED || $object->status == $object::STATUS_SENT) {
				print dolGetButtonAction('', $langs->trans('SetToDraft'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes&token='.newToken(), '', $permissiontoadd);
			}

			if ($object->status == $object::STATUS_VALIDATED) {
				print dolGetButtonAction('', $langs->trans('MarkAsSent'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=set_sent&token='.newToken(), '', $permissiontoadd);
			}

			// Modify
			//print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);

			// Validate
			if ($object->status == $object::STATUS_DRAFT && empty($object->is_template)) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken(), '', $permissiontoadd);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
				}
			}

			// Clone
			if ($permissiontoadd && empty($object->is_template)) {
				print dolGetButtonAction('', $langs->trans('ToClone'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.(!empty($object->socid) ? '&socid='.$object->socid : '').'&action=clone&token='.newToken(), '', $permissiontoadd);
			}

			/*
			// Disable / Enable
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_ENABLED) {
					print dolGetButtonAction('', $langs->trans('Disable'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=disable&token='.newToken(), '', $permissiontoadd);
				} else {
					print dolGetButtonAction('', $langs->trans('Enable'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=enable&token='.newToken(), '', $permissiontoadd);
				}
			}
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_VALIDATED) {
					print dolGetButtonAction('', $langs->trans('Cancel'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=close&token='.newToken(), '', $permissiontoadd);
				} else {
					print dolGetButtonAction('', $langs->trans('Re-Open'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=reopen&token='.newToken(), '', $permissiontoadd);
				}
			}
			*/

			// Delete (with preloaded confirm popup)
			$deleteUrl = $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken();
			$buttonId = 'action-delete-no-ajax';
			if ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile)) {	// We can use preloaded confirm if not jmobile
				$deleteUrl = '';
				$buttonId = 'action-delete';
			}
			$params = array();
			print dolGetButtonAction('', $langs->trans("Delete"), 'delete', $deleteUrl, $buttonId, $permissiontodelete, $params);
		}
		print '</div>'."\n";
	}


	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		$includedocgeneration = 1;

		// Documents

		$object->element = "diffusiondoc";
		if ($includedocgeneration) {
			$objref = dol_sanitizeFileName($object->ref);
			$relativepath = $object->element.'/'.$objref;
			$filedir = $upload_dir;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id.'&entity='.(int) $entityfordoc;
			$genallowed = $permissiontoread; // If you can read, you can build the PDF to read content
			$delallowed = $permissiontoadd; // If you can create/edit, you can remove a file on card
			$modulepart = 'diffusion';
			dol_syslog(__METHOD__.' showdocuments entity='.(int) $entityfordoc.' relativepath='.$relativepath.' filedir='.$filedir.' modulepart='.$modulepart, LOG_DEBUG);
			$tmperrorreporting = error_reporting();
			error_reporting($tmperrorreporting & ~E_WARNING);
			$moreparam = '&entity='.(int) $entityfordoc;
			print $formfile->showdocuments($modulepart, $relativepath, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, $moreparam, '', '', $langs->defaultlang);
			error_reporting($tmperrorreporting);
		}
		/*
		// Show links to link elements
		$object->element = "diffusion";
		$tmparray = $form->showLinkToObjectBlock($object, array(), array('diffusion'), 1);
		if (is_array($tmparray)) {
			$linktoelem = $tmparray['linktoelem'];
			$htmltoenteralink = $tmparray['htmltoenteralink'];
			print $htmltoenteralink;
			$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);
		} else {
			// backward compatibility
			$somethingshown = $form->showLinkedObjectBlock($object, $tmparray);
		}
		*/
		print '</div><div class="fichehalfright">';

		$MAXEVENT = 10;

		$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/diffusion/diffusion_agenda.php', 1).'?id='.$object->id);

		$includeeventlist = 1;

		// List of actions on element
		if ($includeeventlist) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);
		}

		print '</div></div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form

	if ($action == 'presend' && getDolGlobalInt('MAIN_MAIL_ENABLED_USER_DEST_SELECT') && !GETPOSTISSET('receiveruser')) {
		$sql = 'SELECT DISTINCT dc.fk_contact';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'diffusion_contact as dc';
		$sql .= ' WHERE dc.fk_diffusion = '.((int) $object->id);
		$sql .= " AND dc.contact_source = 'internal'";
		$sql .= ' AND dc.mail_status = 1';

		$resql = $db->query($sql);
		if ($resql) {
			$receiveruser = array();
			while ($obj = $db->fetch_object($resql)) {
				$receiveruser[] = (int) $obj->fk_contact;
			}

			if (!empty($receiveruser)) {
				$_POST['receiveruser'] = $receiveruser;
				$_REQUEST['receiveruser'] = $receiveruser;
			}
		}
	}

	$modelmail = 'diffusion';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->diffusion->multidir_output[$conf->entity];
	$trackid = 'diffusion'.$object->id;

	include dol_buildpath('/diffusion/tpl/card_presend.tpl.php', 0);
}

// End of page
llxFooter();
$db->close();
