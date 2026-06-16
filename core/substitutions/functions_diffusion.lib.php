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

		diffusion_fill_standard_diffusion_substitutions($substitutionarray, $diffusion);
		$substitutionarray['__DIFFUSION_DESCRIPTION__'] = !empty($diffusion->description) ? dol_string_nohtmltag((string) $diffusion->description) : '';
		$substitutionarray['__DIFFUSION_STATUS__'] = isset($diffusion->status) ? diffusion_get_status_label_for_substitution($diffusion->status, $langs) : '';
		$substitutionarray['__DIFFUSION_URL__'] = $url;
		$substitutionarray['__DIFFUSION_THIRDPARTY_NAME__'] = '';
		diffusion_init_author_substitution_keys($substitutionarray);

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
				$substitutionarray['__PROJECT_ID__'] = !empty($project->id) ? (string) $project->id : '';
				$substitutionarray['__PROJECT_REF__'] = (string) $project->ref;
				$substitutionarray['__PROJECT_NAME__'] = (string) $project->title;
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
				diffusion_fill_author_substitution_keys($substitutionarray, $langs, $author);
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
 * Fill standard object substitution keys for a Diffusion object.
 *
 * DiffusionContact notifications load their parent Diffusion after the core
 * common substitutions have been built from the contact line object.
 *
 * @param array<string,string> $substitutionarray Substitution array
 * @param CommonObject $diffusion Diffusion object
 * @return void
 */
function diffusion_fill_standard_diffusion_substitutions(&$substitutionarray, $diffusion)
{
	$diffusionid = !empty($diffusion->id) ? (int) $diffusion->id : (!empty($diffusion->rowid) ? (int) $diffusion->rowid : 0);

	$substitutionarray['__ID__'] = $diffusionid > 0 ? (string) $diffusionid : '';
	$substitutionarray['__REF__'] = !empty($diffusion->ref) ? (string) $diffusion->ref : '';
	$substitutionarray['__NEWREF__'] = isset($diffusion->newref) ? (string) $diffusion->newref : '';
	$substitutionarray['__LABEL__'] = !empty($diffusion->label) ? (string) $diffusion->label : '';
	$substitutionarray['__PROJECT_ID__'] = '';
	$substitutionarray['__PROJECT_REF__'] = '';
	$substitutionarray['__PROJECT_NAME__'] = '';
}

/**
 * Initialize author substitution keys with empty values.
 *
 * @param array<string,string> $substitutionarray Substitution array
 * @return void
 */
function diffusion_init_author_substitution_keys(&$substitutionarray)
{
	foreach (diffusion_get_author_substitution_default_values() as $key => $value) {
		$substitutionarray[$key] = $value;
	}
}

/**
 * Return default values for author substitution keys.
 *
 * @return array<string,string>
 */
function diffusion_get_author_substitution_default_values()
{
	return array(
		'__AUTHOR_SIGNATURE__' => '',
		'__AUTHOR_ID__' => '',
		'__AUTHOR_LOGIN__' => '',
		'__AUTHOR_EMAIL__' => '',
		'__AUTHOR_PHONE__' => '',
		'__AUTHOR_PHONEPRO__' => '',
		'__AUTHOR_PHONEMOBILE__' => '',
		'__AUTHOR_FAX__' => '',
		'__AUTHOR_LASTNAME__' => '',
		'__AUTHOR_FIRSTNAME__' => '',
		'__AUTHOR_FULLNAME__' => '',
		'__AUTHOR_SUPERVISOR_ID__' => '0',
		'__AUTHOR_JOB__' => '',
		'__AUTHOR_REMOTE_IP__' => '',
		'__AUTHOR_VCARD_URL__' => '',
	);
}

/**
 * Fill author substitution keys from the Diffusion creator.
 *
 * @param array<string,string> $substitutionarray Substitution array
 * @param Translate|null $langs Output language
 * @param User $author Author user
 * @return void
 */
function diffusion_fill_author_substitution_keys(&$substitutionarray, $langs, $author)
{
	$useSignature = !function_exists('getDolGlobalString') || !getDolGlobalString('MAIN_MAIL_DO_NOT_USE_SIGN');

	$substitutionarray['__AUTHOR_SIGNATURE__'] = ($useSignature && !empty($author->signature)) ? (string) $author->signature : '';
	$substitutionarray['__AUTHOR_ID__'] = !empty($author->id) ? (string) $author->id : '';
	$substitutionarray['__AUTHOR_LOGIN__'] = isset($author->login) ? (string) $author->login : '';
	$substitutionarray['__AUTHOR_EMAIL__'] = isset($author->email) ? (string) $author->email : '';
	$substitutionarray['__AUTHOR_PHONE__'] = function_exists('dol_print_phone') ? (string) dol_print_phone((isset($author->office_phone) ? $author->office_phone : ''), '', 0, 0, '', ' ', '', '', -1) : (isset($author->office_phone) ? (string) $author->office_phone : '');
	$substitutionarray['__AUTHOR_PHONEPRO__'] = function_exists('dol_print_phone') ? (string) dol_print_phone((isset($author->user_mobile) ? $author->user_mobile : ''), '', 0, 0, '', ' ', '', '', -1) : (isset($author->user_mobile) ? (string) $author->user_mobile : '');
	$substitutionarray['__AUTHOR_PHONEMOBILE__'] = function_exists('dol_print_phone') ? (string) dol_print_phone((isset($author->personal_mobile) ? $author->personal_mobile : ''), '', 0, 0, '', ' ', '', '', -1) : (isset($author->personal_mobile) ? (string) $author->personal_mobile : '');
	$substitutionarray['__AUTHOR_FAX__'] = isset($author->office_fax) ? (string) $author->office_fax : '';
	$substitutionarray['__AUTHOR_LASTNAME__'] = isset($author->lastname) ? (string) $author->lastname : '';
	$substitutionarray['__AUTHOR_FIRSTNAME__'] = isset($author->firstname) ? (string) $author->firstname : '';
	$substitutionarray['__AUTHOR_FULLNAME__'] = method_exists($author, 'getFullName') ? (string) $author->getFullName($langs) : trim($substitutionarray['__AUTHOR_FIRSTNAME__'].' '.$substitutionarray['__AUTHOR_LASTNAME__']);
	$substitutionarray['__AUTHOR_SUPERVISOR_ID__'] = !empty($author->fk_user) ? (string) $author->fk_user : '0';
	$substitutionarray['__AUTHOR_JOB__'] = isset($author->job) ? (string) $author->job : '';
	$substitutionarray['__AUTHOR_REMOTE_IP__'] = function_exists('getUserRemoteIP') ? (string) getUserRemoteIP() : '';
	$substitutionarray['__AUTHOR_VCARD_URL__'] = method_exists($author, 'getOnlineVirtualCardUrl') ? (string) $author->getOnlineVirtualCardUrl('', 'external') : '';
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
		'__SENDEREMAIL_SIGNATURE__' => 'DiffusionSubstitutionSenderEmailSignature',
		'__USER_SIGNATURE__' => 'DiffusionSubstitutionUserSignature',
		'__USER_ID__' => 'DiffusionSubstitutionUser',
		'__USER_LOGIN__' => 'DiffusionSubstitutionUser',
		'__USER_EMAIL__' => 'DiffusionSubstitutionUser',
		'__USER_PHONE__' => 'DiffusionSubstitutionUser',
		'__USER_PHONEPRO__' => 'DiffusionSubstitutionUser',
		'__USER_PHONEMOBILE__' => 'DiffusionSubstitutionUser',
		'__USER_FAX__' => 'DiffusionSubstitutionUser',
		'__USER_LASTNAME__' => 'DiffusionSubstitutionUser',
		'__USER_FIRSTNAME__' => 'DiffusionSubstitutionUser',
		'__USER_FULLNAME__' => 'DiffusionSubstitutionUser',
		'__USER_SUPERVISOR_ID__' => 'DiffusionSubstitutionUser',
		'__USER_JOB__' => 'DiffusionSubstitutionUser',
		'__USER_REMOTE_IP__' => 'DiffusionSubstitutionUser',
		'__USER_VCARD_URL__' => 'DiffusionSubstitutionUser',
		'__AUTHOR_SIGNATURE__' => 'DiffusionSubstitutionAuthorSignature',
		'__AUTHOR_ID__' => 'DiffusionSubstitutionAuthor',
		'__AUTHOR_LOGIN__' => 'DiffusionSubstitutionAuthor',
		'__AUTHOR_EMAIL__' => 'DiffusionSubstitutionAuthor',
		'__AUTHOR_PHONE__' => 'DiffusionSubstitutionAuthor',
		'__AUTHOR_PHONEPRO__' => 'DiffusionSubstitutionAuthor',
		'__AUTHOR_PHONEMOBILE__' => 'DiffusionSubstitutionAuthor',
		'__AUTHOR_FAX__' => 'DiffusionSubstitutionAuthor',
		'__AUTHOR_LASTNAME__' => 'DiffusionSubstitutionAuthor',
		'__AUTHOR_FIRSTNAME__' => 'DiffusionSubstitutionAuthor',
		'__AUTHOR_FULLNAME__' => 'DiffusionSubstitutionAuthor',
		'__AUTHOR_SUPERVISOR_ID__' => 'DiffusionSubstitutionAuthor',
		'__AUTHOR_JOB__' => 'DiffusionSubstitutionAuthor',
		'__AUTHOR_REMOTE_IP__' => 'DiffusionSubstitutionAuthor',
		'__AUTHOR_VCARD_URL__' => 'DiffusionSubstitutionAuthor',
		'__MYCOMPANY_NAME__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_EMAIL__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_URL__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_PHONE__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_PHONEMOBILE__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_FAX__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_PROFID1__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_PROFID2__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_PROFID3__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_PROFID4__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_PROFID5__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_PROFID6__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_PROFID7__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_PROFID8__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_PROFID9__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_PROFID10__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_CAPITAL__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_FULLADDRESS__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_ADDRESS__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_VATNUMBER__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_ZIP__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_TOWN__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_STATE__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_COUNTRY__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_COUNTRY_ID__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_COUNTRY_CODE__' => 'DiffusionSubstitutionMyCompany',
		'__MYCOMPANY_CURRENCY_CODE__' => 'DiffusionSubstitutionMyCompany',
		'__ID__' => 'DiffusionSubstitutionObjectId',
		'__REF__' => 'DiffusionSubstitutionObjectRef',
		'__NEWREF__' => 'DiffusionSubstitutionObjectNewRef',
		'__LABEL__' => 'DiffusionSubstitutionObjectLabel',
		'__PROJECT_ID__' => 'DiffusionSubstitutionProject',
		'__PROJECT_REF__' => 'DiffusionSubstitutionProject',
		'__PROJECT_NAME__' => 'DiffusionSubstitutionProject',
		'__NOW_TMS__' => 'DiffusionSubstitutionDate',
		'__NOW_TMS_YMD__' => 'DiffusionSubstitutionDate',
		'__DAY__' => 'DiffusionSubstitutionDate',
		'__DAY_TEXT__' => 'DiffusionSubstitutionDate',
		'__DAY_TEXT_SHORT__' => 'DiffusionSubstitutionDate',
		'__DAY_TEXT_MIN__' => 'DiffusionSubstitutionDate',
		'__MONTH__' => 'DiffusionSubstitutionDate',
		'__MONTH_TEXT__' => 'DiffusionSubstitutionDate',
		'__MONTH_TEXT_SHORT__' => 'DiffusionSubstitutionDate',
		'__MONTH_TEXT_MIN__' => 'DiffusionSubstitutionDate',
		'__YEAR__' => 'DiffusionSubstitutionDate',
		'__YEAR_PREVIOUS_MONTH__' => 'DiffusionSubstitutionDate',
		'__YEAR_NEXT_MONTH__' => 'DiffusionSubstitutionDate',
		'__PREVIOUS_DAY__' => 'DiffusionSubstitutionDate',
		'__PREVIOUS_MONTH__' => 'DiffusionSubstitutionDate',
		'__PREVIOUS_MONTH_TEXT__' => 'DiffusionSubstitutionDate',
		'__PREVIOUS_MONTH_TEXT_SHORT__' => 'DiffusionSubstitutionDate',
		'__PREVIOUS_MONTH_TEXT_MIN__' => 'DiffusionSubstitutionDate',
		'__PREVIOUS_YEAR__' => 'DiffusionSubstitutionDate',
		'__NEXT_DAY__' => 'DiffusionSubstitutionDate',
		'__NEXT_MONTH__' => 'DiffusionSubstitutionDate',
		'__NEXT_MONTH_TEXT__' => 'DiffusionSubstitutionDate',
		'__NEXT_MONTH_TEXT_SHORT__' => 'DiffusionSubstitutionDate',
		'__NEXT_MONTH_TEXT_MIN__' => 'DiffusionSubstitutionDate',
		'__NEXT_YEAR__' => 'DiffusionSubstitutionDate',
		'__ENTITY_ID__' => 'DiffusionSubstitutionSystem',
		'__DOL_MAIN_URL_ROOT__' => 'DiffusionSubstitutionSystem',
		'__(AnyTranslationKey)__' => 'DiffusionSubstitutionTranslationKey',
		'__(AnyTranslationKey|langfile)__' => 'DiffusionSubstitutionTranslationLangfileKey',
		'__[AnyConstantKey]__' => 'DiffusionSubstitutionConstantKey',
		'__DIFFUSION_DESCRIPTION__' => 'DiffusionSubstitutionDiffusionDescription',
		'__DIFFUSION_STATUS__' => 'DiffusionSubstitutionDiffusionStatus',
		'__DIFFUSION_URL__' => 'DiffusionSubstitutionDiffusionUrl',
		'__DIFFUSION_THIRDPARTY_NAME__' => 'DiffusionSubstitutionDiffusionThirdpartyName',
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
