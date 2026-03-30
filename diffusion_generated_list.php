<?php
/* Copyright (C) 2025-2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
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
 * \file       diffusion_generated_list.php
 * \ingroup    diffusion
 * \brief      Generated diffusions list from a template
 */

$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/diffusion/class/diffusion.class.php');
dol_include_once('/diffusion/lib/diffusion_diffusion.lib.php');

$langs->loadLangs(array("diffusion@diffusion", "other"));

$id = GETPOSTINT('id');
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTINT('page');
if (empty($page) || $page < 0) {
	$page = 0;
}
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$offset = $limit * $page;

$object = new Diffusion($db);
$form = new Form($db);

$permissiontoread = (!empty($user->admin) || $user->hasRight('diffusion', 'diffusiondoc', 'read'));
if (!$permissiontoread) {
	accessforbidden();
}

if ($id <= 0 || $object->fetch($id) <= 0 || empty($object->is_template)) {
	accessforbidden();
}

if (empty($sortfield)) {
	$sortfield = 't.rowid';
}
if (empty($sortorder)) {
	$sortorder = 'DESC';
}

$head = diffusionPrepareHead($object);

$sql = "SELECT t.rowid, t.ref, t.label, t.date_creation, t.date_expedition, t.status";
$sql .= " FROM ".MAIN_DB_PREFIX."diffusion as t";
$sql .= " WHERE t.entity IN (".getEntity('diffusion').")";
$sql .= " AND t.is_template = 0";
$sql .= " AND t.model_source = ".((int) $object->id);
$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit, $offset);

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$sqlcount = "SELECT COUNT(t.rowid) as nb";
$sqlcount .= " FROM ".MAIN_DB_PREFIX."diffusion as t";
$sqlcount .= " WHERE t.entity IN (".getEntity('diffusion').")";
$sqlcount .= " AND t.is_template = 0";
$sqlcount .= " AND t.model_source = ".((int) $object->id);
$resqlcount = $db->query($sqlcount);
$nbtotalofrecords = 0;
if ($resqlcount) {
	$objcount = $db->fetch_object($resqlcount);
	$nbtotalofrecords = (int) $objcount->nb;
}

$title = $langs->trans('DiffusionsGenerees');
llxHeader('', $title);

print dol_get_fiche_head($head, 'generated', $langs->trans('DiffusionModele'), -1, $object->picto, 0, '', '', 0, '', 1);
$linkback = '<a href="'.dol_buildpath('/diffusion/diffusion_list.php', 1).'?show_templates=1">'.$langs->trans("BackToList").'</a>';
dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref');
print dol_get_fiche_end();

$param = 'id='.(int) $object->id;
print '<br>';
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], '&'.$param, $sortfield, $sortorder, '', $db->num_rows($resql), $nbtotalofrecords, '', 0, '', '', $limit, 0, 0, 1);
print '<div class="div-table-responsive">';
print '<table class="tagtable liste">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "t.ref", "", '&'.$param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Label"), $_SERVER["PHP_SELF"], "t.label", "", '&'.$param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("DateCreation"), $_SERVER["PHP_SELF"], "t.date_creation", "", '&'.$param, '', $sortfield, $sortorder, 'center ');
print_liste_field_titre($langs->trans("DateEnvoi"), $_SERVER["PHP_SELF"], "t.date_expedition", "", '&'.$param, '', $sortfield, $sortorder, 'center ');
print_liste_field_titre($langs->trans("Status"), $_SERVER["PHP_SELF"], "t.status", "", '&'.$param, '', $sortfield, $sortorder, 'center ');
print '</tr>';

if ($db->num_rows($resql) > 0) {
	while ($obj = $db->fetch_object($resql)) {
		$tmpobj = new Diffusion($db);
		$tmpobj->id = (int) $obj->rowid;
		$tmpobj->ref = $obj->ref;
		$tmpobj->status = (int) $obj->status;
		print '<tr class="oddeven">';
		print '<td>'.$tmpobj->getNomUrl(1).'</td>';
		print '<td>'.dol_escape_htmltag($obj->label).'</td>';
		print '<td class="center">'.dol_print_date($db->jdate($obj->date_creation), 'dayhour').'</td>';
		print '<td class="center">'.(!empty($obj->date_expedition) ? dol_print_date($db->jdate($obj->date_expedition), 'dayhour') : '<span class="opacitymedium">'.$langs->trans('None').'</span>').'</td>';
		print '<td class="center">'.$tmpobj->getLibStatut(5).'</td>';
		print '</tr>';
	}
} else {
	print '<tr class="oddeven">';
	print '<td class="opacitymedium center" colspan="5">'.$langs->trans('NoGeneratedDiffusionFromTemplate').'</td>';
	print '</tr>';
}

print '</table>';
print '</div>';

llxFooter();
$db->close();
