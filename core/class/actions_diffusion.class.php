<?php
/* Copyright (C) 2026	Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
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
 * \file		class/actions_diffusion.class.php
 * \ingroup	diffusion
 * \brief		Hooks for Diffusion module
 */

/**
 * Class ActionsDiffusion
 */
class ActionsDiffusion
{
	/** @var string Identifier used by Multicompany external sharing payload */
	public const MULTICOMPANY_SHARING_ROOT_KEY = 'diffusion';

	/** @var string Email template type for manual sending from diffusion cards and lists */
	public const EMAIL_TEMPLATE_TYPE_MANUAL = 'diffusion';

	/** @var string Email template type for diffusion business notifications */
	public const EMAIL_TEMPLATE_TYPE_DIFFUSION = 'diffusiondoc@diffusion';

	/** @var string Email template type for diffusion contact business notifications */
	public const EMAIL_TEMPLATE_TYPE_DIFFUSIONCONTACT = 'diffusioncontact@diffusion';

	/** @var DoliDB Database handler */
	public $db;

	/** @var string Error */
	public $error = '';

	/** @var array<string> Errors */
	public $errors = array();

	/** @var string Output */
	public $resprints;

	/** @var array<string,mixed> Hook results */
	public $results = array();

	/**
	 * Return the centralized list of business events exposed by the module.
	 *
	 * @return array<string,array<string,int|string>>
	 */
	public static function getBusinessEventsDefinition()
	{
		return array(
			'DIFFUSION_CREATE' => array('label' => 'DiffusionTriggerLabelCreate', 'description' => 'DiffusionTriggerDescCreate', 'rang' => 2000, 'elementtype' => self::EMAIL_TEMPLATE_TYPE_DIFFUSION),
			'DIFFUSION_VALIDATE' => array('label' => 'DiffusionTriggerLabelValidate', 'description' => 'DiffusionTriggerDescValidate', 'rang' => 2001, 'elementtype' => self::EMAIL_TEMPLATE_TYPE_DIFFUSION),
			'DIFFUSION_SENDMAIL' => array('label' => 'DiffusionTriggerLabelSendMail', 'description' => 'DiffusionTriggerDescSendMail', 'rang' => 2002, 'elementtype' => self::EMAIL_TEMPLATE_TYPE_DIFFUSION),
			'DIFFUSION_SETDIFFUSED' => array('label' => 'DiffusionTriggerLabelSetDiffused', 'description' => 'DiffusionTriggerDescSetDiffused', 'rang' => 2003, 'elementtype' => self::EMAIL_TEMPLATE_TYPE_DIFFUSION),
			'DIFFUSION_BACKTODRAFT' => array('label' => 'DiffusionTriggerLabelBackToDraft', 'description' => 'DiffusionTriggerDescBackToDraft', 'rang' => 2004, 'elementtype' => self::EMAIL_TEMPLATE_TYPE_DIFFUSION),
			'DIFFUSION_DELETE' => array('label' => 'DiffusionTriggerLabelDelete', 'description' => 'DiffusionTriggerDescDelete', 'rang' => 2005, 'elementtype' => self::EMAIL_TEMPLATE_TYPE_DIFFUSION),
			'DIFFUSION_CANCEL' => array('label' => 'DiffusionTriggerLabelCancel', 'description' => 'DiffusionTriggerDescCancel', 'rang' => 2006, 'elementtype' => self::EMAIL_TEMPLATE_TYPE_DIFFUSION),
			'DIFFUSION_REOPEN' => array('label' => 'DiffusionTriggerLabelReopen', 'description' => 'DiffusionTriggerDescReopen', 'rang' => 2007, 'elementtype' => self::EMAIL_TEMPLATE_TYPE_DIFFUSION),
			'DIFFUSION_DIFFUSION_MODIFY' => array('label' => 'DiffusionTriggerLabelModify', 'description' => 'DiffusionTriggerDescModify', 'rang' => 2008, 'elementtype' => self::EMAIL_TEMPLATE_TYPE_DIFFUSION),
			'DIFFUSIONCONTACT_INSERT' => array('label' => 'DiffusionContactTriggerLabelInsert', 'description' => 'DiffusionContactTriggerDescInsert', 'rang' => 2010, 'elementtype' => self::EMAIL_TEMPLATE_TYPE_DIFFUSIONCONTACT),
			'DIFFUSIONCONTACT_DELETELINE' => array('label' => 'DiffusionContactTriggerLabelDeleteLine', 'description' => 'DiffusionContactTriggerDescDeleteLine', 'rang' => 2011, 'elementtype' => self::EMAIL_TEMPLATE_TYPE_DIFFUSIONCONTACT),
			'DIFFUSIONCONTACT_UPDATELINE' => array('label' => 'DiffusionContactTriggerLabelUpdateLine', 'description' => 'DiffusionContactTriggerDescUpdateLine', 'rang' => 2012, 'elementtype' => self::EMAIL_TEMPLATE_TYPE_DIFFUSIONCONTACT),
			'DIFFUSIONCONTACT_DELETEALL' => array('label' => 'DiffusionContactTriggerLabelDeleteAll', 'description' => 'DiffusionContactTriggerDescDeleteAll', 'rang' => 2013, 'elementtype' => self::EMAIL_TEMPLATE_TYPE_DIFFUSIONCONTACT),
		);
	}

	/**
	 * Return one business event definition.
	 *
	 * @param string $code Business trigger code
	 * @return array<string,int|string>
	 */
	public static function getBusinessEventDefinition($code)
	{
		$events = self::getBusinessEventsDefinition();
		return !empty($events[$code]) ? $events[$code] : array();
	}

	/**
	 * Return business event codes supported by native notifications.
	 *
	 * @return string[]
	 */
	public static function getNotificationEventCodes()
	{
		return array_keys(self::getBusinessEventsDefinition());
	}

	/**
	 * Return email template types exposed by the module.
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function getEmailTemplateTypes()
	{
		return array(
			self::EMAIL_TEMPLATE_TYPE_MANUAL => array('label' => 'MailToSendDiffusion', 'picto' => 'fa-paper-plane'),
			self::EMAIL_TEMPLATE_TYPE_DIFFUSION => array('label' => 'MailToNotifyDiffusion', 'picto' => 'fa-paper-plane'),
			self::EMAIL_TEMPLATE_TYPE_DIFFUSIONCONTACT => array('label' => 'MailToNotifyDiffusionContact', 'picto' => 'fa-address-book'),
		);
	}

	/**
	 * Return default email templates to create at module activation.
	 *
	 * @return array<string,array<string,int|string>>
	 */
	public static function getDefaultEmailTemplatesDefinition()
	{
		return array(
			'DIFFUSION_MANUAL_SEND' => array(
				'type_template' => self::EMAIL_TEMPLATE_TYPE_MANUAL,
				'label' => 'DiffusionEmailTemplateManualLabel',
				'topic' => 'DiffusionEmailTemplateManualTopic',
				'content' => 'DiffusionEmailTemplateManualContent',
				'position' => 100,
				'joinfiles' => 1,
			),
			'DIFFUSION_NOTIFICATION' => array(
				'type_template' => self::EMAIL_TEMPLATE_TYPE_DIFFUSION,
				'label' => 'DiffusionEmailTemplateNotificationLabel',
				'topic' => 'DiffusionEmailTemplateNotificationTopic',
				'content' => 'DiffusionEmailTemplateNotificationContent',
				'position' => 110,
				'joinfiles' => 0,
			),
			'DIFFUSIONCONTACT_NOTIFICATION' => array(
				'type_template' => self::EMAIL_TEMPLATE_TYPE_DIFFUSIONCONTACT,
				'label' => 'DiffusionContactEmailTemplateNotificationLabel',
				'topic' => 'DiffusionContactEmailTemplateNotificationTopic',
				'content' => 'DiffusionContactEmailTemplateNotificationContent',
				'position' => 120,
				'joinfiles' => 0,
			),
		);
	}

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		dol_syslog(__METHOD__ . " hook class initialized from class/actions_diffusion.class.php", LOG_DEBUG);
	}

	/**
	 * Build the Multicompany sharing payload for the module.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function getMulticompanySharingDefinition()
	{
		global $conf;

		return array(
			self::MULTICOMPANY_SHARING_ROOT_KEY => array(
				'sharingelements' => array(
					'diffusion' => array(
						'type' => 'element',
						'icon' => 'paper-plane',
						'lang' => 'diffusion@diffusion',
						'tooltip' => 'ShareDiffusionTooltip',
						'enable' => '!empty($conf->diffusion->enabled)',
						'input' => array(
							'global' => array(
								'showhide' => true,
								'hide' => true,
								'del' => true,
							),
						),
					),
					'diffusionnumbering' => array(
						'type' => 'objectnumber',
						'icon' => 'cogs',
						'lang' => 'diffusion@diffusion',
						'tooltip' => 'ShareDiffusionNumberingTooltip',
						'enable' => '!empty($conf->diffusion->enabled)',
						'input' => array(
							'global' => array(
								'hide' => true,
								'del' => true,
							),
						),
					),
				),
				'sharingmodulename' => array(
					'diffusion' => 'diffusion',
					'diffusionnumbering' => 'diffusion',
				),
			),
		);
	}

	/**
	 * Register sharing definition for dedicated multicompany hook contexts.
	 *
	 * @return void
	 */
	private function registerMulticompanySharingDefinition()
	{
		global $langs;

		$langs->loadLangs(array('diffusion@diffusion'));
		$this->results = array_replace_recursive($this->results, self::getMulticompanySharingDefinition());
	}

	/**
	 * Provide sharing options through multicompany external module hook.
	 *
	 * @param array<string,mixed> $parameters Hook parameters
	 * @param CommonObject $object Current object
	 * @param string $action Current action
	 * @param HookManager $hookmanager Hook manager propagated
	 * @return int
	 */
	public function multicompanyExternalModulesSharing($parameters, &$object, &$action, $hookmanager)
	{
		$this->registerMulticompanySharingDefinition();

		return 0;
	}

	/**
	 * Backward-compatible alias for multicompany sharing hook name.
	 *
	 * @param array<string,mixed> $parameters Hook parameters
	 * @param CommonObject $object Current object
	 * @param string $action Current action
	 * @param HookManager $hookmanager Hook manager propagated
	 * @return int
	 */
	public function multicompanyExternalModuleSharing($parameters, &$object, &$action, $hookmanager)
	{
		$this->registerMulticompanySharingDefinition();

		return 0;
	}

	/**
	 * Additional alias for broad multicompany sharing options requests.
	 *
	 * @param array<string,mixed> $parameters Hook parameters
	 * @param CommonObject $object Current object
	 * @param string $action Current action
	 * @param HookManager $hookmanager Hook manager propagated
	 * @return int
	 */
	public function multicompanySharingOptions($parameters, &$object, &$action, $hookmanager)
	{
		$this->registerMulticompanySharingDefinition();

		return 0;
	}

	/**
	 * Add DIFFUSION entry into email templates element list.
	 *
	 * @param array<string,mixed>	$parameters Hook parameters
	 * @param CommonObject			$object	Current object
	 * @param string				$action	Current action
	 * @param HookManager			$hookmanager Hook manager propagated
	 * @return int
	 */
	public function emailElementlist($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;

		$langs->load('diffusion@diffusion');

		$this->results = array();
		foreach (self::getEmailTemplateTypes() as $type => $typeconf) {
			$picto = !empty($typeconf['picto']) ? $typeconf['picto'] : 'email';
			$label = !empty($typeconf['label']) ? $typeconf['label'] : $type;
			$this->results[$type] = img_picto('', $picto, 'class="pictofixedwidth"') . dol_escape_htmltag($langs->trans($label));
		}

		return 0;
	}

	/**
	 * Inject Diffusion entry into the quick add dropdown menu.
	 *
	 * @param array<string,mixed>	$parameters Hook parameters
	 * @param CommonObject			$object	Current object
	 * @param string				$action	Current action
	 * @param HookManager			$hookmanager Hook manager propagated
	 * @return int
	 */
	public function menuDropdownQuickaddItems($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $user;

		$this->results = array();
		$this->resprints = '';

		$langs->loadLangs(array('diffusion@diffusion'));

		$hasWriteRight = $user->hasRight('diffusion', 'diffusiondoc', 'write') || $user->hasRight('diffusion', 'diffusion', 'write') || $user->hasRight('diffusion', 'write');

		$this->results[0] = array(
			'url' => '/custom/diffusion/diffusion_card.php?action=create',
			'title' => 'QuickCreateDiffusion@diffusion',
			'name' => 'Diffusion@diffusion',
			'picto' => 'fa-paper-plane',
			'activation' => isModEnabled('diffusion') && $hasWriteRight,
			'position' => 100,
		);

		return 0;
	}

	/**
	 * Return diffusion count linked to project.
	 *
	 * @param int $projectid Project id
	 * @return int
	 */
	private function getDiffusionCountByProject($projectid)
	{
		$projectid = (int) $projectid;

		$sql = "SELECT COUNT(t.rowid) as nb";
		$sql .= " FROM " . MAIN_DB_PREFIX . "diffusion as t";
		$sql .= " WHERE t.fk_project = " . $projectid;
		$sql .= " AND t.entity IN (" . getEntity('diffusion') . ")";

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(__METHOD__ . " sql failed error=" . $this->db->lasterror(), LOG_ERR);
			$this->errors[] = $this->db->lasterror();
			return -1;
		}

		$obj = $this->db->fetch_object($resql);
		$nbdiffusions = (int) (!empty($obj->nb) ? $obj->nb : 0);
		$this->db->free($resql);

		return $nbdiffusions;
	}

	/**
	 * Check if user can read diffusion objects.
	 *
	 * @param User $user Current user
	 * @return bool
	 */
	private function userCanReadDiffusion($user)
	{
		if (!is_object($user)) {
			return false;
		}

		return (!empty($user->admin)
			|| $user->hasRight('diffusion', 'diffusiondoc', 'read')
			|| $user->hasRight('diffusion', 'diffusion', 'read')
			|| $user->hasRight('diffusion', 'read'));
	}

	/**
	 * Check if user can write diffusion objects.
	 *
	 * @param User $user Current user
	 * @return bool
	 */
	private function userCanWriteDiffusion($user)
	{
		if (!is_object($user)) {
			return false;
		}

		return (!empty($user->admin)
			|| $user->hasRight('diffusion', 'diffusiondoc', 'write')
			|| $user->hasRight('diffusion', 'diffusion', 'write')
			|| $user->hasRight('diffusion', 'write'));
	}

	/**
	 * Complete project tabs head to include diffusion count on overview tab.
	 *
	 * @param array<string,mixed>	$parameters Hook parameters
	 * @param CommonObject			$object	Current object
	 * @param string				$action	Current action
	 * @param HookManager			$hookmanager Hook manager propagated
	 * @return int
	 */
	public function completeTabsHead(&$parameters, &$object, &$action, $hookmanager)
	{
		$objectType = !empty($parameters['type']) ? (string) $parameters['type'] : '';
		if ($objectType !== 'project') {
			return 0;
		}
		if (empty($object) || empty($object->id)) {
			return 0;
		}
		if (empty($parameters['head']) || !is_array($parameters['head'])) {
			return 0;
		}

		$nbdiffusions = $this->getDiffusionCountByProject((int) $object->id);
		if ($nbdiffusions <= 0) {
			return 0;
		}

		$updated = false;
		foreach ($parameters['head'] as $tabKey => $tab) {
			if (!is_array($tab) || empty($tab[2]) || $tab[2] !== 'element') {
				continue;
			}

			$tabLabel = isset($tab[1]) ? (string) $tab[1] : '';
			if (strpos($tabLabel, 'badge-diffusion-merged') !== false || strpos($tabLabel, 'badge-diffusion-added') !== false) {
				continue;
			}
			if (preg_match('/(<span class=")([^"]*badge[^"]*)(">)([0-9]+)(<\/span>)/', $tabLabel, $matches)) {
				$newValue = ((int) $matches[4]) + $nbdiffusions;
				$newBadgeClasses = trim($matches[2] . ' badge-diffusion-merged');
				$tab[1] = preg_replace('/(<span class=")([^"]*badge[^"]*)(">)([0-9]+)(<\/span>)/', '${1}' . $newBadgeClasses . '${3}' . $newValue . '${5}', $tabLabel, 1);
			} else {
				$tab[1] = $tabLabel . '<span class="badge marginleftonlyshort badge-diffusion-added">' . $nbdiffusions . '</span>';
			}

			$parameters['head'][$tabKey] = $tab;
			$updated = true;
			break;
		}

		if (!$updated) {
			return 0;
		}

		return 0;
	}

	/**
	 * Detect if current referent context is diffusion.
	 *
	 * @param array<string,mixed>	$parameters Hook parameters
	 * @return bool
	 */
	private function isDiffusionReferentContext($parameters)
	{
		$referentKeys = array();

		if (!empty($parameters['key'])) {
			$referentKeys[] = (string) $parameters['key'];
		}
		if (!empty($parameters['element'])) {
			$referentKeys[] = (string) $parameters['element'];
		}
		if (!empty($parameters['objecttype'])) {
			$referentKeys[] = (string) $parameters['objecttype'];
		}
		if (!empty($parameters['type'])) {
			$referentKeys[] = (string) $parameters['type'];
		}

		if (!empty($parameters['value']) && is_array($parameters['value'])) {
			$value = $parameters['value'];
			if (!empty($value['table'])) {
				$referentKeys[] = (string) $value['table'];
			}
			if (!empty($value['class'])) {
				$referentKeys[] = (string) $value['class'];
			}
			if (!empty($value['name'])) {
				$referentKeys[] = (string) $value['name'];
			}
		}

		$allowedValues = array('diffusion', 'Diffusion');
		foreach ($referentKeys as $key) {
			if (in_array($key, $allowedValues, true)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add diffusion entry in project overview referents list.
	 *
	 * @param array		$parameters Hook parameters
	 * @param CommonObject	$object	Current object
	 * @param string		$action	Current action
	 * @param HookManager	$hookmanager Hook manager propagated
	 * @return int
	 */
	public function completeListOfReferent($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $user;

		dol_syslog(__METHOD__ . " called context=" . (is_object($object) && isset($object->element) ? $object->element : 'none') . " action=" . $action, LOG_WARNING);

		if (empty($object) || $object->element !== 'project') {
			dol_syslog(__METHOD__ . " skip: not a project context", LOG_DEBUG);
			return 0;
		}
		$canReadDiffusion = $this->userCanReadDiffusion($user);
		if (empty($canReadDiffusion)) {
			dol_syslog(__METHOD__ . " skip: missing read right for user id=" . ((int) $user->id), LOG_DEBUG);
			return 0;
		}


		$langs->load('diffusion@diffusion');
		dol_include_once('/diffusion/class/diffusion.class.php');

		$this->results = array(
			'diffusion' => array(
				'name' => $langs->trans('Diffusion'),
				'title' => $langs->trans('DiffusionsLieesAuProjet'),
				'class' => 'Diffusion',
				'table' => 'diffusion',
				'project_field' => 'fk_project',
				'datefieldname' => 'date_expedition',
				'margin' => 'minus',
				'disableamount' => 1,
				'urlnew' => DOL_URL_ROOT . '/custom/diffusion/diffusion_card.php?action=create&projectid=' . (int) $object->id,
				'lang' => 'diffusion',
				'buttonnew' => $langs->trans('NewDiffusion'),
				'testnew' => ($this->userCanWriteDiffusion($user)),
				'test' => ($this->userCanReadDiffusion($user)),
			),
		);

		dol_syslog(__METHOD__ . " referent registered for project id=" . ((int) $object->id), LOG_WARNING);

		return 1;
	}

	/**
	 * Render overview detail block for project card.
	 *
	 * @param array		$parameters Hook parameters
	 * @param CommonObject	$object	Current object
	 * @param string		$action	Current action
	 * @param HookManager	$hookmanager Hook manager propagated
	 * @return int
	 */
	public function printOverviewDetail($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $user, $conf;

		dol_syslog(__METHOD__ . " called context=" . (is_object($object) && isset($object->element) ? $object->element : 'none') . " action=" . $action, LOG_WARNING);

		if (empty($object) || $object->element !== 'project') {
			dol_syslog(__METHOD__ . " skip: not a project context", LOG_DEBUG);
			return 0;
		}
		$canReadDiffusion = $this->userCanReadDiffusion($user);
		if (empty($canReadDiffusion)) {
			dol_syslog(__METHOD__ . " skip: missing read right for user id=" . ((int) $user->id), LOG_DEBUG);
			return 0;
		}
		$hasReferentContext = !empty($parameters['key']) || !empty($parameters['element']) || !empty($parameters['objecttype']) || !empty($parameters['type']);
		if (!empty($parameters['value']) && is_array($parameters['value'])) {
			$hasReferentContext = $hasReferentContext || !empty($parameters['value']['table']) || !empty($parameters['value']['class']) || !empty($parameters['value']['name']);
		}
		if ($hasReferentContext && !$this->isDiffusionReferentContext($parameters)) {
			dol_syslog(__METHOD__ . " skip: unmanaged referent context", LOG_DEBUG);
			return 0;
		}

		$langs->load('diffusion@diffusion');
		dol_include_once('/diffusion/class/diffusion.class.php');

		$canWriteDiffusion = $this->userCanWriteDiffusion($user);
		if ($action === 'unlinkdiffusionfromproject' && !empty($canWriteDiffusion)) {
			$diffusionId = GETPOSTINT('diffusionid');
			$diffusionunlink = new Diffusion($this->db);
			$resultFetch = $diffusionunlink->fetch($diffusionId);
			if ($resultFetch > 0) {
				$diffusionunlink->setProject(0);
				$resultUnlink = $diffusionunlink->syncProjectObjectLink(0);
				if ($resultUnlink < 0) {
					setEventMessages($diffusionunlink->error, $diffusionunlink->errors, 'errors');
				} else {
					setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
				}
			}
			$queryParams = array();
			$queryString = parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_QUERY);
			if (!empty($queryString)) {
				parse_str($queryString, $queryParams);
			}
			unset($queryParams['action']);
			unset($queryParams['diffusionid']);
			unset($queryParams['token']);
			$redirectUrl = $_SERVER['PHP_SELF'];
			if (!empty($queryParams)) {
				$redirectUrl .= '?' . http_build_query($queryParams);
			}
			$redirectUrl .= '#table_diffusion';
			header('Location: ' . $redirectUrl);
			exit;
		}

		$sql = "SELECT t.rowid, t.ref, t.label, t.date_expedition, t.fk_user_exped, t.status";
		$sql .= ", u.login as user_login, u.firstname as user_firstname, u.lastname as user_lastname";
		$sql .= " FROM " . MAIN_DB_PREFIX . "diffusion as t";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON u.rowid = t.fk_user_exped";
		$sql .= " WHERE t.fk_project = " . ((int) $object->id);
		$sql .= " AND t.entity IN (" . getEntity('diffusion') . ")";
		$sql .= " ORDER BY t.date_expedition DESC, t.rowid DESC";

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(__METHOD__ . " sql failed error=" . $this->db->lasterror(), LOG_ERR);
			$this->errors[] = $this->db->lasterror();
			return -1;
		}

		$referentValue = array();
		if (!empty($parameters['value']) && is_array($parameters['value'])) {
			$referentValue = $parameters['value'];
		}

		$title = $langs->trans('DiffusionsLieesAuProjet');
		if (!empty($referentValue['title'])) {
			$title = $langs->trans($referentValue['title']);
		}

		$urlnew = DOL_URL_ROOT . '/custom/diffusion/diffusion_card.php?action=create&projectid=' . ((int) $object->id);
		if (!empty($referentValue['urlnew'])) {
			$urlnew = (string) $referentValue['urlnew'];
		}
		$buttonTitle = $langs->trans('NewDiffusion');
		if (!empty($referentValue['buttonnew'])) {
			$buttonTitle = $langs->trans($referentValue['buttonnew']);
		}
		$canCreate = $this->userCanWriteDiffusion($user);
		if (array_key_exists('testnew', $referentValue)) {
			$canCreate = !empty($referentValue['testnew']);
		}
		if (strpos($urlnew, 'backtopage=') === false) {
			$backtopage = (string) $_SERVER['REQUEST_URI'];
			if (strpos($backtopage, '#table_diffusion') === false) {
				$backtopage .= '#table_diffusion';
			}
			$urlnew .= (strpos($urlnew, '?') === false ? '?' : '&') . 'backtopage=' . urlencode($backtopage);
		}

		$buttonAdd = '';
		if ($canCreate) {
			$buttonAdd = '<a class="buttonxxx marginleftonly" href="' . dol_escape_htmltag($urlnew) . '" title="' . dol_escape_htmltag($buttonTitle) . '"><span class="fa fa-plus-circle valignmiddle paddingleft"></span></a><div></div>';
		}

		$out = '<a id="table_diffusion"></a>';
		$out .= '<table class="centpercent notopnoleftnoright table-fiche-title"><tbody><tr class="toptitle">';
		$out .= '<td class="nobordernopadding valignmiddle col-title"><div class="titre inline-block"><span class="inline-block valignmiddle">' . $title . '</span></div></td>';
		$out .= '<td class="nobordernopadding titre_right wordbreakimp right valignmiddle col-right"><div class="inline-block valignmiddle">' . $buttonAdd . '</div></td>';
		$out .= '</tr></tbody></table>';
		$out .= '<div class="div-table-responsive-no-min">';
		$out .= '<table class="tagtable liste noborder centpercent">';
		$out .= '<tr class="liste_titre">';
		$out .= '<td style="width: 24px"></td>';
		$out .= '<td>' . $langs->trans('Ref') . '</td>';
		$out .= '<td>' . $langs->trans('Label') . '</td>';
		$out .= '<td class="center">' . $langs->trans('DateEnvoi') . '</td>';
		$out .= '<td>' . $langs->trans('UserExpedition') . '</td>';
		$out .= '<td class="right">' . $langs->trans('Status') . '</td>';
		$out .= '</tr>';

		$diffusionstatic = new Diffusion($this->db);
		$num = $this->db->num_rows($resql);

		if ($num <= 0) {
			$out .= '<tr><td colspan="6"><span class="opacitymedium">' . $langs->trans('NoDiffusionsForProject') . '</span></td></tr>';
		} else {
			while ($obj = $this->db->fetch_object($resql)) {
				$diffusionstatic->id = (int) $obj->rowid;
				$diffusionstatic->ref = $obj->ref;
				$diffusionstatic->status = (int) $obj->status;
				$expeditionDate = '';
				if (!empty($obj->date_expedition)) {
					$expeditionDate = dol_print_date($this->db->jdate($obj->date_expedition), 'dayhour');
				}
				$expeditor = '';
				if (!empty($obj->fk_user_exped)) {
					$expeditor = trim($obj->user_firstname . ' ' . $obj->user_lastname);
					if (empty($expeditor)) {
						$expeditor = $obj->user_login;
					}
				}

				$unlinkButton = '';
				if ($this->userCanWriteDiffusion($user)) {
					$urlunlink = $_SERVER['PHP_SELF'] . '?id=' . ((int) $object->id) . '&action=unlinkdiffusionfromproject&diffusionid=' . ((int) $obj->rowid) . '&token=' . newToken() . '#table_diffusion';
					$unlinkButton = '<a href="' . dol_escape_htmltag($urlunlink) . '" class="reposition"><span class="fas fa-unlink" title="' . dol_escape_htmltag($langs->trans('Unlink')) . '"></span></a>';
				}

				$out .= '<tr class="oddeven">';
				$out .= '<td style="width: 24px">' . $unlinkButton . '</td>';
				$out .= '<td>' . $diffusionstatic->getNomUrl(1) . '</td>';
				$out .= '<td>' . dol_escape_htmltag((string) $obj->label) . '</td>';
				$out .= '<td class="center">' . $expeditionDate . '</td>';
				$out .= '<td>' . dol_escape_htmltag($expeditor) . '</td>';
				$out .= '<td class="right">' . $diffusionstatic->getLibStatut(5) . '</td>';
				$out .= '</tr>';
			}
		}

		if ($num > 0) {
			$out .= '<tr class="liste_total">';
			$out .= '<td colspan="2">' . $langs->trans('Number') . ': ' . $num . '</td>';
			$out .= '<td>&nbsp;</td>';
			$out .= '<td>&nbsp;</td>';
			$out .= '<td>&nbsp;</td>';
			$out .= '<td>&nbsp;</td>';
			$out .= '</tr>';
		}

		$this->db->free($resql);

		$out .= '</table>';
		$out .= '</div>';
		$this->resprints .= $out;

		dol_syslog(__METHOD__ . " rendered detail table for project id=" . ((int) $object->id) . " rows=" . $num, LOG_WARNING);

		return 1;
	}


	/**
	 * Add DIFFUSION events to notification managed events list.
	 *
	 * @param array<string,mixed>	$parameters Hook parameters
	 * @param CommonObject			$object	Current object
	 * @param string				$action	Current action
	 * @param HookManager			$hookmanager Hook manager propagated
	 * @return int
	 */
	public function notifsupported($parameters, &$object, &$action, $hookmanager)
	{
		global $conf;

		$notificationElementAliases = array('diffusion', 'diffusiondoc', self::EMAIL_TEMPLATE_TYPE_DIFFUSION, 'diffusioncontact', self::EMAIL_TEMPLATE_TYPE_DIFFUSIONCONTACT);
		foreach ($notificationElementAliases as $alias) {
			if (empty($conf->{$alias}) || !is_object($conf->{$alias})) {
				$conf->{$alias} = new stdClass();
			}
			$conf->{$alias}->enabled = !empty($conf->diffusion->enabled) ? 1 : 0;
		}

		$events = self::getNotificationEventCodes();

		if (!empty($hookmanager->resArray['arrayofnotifsupported']) && is_array($hookmanager->resArray['arrayofnotifsupported'])) {
			$events = array_merge($hookmanager->resArray['arrayofnotifsupported'], $events);
		}

		$this->results = array('arrayofnotifsupported' => array_values(array_unique($events)));

		return 0;
	}


	/**
	 * Render profit line for project overview.
	 *
	 * @param array<string,mixed>	$parameters Hook parameters
	 * @param CommonObject			$project	Current project
	 * @param string				$action		Current action
	 * @param HookManager			$hookmanager Hook manager propagated
	 * @return int
	 */
	public function printOverviewProfit($parameters, &$project, &$action, $hookmanager)
	{
		global $db, $langs, $form;

		dol_syslog(__METHOD__ . " called projectid=" . ((int) $project->id) . " action=" . $action, LOG_DEBUG);

		if (!$this->isDiffusionReferentContext($parameters)) {
			dol_syslog(__METHOD__ . " skip unmanaged referent context", LOG_DEBUG);
			return 0;
		}

		$value = &$parameters['value'];
		dol_syslog(__METHOD__ . " datefieldname=" . (!empty($value['datefieldname']) ? $value['datefieldname'] : 'undefined'), LOG_DEBUG);
		$fk_project = (int) $project->id;
		$dates = $parameters['dates'] ?? null;
		$datee = $parameters['datee'] ?? null;

		$sql = "SELECT COUNT(rowid) as nb";
		$sql .= " FROM " . MAIN_DB_PREFIX . "diffusion";
		$sql .= " WHERE entity IN (" . getEntity('diffusion') . ")";
		$sql .= " AND fk_project = " . $fk_project;

		if (!empty($dates)) {
			$sql .= " AND " . $value['datefieldname'] . " >= '" . $db->idate((int) $dates) . "'";
		}
		if (!empty($datee)) {
			$sql .= " AND " . $value['datefieldname'] . " <= '" . $db->idate((int) $datee) . "'";
		}

		dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);
		$resql = $db->query($sql);
		if (!$resql) {
			dol_syslog(__METHOD__ . " sql error=" . $db->lasterror(), LOG_ERR);
			return -1;
		}

		$obj = $db->fetch_object($resql);
		$nb = (int) ($obj->nb ?? 0);
		$db->free($resql);

		dol_syslog(__METHOD__ . " found nb=" . $nb, LOG_DEBUG);

		$name = $langs->trans($value['name']);
		$nameLink = '<a href="#table_diffusion">' . dol_escape_htmltag($name) . '</a>';
		$na = '<span class="opacitymedium">' . $form->textwithpicto($langs->trans("NA"), $langs->trans("NoAmountForThisElement")) . '</span>';

		$out = '<tr class="oddeven">';
		$out .= '<td class="left">' . $nameLink . '</td>';
		$out .= '<td class="right">' . $nb . '</td>';
		$out .= '<td class="right">' . $na . '</td>';
		$out .= '<td class="right">' . $na . '</td>';
		$out .= '</tr>';

		$this->resprints = $out;
		dol_syslog(__METHOD__ . " rendered projectid=" . $fk_project . " nb=" . $nb, LOG_DEBUG);

		return 1;
	}

}
