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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Complete substitutions for Diffusion notifications and email templates.
 *
 * @param array<string,string> $substitutionarray Substitution array
 * @param Translate $langs Output language
 * @param CommonObject|null $object Current object, null when Dolibarr builds email template help
 * @param mixed $parameters Extra parameters
 * @return void
 */
function diffusion_completesubstitutionarray(&$substitutionarray, $langs, $object, $parameters = null)
{
	if (is_object($langs)) {
		$langs->loadLangs(array('diffusion@diffusion'));
	}

	if (empty($object) || !is_object($object)) {
		if (diffusion_is_substitution_catalog_mode($parameters)) {
			diffusion_register_available_substitution_keys($substitutionarray, $langs);
		}
		return;
	}

	$isDiffusion = (!empty($object->element) && $object->element === 'diffusiondoc')
		|| (!empty($object->table_element) && $object->table_element === 'diffusion');
	$isDiffusionContact = (!empty($object->element) && $object->element === 'diffusioncontact')
		|| (!empty($object->table_element) && $object->table_element === 'diffusion_contact');
	if (!$isDiffusion && !$isDiffusionContact) {
		return;
	}

	$fillDiffusionSubstitutions = function ($diffusion) use (&$substitutionarray, $langs) {
		$url = '';
		if (!empty($diffusion->id)) {
			$url = dol_buildpath('/diffusion/diffusion_card.php', 2).'?id='.(int) $diffusion->id;
		}

		$substitutionarray['__DIFFUSION_REF__'] = !empty($diffusion->ref) ? (string) $diffusion->ref : '';
		$substitutionarray['__DIFFUSION_LABEL__'] = !empty($diffusion->label) ? (string) $diffusion->label : '';
		$substitutionarray['__DIFFUSION_DESCRIPTION__'] = !empty($diffusion->description) ? dol_string_nohtmltag((string) $diffusion->description) : '';
		$substitutionarray['__DIFFUSION_STATUS__'] = isset($diffusion->status) ? diffusion_get_status_label_for_substitution($diffusion->status, $langs) : '';
		$substitutionarray['__DIFFUSION_URL__'] = $url;
		$substitutionarray['__DIFFUSION_PROJECT_REF__'] = '';
		$substitutionarray['__DIFFUSION_PROJECT_LABEL__'] = '';
		$substitutionarray['__DIFFUSION_THIRDPARTY_NAME__'] = '';
		$substitutionarray['__DIFFUSION_AUTHOR_FULLNAME__'] = '';
		$substitutionarray['__DIFFUSION_AUTHOR_EMAIL__'] = '';

		$thirdpartyid = 0;
		if (!empty($diffusion->socid)) {
			$thirdpartyid = (int) $diffusion->socid;
		} elseif (!empty($diffusion->fk_soc)) {
			$thirdpartyid = (int) $diffusion->fk_soc;
		}

		if (!empty($diffusion->fk_project)) {
			require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
			$project = new Project($diffusion->db);
			if ($project->fetch((int) $diffusion->fk_project) > 0) {
				$substitutionarray['__DIFFUSION_PROJECT_REF__'] = (string) $project->ref;
				$substitutionarray['__DIFFUSION_PROJECT_LABEL__'] = (string) $project->title;
				if (empty($thirdpartyid) && !empty($project->socid)) {
					$thirdpartyid = (int) $project->socid;
				}
			}
		}

		if (!empty($thirdpartyid)) {
			require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
			$thirdparty = new Societe($diffusion->db);
			if ($thirdparty->fetch($thirdpartyid) > 0) {
				$substitutionarray['__DIFFUSION_THIRDPARTY_NAME__'] = (string) $thirdparty->name;
			}
		}

		if (!empty($diffusion->fk_user_creat)) {
			require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
			$author = new User($diffusion->db);
			if ($author->fetch((int) $diffusion->fk_user_creat) > 0) {
				$substitutionarray['__DIFFUSION_AUTHOR_FULLNAME__'] = $author->getFullName($langs);
				$substitutionarray['__DIFFUSION_AUTHOR_EMAIL__'] = (string) $author->email;
			}
		}
	};

	if ($isDiffusion) {
		$fillDiffusionSubstitutions($object);
		return;
	}

	$diffusionid = !empty($object->fk_diffusion) ? (int) $object->fk_diffusion : 0;
	$substitutionarray['__DIFFUSIONCONTACT_ID__'] = !empty($object->id) ? (string) $object->id : '';
	$substitutionarray['__DIFFUSIONCONTACT_FK_DIFFUSION__'] = $diffusionid > 0 ? (string) $diffusionid : '';
	$substitutionarray['__DIFFUSIONCONTACT_CONTACT_ID__'] = !empty($object->fk_contact) ? (string) $object->fk_contact : '';
	$substitutionarray['__DIFFUSIONCONTACT_SOURCE__'] = !empty($object->contact_source) ? (string) $object->contact_source : '';
	$substitutionarray['__DIFFUSIONCONTACT_NAME__'] = '';
	$substitutionarray['__DIFFUSIONCONTACT_EMAIL__'] = '';
	$substitutionarray['__DIFFUSIONCONTACT_MAIL_STATUS__'] = isset($object->mail_status) ? diffusion_get_binary_status_label_for_substitution($object->mail_status, $langs) : '';
	$substitutionarray['__DIFFUSIONCONTACT_LETTER_STATUS__'] = isset($object->letter_status) ? diffusion_get_binary_status_label_for_substitution($object->letter_status, $langs) : '';
	$substitutionarray['__DIFFUSIONCONTACT_HAND_STATUS__'] = isset($object->hand_status) ? diffusion_get_binary_status_label_for_substitution($object->hand_status, $langs) : '';
	$substitutionarray['__DIFFUSIONCONTACT_URL__'] = $diffusionid > 0 ? dol_buildpath('/diffusion/diffusion_card.php', 2).'?id='.$diffusionid : '';

	if (!empty($object->fk_contact)) {
		if (!empty($object->contact_source) && $object->contact_source === 'internal') {
			require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
			$contactuser = new User($object->db);
			if ($contactuser->fetch((int) $object->fk_contact) > 0) {
				$substitutionarray['__DIFFUSIONCONTACT_NAME__'] = $contactuser->getFullName($langs);
				$substitutionarray['__DIFFUSIONCONTACT_EMAIL__'] = (string) $contactuser->email;
			}
		} else {
			require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
			$contact = new Contact($object->db);
			if ($contact->fetch((int) $object->fk_contact) > 0) {
				$substitutionarray['__DIFFUSIONCONTACT_NAME__'] = $contact->getFullName($langs);
				$substitutionarray['__DIFFUSIONCONTACT_EMAIL__'] = (string) $contact->email;
			}
		}
	}

	if ($diffusionid > 0) {
		dol_include_once('/diffusion/class/diffusion.class.php');
		if (class_exists('Diffusion')) {
			$diffusion = new Diffusion($object->db);
			if ($diffusion->fetch($diffusionid) > 0) {
				$fillDiffusionSubstitutions($diffusion);
			}
		}
	}
}

/**
 * Check whether Dolibarr is building the help/catalog of available email variables.
 *
 * @param mixed $parameters Extra parameters
 * @return bool
 */
function diffusion_is_substitution_catalog_mode($parameters)
{
	if (!is_array($parameters) || empty($parameters['mode'])) {
		return empty($parameters);
	}

	return in_array((string) $parameters['mode'], array('formemail', 'formemailwithlines', 'formemailforlines', 'emailing'), true);
}

/**
 * Register Diffusion substitution keys for Dolibarr email template help.
 *
 * @param array<string,string> $substitutionarray Substitution array
 * @param Translate $langs Output language
 * @return void
 */
function diffusion_register_available_substitution_keys(&$substitutionarray, $langs)
{
	foreach (diffusion_get_available_substitution_keys($langs) as $key => $label) {
		$substitutionarray[$key] = $label;
	}
}

/**
 * Return Diffusion substitution keys with translated labels.
 *
 * @param Translate|null $langs Output language
 * @return array<string,string>
 */
function diffusion_get_available_substitution_keys($langs = null)
{
	if (is_object($langs)) {
		$langs->loadLangs(array('diffusion@diffusion'));
	}

	$translationkeys = array(
		'__DIFFUSION_REF__' => 'DiffusionSubstitutionDiffusionRef',
		'__DIFFUSION_LABEL__' => 'DiffusionSubstitutionDiffusionLabel',
		'__DIFFUSION_DESCRIPTION__' => 'DiffusionSubstitutionDiffusionDescription',
		'__DIFFUSION_STATUS__' => 'DiffusionSubstitutionDiffusionStatus',
		'__DIFFUSION_URL__' => 'DiffusionSubstitutionDiffusionUrl',
		'__DIFFUSION_PROJECT_REF__' => 'DiffusionSubstitutionDiffusionProjectRef',
		'__DIFFUSION_PROJECT_LABEL__' => 'DiffusionSubstitutionDiffusionProjectLabel',
		'__DIFFUSION_THIRDPARTY_NAME__' => 'DiffusionSubstitutionDiffusionThirdpartyName',
		'__DIFFUSION_AUTHOR_FULLNAME__' => 'DiffusionSubstitutionDiffusionAuthorFullname',
		'__DIFFUSION_AUTHOR_EMAIL__' => 'DiffusionSubstitutionDiffusionAuthorEmail',
		'__DIFFUSIONCONTACT_ID__' => 'DiffusionSubstitutionDiffusionContactId',
		'__DIFFUSIONCONTACT_FK_DIFFUSION__' => 'DiffusionSubstitutionDiffusionContactFkDiffusion',
		'__DIFFUSIONCONTACT_CONTACT_ID__' => 'DiffusionSubstitutionDiffusionContactContactId',
		'__DIFFUSIONCONTACT_SOURCE__' => 'DiffusionSubstitutionDiffusionContactSource',
		'__DIFFUSIONCONTACT_NAME__' => 'DiffusionSubstitutionDiffusionContactName',
		'__DIFFUSIONCONTACT_EMAIL__' => 'DiffusionSubstitutionDiffusionContactEmail',
		'__DIFFUSIONCONTACT_MAIL_STATUS__' => 'DiffusionSubstitutionDiffusionContactMailStatus',
		'__DIFFUSIONCONTACT_LETTER_STATUS__' => 'DiffusionSubstitutionDiffusionContactLetterStatus',
		'__DIFFUSIONCONTACT_HAND_STATUS__' => 'DiffusionSubstitutionDiffusionContactHandStatus',
		'__DIFFUSIONCONTACT_URL__' => 'DiffusionSubstitutionDiffusionContactUrl',
	);

	$keys = array();
	foreach ($translationkeys as $key => $translationkey) {
		$keys[$key] = is_object($langs) ? $langs->transnoentitiesnoconv($translationkey) : $translationkey;
	}

	return $keys;
}

/**
 * Return HTML help for Diffusion substitution keys.
 *
 * @param Translate|null $langs Output language
 * @return string
 */
function diffusion_get_available_substitution_help_html($langs = null)
{
	if (is_object($langs)) {
		$langs->loadLangs(array('diffusion@diffusion'));
	}

	$title = is_object($langs) ? $langs->transnoentitiesnoconv('DiffusionSubstitutionHelpTitle') : 'Diffusion variables';
	$intro = is_object($langs) ? $langs->transnoentitiesnoconv('DiffusionSubstitutionHelpIntro') : 'You can use the following substitution keys:';

	$out = '<strong>'.dol_escape_htmltag($title).'</strong><br>';
	$out .= dol_escape_htmltag($intro).'<br><br>';
	$out .= '<span class="small">';
	foreach (diffusion_get_available_substitution_keys($langs) as $key => $label) {
		$out .= dol_escape_htmltag($key).' -> '.dol_escape_htmltag($label).'<br>';
	}
	$out .= '</span>';

	return $out;
}

/**
 * Return a translated Diffusion status label for notification substitutions.
 *
 * @param mixed $status Diffusion status
 * @param Translate $langs Output language
 * @return string
 */
function diffusion_get_status_label_for_substitution($status, $langs)
{
	if ($status === null || $status === '') {
		return '';
	}

	$statuskeys = array(
		0 => 'DiffusionStatusDraft',
		1 => 'DiffusionStatusValidated',
		6 => 'DiffusionStatusSent',
		9 => 'DiffusionStatusCanceled',
	);

	$status = (int) $status;
	if (isset($statuskeys[$status]) && is_object($langs)) {
		return $langs->transnoentitiesnoconv($statuskeys[$status]);
	}

	return (string) $status;
}

/**
 * Return a translated active/inactive label for contact notification substitutions.
 *
 * @param mixed $status Boolean-like status
 * @param Translate $langs Output language
 * @return string
 */
function diffusion_get_binary_status_label_for_substitution($status, $langs)
{
	if ($status === null || $status === '') {
		return '';
	}

	if (is_object($langs)) {
		return $langs->transnoentitiesnoconv(((int) $status) ? 'Enabled' : 'Disabled');
	}

	return (string) $status;
}

/**
 * Backward-compatible substitution entry point for older local calls.
 *
 * Dolibarr v20 calls diffusion_completesubstitutionarray() for
 * functions_diffusion.lib.php.
 *
 * @param array<string,string> $substitutionarray Substitution array
 * @param Translate $langs Output language
 * @param CommonObject|null $object Current object, null when Dolibarr builds email template help
 * @param mixed $parameters Extra parameters
 * @return void
 */
function complete_substitutions_array_diffusion(&$substitutionarray, $langs, $object, $parameters = null)
{
	diffusion_completesubstitutionarray($substitutionarray, $langs, $object, $parameters);
}
