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

	/** @var string Legacy visible email template type used before module picto alignment */
	public const EMAIL_TEMPLATE_TYPE_LEGACY_DIFFUSION = 'diffusion';

	/** @var string Email template type for manual sending from diffusion cards and lists */
	public const EMAIL_TEMPLATE_TYPE_MANUAL = 'diffusion@diffusion';

	/** @var string Single visible email template type for all Diffusion module emails */
	public const EMAIL_TEMPLATE_TYPE_NOTIFICATION = 'diffusion@diffusion';

	/** @var string Hidden Dolibarr notification template type for Diffusion objects */
	public const EMAIL_TEMPLATE_TYPE_DIFFUSION_SEND = 'diffusiondoc_send';

	/** @var string Hidden Dolibarr notification template type for DiffusionContact objects */
	public const EMAIL_TEMPLATE_TYPE_DIFFUSIONCONTACT_SEND = 'diffusioncontact_send';

	/** @var string Agenda link element type for Diffusion objects */
	public const AGENDA_ELEMENTTYPE_DIFFUSION = 'diffusiondoc@diffusion';

	/** @var string Agenda link element type for DiffusionContact objects */
	public const AGENDA_ELEMENTTYPE_DIFFUSIONCONTACT = 'diffusioncontact@diffusion';

	/** @var DoliDB Database handler */
	public $db;

	/** @var string Error */
	public $error = '';

	/** @var array<string> Errors */
	public $errors = array();

	/** @var array<string> Warnings */
	public $warnings = array();

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
		$diffusion = array(
			'notification_elementtype' => self::EMAIL_TEMPLATE_TYPE_NOTIFICATION,
			'agenda_elementtype' => self::AGENDA_ELEMENTTYPE_DIFFUSION,
		);
		$diffusioncontact = array(
			'notification_elementtype' => self::EMAIL_TEMPLATE_TYPE_NOTIFICATION,
			'agenda_elementtype' => self::AGENDA_ELEMENTTYPE_DIFFUSIONCONTACT,
		);

		return array(
			'DIFFUSION_CREATE' => array_merge(array('label' => 'DiffusionTriggerLabelCreate', 'description' => 'DiffusionTriggerDescCreate', 'rang' => 2000), $diffusion),
			'DIFFUSION_VALIDATE' => array_merge(array('label' => 'DiffusionTriggerLabelValidate', 'description' => 'DiffusionTriggerDescValidate', 'rang' => 2001), $diffusion),
			'DIFFUSION_SENDMAIL' => array_merge(array('label' => 'DiffusionTriggerLabelSendMail', 'description' => 'DiffusionTriggerDescSendMail', 'rang' => 2002), $diffusion),
			'DIFFUSION_SETDIFFUSED' => array_merge(array('label' => 'DiffusionTriggerLabelSetDiffused', 'description' => 'DiffusionTriggerDescSetDiffused', 'rang' => 2003), $diffusion),
			'DIFFUSION_BACKTODRAFT' => array_merge(array('label' => 'DiffusionTriggerLabelBackToDraft', 'description' => 'DiffusionTriggerDescBackToDraft', 'rang' => 2004), $diffusion),
			'DIFFUSION_DELETE' => array_merge(array('label' => 'DiffusionTriggerLabelDelete', 'description' => 'DiffusionTriggerDescDelete', 'rang' => 2005), $diffusion),
			'DIFFUSION_CANCEL' => array_merge(array('label' => 'DiffusionTriggerLabelCancel', 'description' => 'DiffusionTriggerDescCancel', 'rang' => 2006), $diffusion),
			'DIFFUSION_REOPEN' => array_merge(array('label' => 'DiffusionTriggerLabelReopen', 'description' => 'DiffusionTriggerDescReopen', 'rang' => 2007), $diffusion),
			'DIFFUSION_DIFFUSION_MODIFY' => array_merge(array('label' => 'DiffusionTriggerLabelModify', 'description' => 'DiffusionTriggerDescModify', 'rang' => 2008), $diffusion),
			'DIFFUSIONCONTACT_INSERT' => array_merge(array('label' => 'DiffusionContactTriggerLabelInsert', 'description' => 'DiffusionContactTriggerDescInsert', 'rang' => 2010), $diffusioncontact),
			'DIFFUSIONCONTACT_DELETELINE' => array_merge(array('label' => 'DiffusionContactTriggerLabelDeleteLine', 'description' => 'DiffusionContactTriggerDescDeleteLine', 'rang' => 2011), $diffusioncontact),
			'DIFFUSIONCONTACT_UPDATELINE' => array_merge(array('label' => 'DiffusionContactTriggerLabelUpdateLine', 'description' => 'DiffusionContactTriggerDescUpdateLine', 'rang' => 2012), $diffusioncontact),
			'DIFFUSIONCONTACT_DELETEALL' => array_merge(array('label' => 'DiffusionContactTriggerLabelDeleteAll', 'description' => 'DiffusionContactTriggerDescDeleteAll', 'rang' => 2013), $diffusioncontact),
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
	 * Force native notification trigger rows to use the single visible Diffusion element type.
	 *
	 * @param DoliDB $db Database handler
	 * @return int<-1,1>
	 */
	public static function repairNotificationActionTriggerElementTypes($db)
	{
		$codes = array();
		foreach (self::getNotificationEventCodes() as $code) {
			$codes[] = "'".$db->escape($code)."'";
		}
		if (empty($codes)) {
			return 1;
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."c_action_trigger";
		$sql .= " SET elementtype = '".$db->escape(self::EMAIL_TEMPLATE_TYPE_NOTIFICATION)."'";
		$sql .= " WHERE code IN (".implode(',', $codes).")";

		return $db->query($sql) ? 1 : -1;
	}

	/**
	 * Return email template types exposed by the module.
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function getEmailTemplateTypes()
	{
		return array(
			self::EMAIL_TEMPLATE_TYPE_MANUAL => array('label' => 'MailToSendDiffusion', 'picto' => 'diffusion@diffusion'),
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
				'type_template' => self::EMAIL_TEMPLATE_TYPE_NOTIFICATION,
				'label' => 'DiffusionEmailTemplateNotificationLabel',
				'topic' => 'DiffusionEmailTemplateNotificationTopic',
				'content' => 'DiffusionEmailTemplateNotificationContent',
				'position' => 110,
				'joinfiles' => 0,
			),
			'DIFFUSIONCONTACT_NOTIFICATION' => array(
				'type_template' => self::EMAIL_TEMPLATE_TYPE_NOTIFICATION,
				'label' => 'DiffusionContactEmailTemplateNotificationLabel',
				'topic' => 'DiffusionContactEmailTemplateNotificationTopic',
				'content' => 'DiffusionContactEmailTemplateNotificationContent',
				'position' => 120,
				'joinfiles' => 0,
			),
		);
	}

	/**
	 * Return legacy visible email template types that must be folded into the single Diffusion type.
	 *
	 * @return string[]
	 */
	public static function getLegacyVisibleEmailTemplateTypes()
	{
		return array(
			self::EMAIL_TEMPLATE_TYPE_LEGACY_DIFFUSION,
			self::AGENDA_ELEMENTTYPE_DIFFUSION,
			self::AGENDA_ELEMENTTYPE_DIFFUSIONCONTACT,
		);
	}

	/**
	 * Return hidden email template types expected by Dolibarr notifications.
	 *
	 * @return string[]
	 */
	public static function getNotificationMirrorEmailTemplateTypes()
	{
		return array(
			self::EMAIL_TEMPLATE_TYPE_DIFFUSION_SEND,
			self::EMAIL_TEMPLATE_TYPE_DIFFUSIONCONTACT_SEND,
		);
	}

	/**
	 * Migrate legacy visible notification template types into the single visible Diffusion type.
	 *
	 * @param DoliDB $db Database handler
	 * @return int<-1,1>
	 */
	public static function migrateLegacyVisibleEmailTemplateTypes($db)
	{
		$visibletypes = array_merge(array(self::EMAIL_TEMPLATE_TYPE_NOTIFICATION), self::getLegacyVisibleEmailTemplateTypes());
		$visibletypes = array_values(array_unique($visibletypes));

		$quotedtypes = array();
		foreach ($visibletypes as $type) {
			$quotedtypes[] = "'".$db->escape($type)."'";
		}
		if (empty($quotedtypes)) {
			return 1;
		}

		$sql = "SELECT rowid, entity, label, lang, type_template, active, position";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_email_templates";
		$sql .= " WHERE module = 'diffusion'";
		$sql .= " AND type_template IN (".implode(',', $quotedtypes).")";
		$sql .= " ORDER BY entity, label, lang, rowid";

		$resql = $db->query($sql);
		if (!$resql) {
			return -1;
		}

		$groups = array();
		while ($obj = $db->fetch_object($resql)) {
			$row = array(
				'rowid' => (int) $obj->rowid,
				'entity' => (int) $obj->entity,
				'label' => $obj->label,
				'lang' => $obj->lang,
				'type_template' => (string) $obj->type_template,
				'active' => (int) $obj->active,
				'position' => ($obj->position === null ? null : (int) $obj->position),
			);

			$key = self::getEmailTemplateUniqueRuntimeKey((int) $obj->entity, $obj->label, $obj->lang);
			if (empty($groups[$key])) {
				$groups[$key] = array();
			}
			$groups[$key][] = $row;
		}
		$db->free($resql);

		foreach ($groups as $rows) {
			$keeperindex = self::getVisibleEmailTemplateKeeperIndex($rows);

			foreach ($rows as $index => $row) {
				if ($index === $keeperindex) {
					if ($row['type_template'] === self::EMAIL_TEMPLATE_TYPE_NOTIFICATION) {
						continue;
					}

					$sqlupdate = "UPDATE ".MAIN_DB_PREFIX."c_email_templates";
					$sqlupdate .= " SET type_template = '".$db->escape(self::EMAIL_TEMPLATE_TYPE_NOTIFICATION)."'";
					$sqlupdate .= " WHERE rowid = ".((int) $row['rowid']);

					if (!$db->query($sqlupdate)) {
						return -1;
					}

					continue;
				}

				$archivedlabel = self::getLegacyVisibleEmailTemplateArchiveLabel($row['label'], $row['type_template'], (int) $row['rowid']);
				if (self::emailTemplateLabelExists($db, (int) $row['entity'], $archivedlabel, $row['lang'], (int) $row['rowid'])) {
					$archivedlabel = self::getLegacyVisibleEmailTemplateArchiveLabel($row['label'], $row['type_template'].'-'.((int) $row['rowid']), (int) $row['rowid']);
				}

				$sqlupdate = "UPDATE ".MAIN_DB_PREFIX."c_email_templates";
				$sqlupdate .= " SET type_template = '".$db->escape(self::EMAIL_TEMPLATE_TYPE_NOTIFICATION)."'";
				$sqlupdate .= ", label = ".self::sqlNullableString($db, $archivedlabel);
				$sqlupdate .= ", active = 0";
				$sqlupdate .= " WHERE rowid = ".((int) $row['rowid']);

				if (!$db->query($sqlupdate)) {
					return -1;
				}
			}
		}

		return 1;
	}

	/**
	 * Normalize old hidden notification mirrors created without a technical label suffix.
	 *
	 * @param DoliDB $db Database handler
	 * @return int<-1,1>
	 */
	public static function normalizeNotificationEmailTemplateMirrorLabels($db)
	{
		foreach (self::getNotificationMirrorEmailTemplateTypes() as $targettype) {
			$sql = "SELECT rowid, entity, label, lang";
			$sql .= " FROM ".MAIN_DB_PREFIX."c_email_templates";
			$sql .= " WHERE module = 'diffusion'";
			$sql .= " AND type_template = '".$db->escape($targettype)."'";

			$resql = $db->query($sql);
			if (!$resql) {
				return -1;
			}

			while ($obj = $db->fetch_object($resql)) {
				$label = (string) $obj->label;
				$mirrorlabel = self::getNotificationMirrorLabel($label, $targettype);
				if ($label === $mirrorlabel) {
					continue;
				}

				$targetlabel = $mirrorlabel;
				if (self::emailTemplateLabelExists($db, (int) $obj->entity, $targetlabel, $obj->lang, (int) $obj->rowid)) {
					$targetlabel = self::getNotificationMirrorLabel($label, $targettype.'-'.$obj->rowid);
				}

				$sqlupdate = "UPDATE ".MAIN_DB_PREFIX."c_email_templates";
				$sqlupdate .= " SET label = ".self::sqlNullableString($db, $targetlabel);
				$sqlupdate .= " WHERE rowid = ".((int) $obj->rowid);

				if (!$db->query($sqlupdate)) {
					$db->free($resql);
					return -1;
				}
			}

			$db->free($resql);
		}

		return 1;
	}

	/**
	 * Sync the selected visible template for a notification event into hidden Dolibarr notification template types.
	 *
	 * @param DoliDB $db Database handler
	 * @param string $notifcode Notification trigger code
	 * @param CommonObject|null $object Notification object
	 * @return int<-1,1>
	 */
	public static function syncSelectedNotificationEmailTemplateMirrors($db, $notifcode, $object = null)
	{
		if (empty($notifcode) || !in_array($notifcode, self::getNotificationEventCodes(), true)) {
			return 1;
		}

		$label = getDolGlobalString($notifcode.'_TEMPLATE');
		if ($label === '') {
			return 1;
		}
		$visiblelabel = self::getVisibleLabelFromNotificationMirrorLabel($label);

		$resultnormalize = self::normalizeNotificationEmailTemplateMirrorLabels($db);
		if ($resultnormalize < 0) {
			return $resultnormalize;
		}

		$resultmigrate = self::migrateLegacyVisibleEmailTemplateTypes($db);
		if ($resultmigrate < 0) {
			return $resultmigrate;
		}

		$targettype = self::getNotificationMirrorEmailTemplateTypeForContext($notifcode, $object);
		$result = self::syncEmailTemplateMirror($db, $targettype, $visiblelabel);
		if ($result < 0) {
			return $result;
		}
		if ($result === 0) {
			return self::disableNotificationEmailTemplateMirrors($db, $visiblelabel);
		}

		global $conf;
		if (is_object($conf) && !empty($conf->global) && is_object($conf->global)) {
			$conf->global->{$notifcode.'_TEMPLATE'} = self::getNotificationMirrorLabel($visiblelabel, $targettype);
		}

		return 1;
	}

	/**
	 * Return hidden template type expected by Dolibarr for a notification context.
	 *
	 * @param string $notifcode Notification trigger code
	 * @param CommonObject|null $object Notification object
	 * @return string
	 */
	private static function getNotificationMirrorEmailTemplateTypeForContext($notifcode, $object = null)
	{
		if (is_object($object) && !empty($object->element)) {
			$typefromobject = (string) $object->element.'_send';
			if (in_array($typefromobject, self::getNotificationMirrorEmailTemplateTypes(), true)) {
				return $typefromobject;
			}
		}

		if (strpos($notifcode, 'DIFFUSIONCONTACT_') === 0) {
			return self::EMAIL_TEMPLATE_TYPE_DIFFUSIONCONTACT_SEND;
		}

		return self::EMAIL_TEMPLATE_TYPE_DIFFUSION_SEND;
	}

	/**
	 * Return the hidden mirror label used to avoid Dolibarr unique key conflicts.
	 *
	 * @param string $label Visible email template label
	 * @param string $targettype Hidden target template type
	 * @return string
	 */
	private static function getNotificationMirrorLabel($label, $targettype)
	{
		$suffix = ' ['.$targettype.']';
		if (substr($label, -strlen($suffix)) === $suffix) {
			return $label;
		}

		$maxlabelsize = 180 - strlen($suffix);
		if ($maxlabelsize < 1) {
			$maxlabelsize = 1;
		}

		return substr((string) $label, 0, $maxlabelsize).$suffix;
	}

	/**
	 * Return visible template label when the runtime value already contains a hidden mirror suffix.
	 *
	 * @param string $label Current template label
	 * @return string
	 */
	private static function getVisibleLabelFromNotificationMirrorLabel($label)
	{
		foreach (self::getNotificationMirrorEmailTemplateTypes() as $targettype) {
			$suffix = ' ['.$targettype.']';
			if (substr($label, -strlen($suffix)) === $suffix) {
				return substr($label, 0, -strlen($suffix));
			}
		}

		return $label;
	}

	/**
	 * Build a runtime key matching Dolibarr native email template uniqueness.
	 *
	 * @param int $entity Entity id
	 * @param mixed $label Template label
	 * @param mixed $lang Template language
	 * @return string
	 */
	private static function getEmailTemplateUniqueRuntimeKey($entity, $label, $lang)
	{
		return serialize(array(
			(int) $entity,
			$label === null ? null : (string) $label,
			$lang === null ? null : (string) $lang,
		));
	}

	/**
	 * Choose the visible template row to keep when legacy rows collide on entity, label and lang.
	 *
	 * @param array<int,array<string,mixed>> $rows Template rows sharing the same native unique key
	 * @return int Array index to keep
	 */
	private static function getVisibleEmailTemplateKeeperIndex($rows)
	{
		foreach ($rows as $index => $row) {
			if ($row['type_template'] === self::EMAIL_TEMPLATE_TYPE_NOTIFICATION && !empty($row['active'])) {
				return $index;
			}
		}

		foreach ($rows as $index => $row) {
			if ($row['type_template'] === self::EMAIL_TEMPLATE_TYPE_NOTIFICATION) {
				return $index;
			}
		}

		foreach ($rows as $index => $row) {
			if (!empty($row['active'])) {
				return $index;
			}
		}

		return 0;
	}

	/**
	 * Return a readable archived label for a duplicate legacy visible template.
	 *
	 * @param mixed $label Current template label
	 * @param string $typeTemplate Previous template type
	 * @param int $rowid Template row id
	 * @return string
	 */
	private static function getLegacyVisibleEmailTemplateArchiveLabel($label, $typeTemplate, $rowid)
	{
		$suffix = ' [legacy '.$typeTemplate.' #'.((int) $rowid).']';
		$baselabel = (string) $label;
		if ($baselabel === '') {
			$baselabel = 'Diffusion email template';
		}

		$maxlabelsize = 180 - strlen($suffix);
		if ($maxlabelsize < 1) {
			$maxlabelsize = 1;
		}

		return substr($baselabel, 0, $maxlabelsize).$suffix;
	}

	/**
	 * Copy or update visible Diffusion templates into one hidden notification type.
	 *
	 * @param DoliDB $db Database handler
	 * @param string $targettype Hidden target template type
	 * @param string $label Optional selected label to sync
	 * @return int<-1,1> Return 1 if synced, 0 if no source template found, <0 if KO
	 */
	private static function syncEmailTemplateMirror($db, $targettype, $label = '')
	{
		$sql = "SELECT rowid, entity, lang, private, fk_user, label, position, defaultfortype, enabled, active,";
		$sql .= " email_from, email_to, email_tocc, email_tobcc, topic, joinfiles, content, content_lines";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_email_templates";
		$sql .= " WHERE module = 'diffusion'";
		$sql .= " AND type_template = '".$db->escape(self::EMAIL_TEMPLATE_TYPE_NOTIFICATION)."'";
		if ($label !== '') {
			$sql .= " AND label = '".$db->escape($label)."'";
		}
		$sql .= " ORDER BY entity, lang, position, rowid";

		$resql = $db->query($sql);
		if (!$resql) {
			return -1;
		}

		$nbsource = 0;
		$neutralmirrorsdone = array();
		while ($obj = $db->fetch_object($resql)) {
			$nbsource++;
			$mirrorlabel = self::getNotificationMirrorLabel((string) $obj->label, $targettype);

			$result = self::syncEmailTemplateMirrorRow($db, $obj, $targettype, $mirrorlabel, $obj->lang);
			if ($result < 0) {
				$db->free($resql);
				return -1;
			}

			$neutralkey = self::getEmailTemplateMirrorRuntimeKey($targettype, $obj, $mirrorlabel);
			if (empty($neutralmirrorsdone[$neutralkey])) {
				if ($obj->lang !== null) {
					$result = self::syncEmailTemplateMirrorRow($db, $obj, $targettype, $mirrorlabel, null);
					if ($result < 0) {
						$db->free($resql);
						return -1;
					}
				}

				$result = self::normalizeNeutralEmailTemplateMirrorDuplicates($db, $obj, $targettype, $mirrorlabel);
				if ($result < 0) {
					$db->free($resql);
					return -1;
				}
				$neutralmirrorsdone[$neutralkey] = 1;
			}
		}

		$db->free($resql);

		return $nbsource > 0 ? 1 : 0;
	}

	/**
	 * Disable stale hidden notification template mirrors for a label no longer available under the visible type.
	 *
	 * @param DoliDB $db Database handler
	 * @param string $label Template label
	 * @return int<-1,1>
	 */
	private static function disableNotificationEmailTemplateMirrors($db, $label)
	{
		$targettypes = array();
		foreach (self::getNotificationMirrorEmailTemplateTypes() as $targettype) {
			$targettypes[] = "'".$db->escape($targettype)."'";
		}
		if (empty($targettypes)) {
			return 1;
		}

		$mirrorlabels = array();
		foreach (self::getNotificationMirrorEmailTemplateTypes() as $targettype) {
			$mirrorlabels[] = "'".$db->escape(self::getNotificationMirrorLabel($label, $targettype))."'";
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."c_email_templates";
		$sql .= " SET active = 0";
		$sql .= " WHERE module = 'diffusion'";
		$sql .= " AND type_template IN (".implode(',', $targettypes).")";
		$sql .= " AND label IN (".implode(',', $mirrorlabels).")";

		return $db->query($sql) ? 1 : -1;
	}

	/**
	 * Copy or update one visible template row into one hidden notification mirror language.
	 *
	 * @param DoliDB $db Database handler
	 * @param stdClass $obj Source email template row
	 * @param string $targettype Hidden target template type
	 * @param string $mirrorlabel Hidden mirror label
	 * @param mixed $mirrorlang Hidden mirror language, null for language-neutral fallback
	 * @return int<-1,1>
	 */
	private static function syncEmailTemplateMirrorRow($db, $obj, $targettype, $mirrorlabel, $mirrorlang)
	{
		$where = self::getEmailTemplateMirrorWhere($db, $obj, $targettype, $mirrorlabel, $mirrorlang);
		$uniquewhere = self::getEmailTemplateUniqueWhere($db, (int) $obj->entity, $mirrorlabel, $mirrorlang);

		$sqlinsert = "INSERT INTO ".MAIN_DB_PREFIX."c_email_templates";
		$sqlinsert .= " (entity, module, type_template, lang, private, fk_user, datec, label, position, defaultfortype, enabled, active,";
		$sqlinsert .= " email_from, email_to, email_tocc, email_tobcc, topic, joinfiles, content, content_lines)";
		$sqlinsert .= " SELECT ".((int) $obj->entity).", 'diffusion', '".$db->escape($targettype)."',";
		$sqlinsert .= " ".self::sqlNullableString($db, $mirrorlang).", ".((int) $obj->private).", ".self::sqlNullableInteger($obj->fk_user).", NOW(),";
		$sqlinsert .= " ".self::sqlNullableString($db, $mirrorlabel).", ".self::sqlNullableInteger($obj->position).", ".((int) $obj->defaultfortype).",";
		$sqlinsert .= " ".self::sqlNullableString($db, $obj->enabled).", ".((int) $obj->active).",";
		$sqlinsert .= " ".self::sqlNullableString($db, $obj->email_from).", ".self::sqlNullableString($db, $obj->email_to).",";
		$sqlinsert .= " ".self::sqlNullableString($db, $obj->email_tocc).", ".self::sqlNullableString($db, $obj->email_tobcc).",";
		$sqlinsert .= " ".self::sqlNullableString($db, $obj->topic).", ".self::sqlNullableString($db, $obj->joinfiles).",";
		$sqlinsert .= " ".self::sqlNullableString($db, $obj->content).", ".self::sqlNullableString($db, $obj->content_lines);
		$sqlinsert .= " FROM DUAL";
		$sqlinsert .= " WHERE NOT EXISTS (SELECT 1 FROM ".MAIN_DB_PREFIX."c_email_templates WHERE ".$uniquewhere.")";

		if (!$db->query($sqlinsert)) {
			return -1;
		}

		$sqlupdate = "UPDATE ".MAIN_DB_PREFIX."c_email_templates";
		$sqlupdate .= " SET position = ".self::sqlNullableInteger($obj->position);
		$sqlupdate .= ", defaultfortype = ".((int) $obj->defaultfortype);
		$sqlupdate .= ", enabled = ".self::sqlNullableString($db, $obj->enabled);
		$sqlupdate .= ", active = ".((int) $obj->active);
		$sqlupdate .= ", email_from = ".self::sqlNullableString($db, $obj->email_from);
		$sqlupdate .= ", email_to = ".self::sqlNullableString($db, $obj->email_to);
		$sqlupdate .= ", email_tocc = ".self::sqlNullableString($db, $obj->email_tocc);
		$sqlupdate .= ", email_tobcc = ".self::sqlNullableString($db, $obj->email_tobcc);
		$sqlupdate .= ", topic = ".self::sqlNullableString($db, $obj->topic);
		$sqlupdate .= ", joinfiles = ".self::sqlNullableString($db, $obj->joinfiles);
		$sqlupdate .= ", content = ".self::sqlNullableString($db, $obj->content);
		$sqlupdate .= ", content_lines = ".self::sqlNullableString($db, $obj->content_lines);
		$sqlupdate .= " WHERE ".$where;

		return $db->query($sqlupdate) ? 1 : -1;
	}

	/**
	 * Disable and rename duplicate neutral mirrors left by older MySQL NULL unique-key behavior.
	 *
	 * @param DoliDB $db Database handler
	 * @param stdClass $obj Source email template row
	 * @param string $targettype Hidden target template type
	 * @param string $mirrorlabel Hidden mirror label
	 * @return int<-1,1>
	 */
	private static function normalizeNeutralEmailTemplateMirrorDuplicates($db, $obj, $targettype, $mirrorlabel)
	{
		$sql = "SELECT rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_email_templates";
		$sql .= " WHERE ".self::getEmailTemplateMirrorWhere($db, $obj, $targettype, $mirrorlabel, null);
		$sql .= " ORDER BY rowid";

		$resql = $db->query($sql);
		if (!$resql) {
			return -1;
		}

		$keepid = 0;
		$duplicateids = array();
		while ($row = $db->fetch_object($resql)) {
			$rowid = (int) $row->rowid;
			if (empty($keepid)) {
				$keepid = $rowid;
				continue;
			}
			$duplicateids[] = $rowid;
		}

		$db->free($resql);

		foreach ($duplicateids as $rowid) {
			$archivedlabel = self::getNotificationMirrorDuplicateArchiveLabel($mirrorlabel, $targettype, $rowid);
			$sqlupdate = "UPDATE ".MAIN_DB_PREFIX."c_email_templates";
			$sqlupdate .= " SET label = ".self::sqlNullableString($db, $archivedlabel).", active = 0";
			$sqlupdate .= " WHERE rowid = ".$rowid;
			if (!$db->query($sqlupdate)) {
				return -1;
			}
		}

		return 1;
	}

	/**
	 * Build a runtime key for one hidden notification mirror independent of language.
	 *
	 * @param string $targettype Hidden target template type
	 * @param stdClass $obj Source email template row
	 * @param string $mirrorlabel Hidden mirror label
	 * @return string
	 */
	private static function getEmailTemplateMirrorRuntimeKey($targettype, $obj, $mirrorlabel)
	{
		return serialize(array(
			$targettype,
			(int) $obj->entity,
			(int) $obj->private,
			($obj->fk_user === null || $obj->fk_user === '') ? null : (int) $obj->fk_user,
			$mirrorlabel,
		));
	}

	/**
	 * Return a readable archived label for duplicate hidden notification mirrors.
	 *
	 * @param string $label Current mirror label
	 * @param string $targettype Hidden target template type
	 * @param int $rowid Template row id
	 * @return string
	 */
	private static function getNotificationMirrorDuplicateArchiveLabel($label, $targettype, $rowid)
	{
		$suffix = ' [duplicate '.$targettype.' #'.((int) $rowid).']';
		$maxlabelsize = 180 - strlen($suffix);
		if ($maxlabelsize < 1) {
			$maxlabelsize = 1;
		}

		return substr((string) $label, 0, $maxlabelsize).$suffix;
	}

	/**
	 * Build the key used to update one hidden notification template mirror.
	 *
	 * @param DoliDB $db Database handler
	 * @param stdClass $obj Source email template row
	 * @param string $targettype Hidden target template type
	 * @param string $mirrorlabel Hidden mirror label
	 * @param mixed $mirrorlang Hidden mirror language
	 * @return string SQL where clause
	 */
	private static function getEmailTemplateMirrorWhere($db, $obj, $targettype, $mirrorlabel, $mirrorlang)
	{
		$where = "module = 'diffusion'";
		$where .= " AND type_template = '".$db->escape($targettype)."'";
		$where .= " AND entity = ".((int) $obj->entity);
		$where .= " AND private = ".((int) $obj->private);
		$where .= " AND label ".self::sqlNullableCondition($db, $mirrorlabel);
		$where .= " AND lang ".self::sqlNullableCondition($db, $mirrorlang);
		if ($obj->fk_user === null || $obj->fk_user === '') {
			$where .= " AND fk_user IS NULL";
		} else {
			$where .= " AND fk_user = ".((int) $obj->fk_user);
		}

		return $where;
	}

	/**
	 * Build the native unique key condition for email templates.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $entity Entity id
	 * @param string $label Template label
	 * @param mixed $lang Template language
	 * @return string SQL where clause
	 */
	private static function getEmailTemplateUniqueWhere($db, $entity, $label, $lang)
	{
		$where = "entity = ".((int) $entity);
		$where .= " AND label ".self::sqlNullableCondition($db, $label);
		$where .= " AND lang ".self::sqlNullableCondition($db, $lang);

		return $where;
	}

	/**
	 * Check whether an email template label already exists for Dolibarr native unique key.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $entity Entity id
	 * @param string $label Template label
	 * @param mixed $lang Template language
	 * @param int $excludeid Row id to exclude
	 * @return bool
	 */
	private static function emailTemplateLabelExists($db, $entity, $label, $lang, $excludeid = 0)
	{
		$sql = "SELECT rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_email_templates";
		$sql .= " WHERE ".self::getEmailTemplateUniqueWhere($db, $entity, $label, $lang);
		if ($excludeid > 0) {
			$sql .= " AND rowid <> ".((int) $excludeid);
		}
		$sql .= " LIMIT 1";

		$resql = $db->query($sql);
		if (!$resql) {
			return true;
		}

		$exists = ($db->num_rows($resql) > 0);
		$db->free($resql);

		return $exists;
	}

	/**
	 * Return a SQL nullable string value.
	 *
	 * @param DoliDB $db Database handler
	 * @param mixed $value Value
	 * @return string
	 */
	private static function sqlNullableString($db, $value)
	{
		if ($value === null) {
			return 'NULL';
		}

		return "'".$db->escape((string) $value)."'";
	}

	/**
	 * Return a SQL nullable integer value.
	 *
	 * @param mixed $value Value
	 * @return string
	 */
	private static function sqlNullableInteger($value)
	{
		if ($value === null || $value === '') {
			return 'NULL';
		}

		return (string) ((int) $value);
	}

	/**
	 * Return a SQL condition for a nullable string column.
	 *
	 * @param DoliDB $db Database handler
	 * @param mixed $value Value
	 * @return string
	 */
	private static function sqlNullableCondition($db, $value)
	{
		if ($value === null) {
			return 'IS NULL';
		}

		return "= '".$db->escape((string) $value)."'";
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

		$notificationElementAliases = array('diffusion', 'diffusion@diffusion', 'diffusiondoc', 'diffusioncontact');
		foreach ($notificationElementAliases as $alias) {
			if (empty($conf->{$alias}) || !is_object($conf->{$alias})) {
				$conf->{$alias} = new stdClass();
			}
			$conf->{$alias}->enabled = !empty($conf->diffusion->enabled) ? 1 : 0;
			if (!empty($conf->diffusion->dir_output)) {
				$conf->{$alias}->dir_output = $conf->diffusion->dir_output;
			}
			if (!empty($conf->diffusion->multidir_output)) {
				$conf->{$alias}->multidir_output = $conf->diffusion->multidir_output;
			}
		}

		if (!empty($parameters['notifcode'])) {
			$result = self::syncSelectedNotificationEmailTemplateMirrors($this->db, (string) $parameters['notifcode'], $object);
			if ($result < 0) {
				$this->error = $this->db->lasterror();
				return -1;
			}
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
