<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file		admin/compatibility.php
 * \ingroup	diffusion
 * \brief		Compatibility page for Diffusion module.
 */

$res = 0;
if (!$res && !empty($_SERVER['CONTEXT_DOCUMENT_ROOT'])) {
	$res = @include $_SERVER['CONTEXT_DOCUMENT_ROOT'].'/main.inc.php';
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)).'/main.inc.php')) {
	$res = @include substr($tmp, 0, ($i + 1)).'/main.inc.php';
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))).'/main.inc.php')) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))).'/main.inc.php';
}
if (!$res && file_exists('../../main.inc.php')) {
	$res = @include '../../main.inc.php';
}
if (!$res && file_exists('../../../main.inc.php')) {
	$res = @include '../../../main.inc.php';
}
if (!$res) {
	die('Include of main fails');
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once '../lib/diffusion.lib.php';
require_once '../class/diffusioncompatibility.class.php';

/**
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array('admin', 'diffusion@diffusion'));

if (empty($user->admin)) {
	accessforbidden();
}

$title = $langs->trans('DiffusionCompatibility');
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?search_keyword='.urlencode('diffusion').'">'.$langs->trans('BackToModuleList').'</a>';

llxHeader('', $title);

print load_fiche_titre($title, $linkback, 'technic');

$head = diffusionAdminPrepareHead();
print dol_get_fiche_head($head, 'compatibility', $langs->trans('DiffusionSetup'), -1, 'diffusion@diffusion');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans('DiffusionCompatibilityEnvironment').'</th></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('DiffusionCompatibilityDetectedPhp').'</td><td>'.dol_escape_htmltag(PHP_VERSION).'</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('DiffusionCompatibilityDetectedDolibarr').'</td><td>'.dol_escape_htmltag(defined('DOL_VERSION') ? DOL_VERSION : '').'</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('DiffusionCompatibilityMinimumPhp').'</td><td>'.dol_escape_htmltag(DiffusionCompatibility::MIN_PHP_VERSION).'</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('DiffusionCompatibilityMinimumDolibarr').'</td><td>'.dol_escape_htmltag(DiffusionCompatibility::MIN_DOLIBARR_VERSION).'</td></tr>';
print '</table>';

print '<br>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>'.$langs->trans('Code').'</th>';
print '<th>'.$langs->trans('Label').'</th>';
print '<th>'.$langs->trans('Description').'</th>';
print '<th class="center">'.$langs->trans('Status').'</th>';
print '<th>'.$langs->trans('Reason').'</th>';
print '</tr>';

foreach (DiffusionCompatibility::getFeatures() as $code => $feature) {
	$status = DiffusionCompatibility::getFeatureStatus($code, $feature);
	print '<tr class="oddeven">';
	print '<td>'.dol_escape_htmltag($code).'</td>';
	print '<td>'.$langs->trans($status['label']).'</td>';
	print '<td>'.$langs->trans($status['description']).'</td>';
	print '<td class="center">'.yn(!empty($status['available'])).'</td>';
	print '<td>'.$langs->trans($status['reason']).'</td>';
	print '</tr>';
}

print '</table>';

print dol_get_fiche_end();

llxFooter();
$db->close();
