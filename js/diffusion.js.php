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
 *
 * Library javascript to enable Browser notifications
 */

if (!defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}


/**
 * \file    diffusion/js/diffusion.js.php
 * \ingroup diffusion
 * \brief   JavaScript file for module Diffusion.
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
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/../main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/../main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=3600, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}

$diffusionSubstitutionHelpHtml = '';
dol_include_once('/diffusion/core/substitutions/functions_diffusion.lib.php');
if (function_exists('diffusion_get_available_substitution_help_html')) {
	if (empty($langs) || !is_object($langs)) {
		require_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';

		$langs = new Translate('', $conf);
		$defaultlang = '';
		if (!empty($_SESSION['dol_lang'])) {
			$defaultlang = $_SESSION['dol_lang'];
		} elseif (function_exists('getDolGlobalString')) {
			$defaultlang = getDolGlobalString('MAIN_LANG_DEFAULT');
		} elseif (!empty($conf->global->MAIN_LANG_DEFAULT)) {
			$defaultlang = $conf->global->MAIN_LANG_DEFAULT;
		}
		$langs->setDefaultLang($defaultlang ?: 'en_US');
	}

	$diffusionSubstitutionHelpHtml = diffusion_get_available_substitution_help_html($langs);
}
?>

/* Javascript library of module Diffusion */

var diffusionSubstitutionHelpHtml = <?php echo json_encode($diffusionSubstitutionHelpHtml, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

function diffusionAppendSubstitutionHelp(existingHtml) {
	'use strict';

	existingHtml = existingHtml || '';
	if (!diffusionSubstitutionHelpHtml || existingHtml.indexOf('__DIFFUSIONCONTACT_ID__') !== -1) {
		return existingHtml;
	}

	return existingHtml + '<br><br>' + diffusionSubstitutionHelpHtml;
}

function diffusionEnhanceEmailTemplateSubstitutionTooltips() {
	'use strict';

	if (window.location.pathname.indexOf('/admin/mails_templates.php') === -1) {
		return;
	}

	jQuery('#idfortooltiponclick_topic, #idfortooltiponclick_content, #idfortooltiponclick_content_lines').each(function () {
		var $tooltip = jQuery(this);
		$tooltip.html(diffusionAppendSubstitutionHelp($tooltip.html()));
	});

	jQuery('.classfortooltip[title], .classfortooltiponclick[title]').each(function () {
		var $element = jQuery(this);
		var title = $element.attr('title') || '';

		if (title && (title.indexOf('__REF__') !== -1 || title.indexOf('__ID__') !== -1 || title.indexOf('Available') !== -1)) {
			$element.attr('title', diffusionAppendSubstitutionHelp(title));
		}
	});
}

jQuery(document).ready(function () {
	'use strict';

	diffusionEnhanceEmailTemplateSubstitutionTooltips();

	jQuery(document).on('click', '.diffusion-select-all-project-contacts', function (event) {
		var target = jQuery(this).data('target');

		event.preventDefault();
		if (!target) {
			return;
		}

		jQuery('#' + target).find('input.diffusion-project-contact-checkbox').prop('checked', true);
	});

	if (!jQuery('body').hasClass('page-notification')) {
		return;
	}

	jQuery('table.noborder tr.oddeven').each(function () {
		var $row = jQuery(this);
		var code = jQuery.trim($row.find('td').eq(1).text());

		if (!/^DIFFUSION/.test(code)) {
			return;
		}

		// Notification codes for diffusion do not use amount thresholds.
		$row.find('td').eq(4).html('&nbsp;');
		$row.find('input[name^="NOTIF_' + code + '_old_"][name$="_amount"]').prop('disabled', true).hide();
		$row.find('input[name="NOTIF_' + code + '_new_amount"]').prop('disabled', true).hide();
	});
});
