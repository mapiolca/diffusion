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
 * \file		core/triggers/interface_99_modDiffusion_DiffusionTriggers.class.php
 * \ingroup	diffusion
 * \brief		Triggers for Diffusion module
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once __DIR__.'/../../class/actions_diffusion.class.php';
require_once __DIR__.'/../../class/diffusioncontact.class.php';


/**
 * Triggers class for Diffusion module
 */
class InterfaceDiffusionTriggers extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = 'diffusion';
		$this->description = 'Diffusion business events triggers';
		$this->version = self::VERSION_DOLIBARR;
		$this->picto = 'diffusion@diffusion';
	}

	/**
	 * Run trigger
	 *
	 * @param string	$action Trigger action code
	 * @param Object	$object Trigger object
	 * @param User	$user User running trigger
	 * @param Translate $langs Lang object
	 * @param Conf	$conf Global config object
	 * @return int
	 */
	public function runTrigger($action, $object, $user, $langs, $conf)
	{
		switch ($action) {
			case 'DIFFUSION_CREATE':
			case 'DIFFUSION_VALIDATE':
			case 'DIFFUSION_SETDIFFUSED':
			case 'DIFFUSION_BACKTODRAFT':
			case 'DIFFUSION_CANCEL':
			case 'DIFFUSION_REOPEN':
			case 'DIFFUSION_DIFFUSION_MODIFY':
			case 'DIFFUSION_DELETE':
			case 'DIFFUSIONCONTACT_INSERT':
			case 'DIFFUSIONCONTACT_DELETELINE':
			case 'DIFFUSIONCONTACT_UPDATELINE':
			case 'DIFFUSIONCONTACT_DELETEALL':
				$result = $this->createAgendaEvent($action, $object, $user, $langs, $conf);
				if ($result < 0) {
					return -1;
				}
				return 0;

			case 'DIFFUSION_SENDMAIL':
				$result = $this->createAgendaEvent($action, $object, $user, $langs, $conf);
				if ($result < 0) {
					return -1;
				}

				// Automatically mark validated diffusion as sent when email sending succeeds.
				if (empty(getDolGlobalInt('DIFFUSION_AUTO_SET_SENT_ON_MAIL'))) {
					return 0;
				}

				if (!empty($object) && is_object($object)) {
					$iselementdiffusion = (!empty($object->element) && $object->element === 'diffusiondoc');
					$istableelementdiffusion = (!empty($object->table_element) && $object->table_element === 'diffusion');

					if (!$iselementdiffusion && !$istableelementdiffusion) {
						return 0;
					}

					if ((int) $object->status === (int) $object::STATUS_VALIDATED && method_exists($object, 'setSent')) {
						$result = $object->setSent($user);
						if ($result < 0) {
							$this->errors[] = $object->error;
							return -1;
						}
					}
				}

				return 0;
		}

		return 0;
	}

	/**
	 * Create a native Agenda event when enabled from Dolibarr Agenda setup.
	 *
	 * @param string $action Trigger code
	 * @param object $object Trigger object
	 * @param User $user Current user
	 * @param Translate $langs Lang object
	 * @param Conf $conf Global config
	 * @return int<-1,1>
	 */
	private function createAgendaEvent($action, $object, $user, $langs, $conf)
	{
		if (!isModEnabled('agenda') || !getDolGlobalInt('MAIN_AGENDA_ACTIONAUTO_'.$action)) {
			return 0;
		}
		if (empty($object) || !is_object($object)) {
			return 0;
		}
		if (!class_exists('ActionComm')) {
			return 0;
		}

		$isdiffusionobject = $this->isDiffusionObject($object);
		$diffusioncontext = $this->getDiffusionAgendaContext($action, $object);
		$diffusionid = (int) $diffusioncontext['diffusion_id'];
		$parentcontext = $this->fetchDiffusionAgendaParentContext($diffusionid);
		if ($diffusionid > 0 && empty($parentcontext)) {
			if (!empty($this->error)) {
				return -1;
			}
			if (!$isdiffusionobject) {
				return 0;
			}
			$parentcontext = array(
				'fk_soc' => !empty($object->fk_soc) ? (int) $object->fk_soc : (!empty($object->socid) ? (int) $object->socid : 0),
				'fk_project' => !empty($object->fk_project) ? (int) $object->fk_project : 0,
				'fk_user_creat' => !empty($object->fk_user_creat) ? (int) $object->fk_user_creat : 0,
			);
		}

		$eventdef = ActionsDiffusion::getBusinessEventDefinition($action);
		$elementtype = !empty($eventdef['agenda_elementtype']) ? (string) $eventdef['agenda_elementtype'] : ActionsDiffusion::AGENDA_ELEMENTTYPE_DIFFUSION;
		$agendaelementid = !empty($object->id) ? (int) $object->id : $diffusionid;
		if ($agendaelementid <= 0) {
			return 0;
		}

		$sql = "SELECT id FROM ".MAIN_DB_PREFIX."actioncomm";
		$sql .= " WHERE elementtype = '".$this->db->escape($elementtype)."'";
		$sql .= " AND fk_element = ".$agendaelementid;
		$sql .= " AND code = '".$this->db->escape($action)."'";
		$sql .= " LIMIT 1";
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}
		if ($this->db->num_rows($resql) > 0) {
			$this->db->free($resql);
			return 0;
		}
		$this->db->free($resql);

		$agenda = new ActionComm($this->db);
		$agenda->type_code = 'AC_OTH_AUTO';
		$agenda->code = $action;
		$agenda->label = $langs->trans('Notify_'.$action);
		$agenda->note_private = !empty($object->actionmsg) ? (string) $object->actionmsg : $agenda->label;
		$agenda->datep = dol_now();
		$agenda->datef = $agenda->datep;
		$agenda->percentage = -1;
		$agenda->elementtype = $elementtype;
		$agenda->fk_element = $agendaelementid;
		$agenda->userownerid = !empty($user->id) ? (int) $user->id : (!empty($object->fk_user_creat) ? (int) $object->fk_user_creat : (!empty($parentcontext['fk_user_creat']) ? (int) $parentcontext['fk_user_creat'] : 0));
		if (!empty($object->socid)) {
			$agenda->socid = (int) $object->socid;
		} elseif (!empty($object->fk_soc)) {
			$agenda->socid = (int) $object->fk_soc;
		} elseif (!empty($parentcontext['fk_soc'])) {
			$agenda->socid = (int) $parentcontext['fk_soc'];
		}
		if (!empty($object->fk_project)) {
			$agenda->fk_project = (int) $object->fk_project;
		} elseif (!empty($parentcontext['fk_project'])) {
			$agenda->fk_project = (int) $parentcontext['fk_project'];
		}
		$this->addDiffusionContactsToAgenda($agenda, $diffusionid, (int) $diffusioncontext['contact_id'], (string) $diffusioncontext['contact_source']);

		$result = $agenda->create($user);
		if ($result < 0) {
			$this->error = $agenda->error;
			$this->errors = $agenda->errors;
			return -1;
		}

		return 1;
	}

	/**
	 * Resolve the parent diffusion and current contact context for an Agenda event.
	 *
	 * @param string $action Trigger code
	 * @param object $object Trigger object
	 * @return array{diffusion_id:int,contact_id:int,contact_source:string}
	 */
	private function getDiffusionAgendaContext($action, $object)
	{
		$context = array(
			'diffusion_id' => 0,
			'contact_id' => 0,
			'contact_source' => '',
		);

		if ($this->isDiffusionObject($object) && !empty($object->id)) {
			$context['diffusion_id'] = (int) $object->id;
			return $context;
		}

		if (!empty($object->fk_diffusion)) {
			$context['diffusion_id'] = (int) $object->fk_diffusion;
		}
		if (!empty($object->fk_contact)) {
			$context['contact_id'] = (int) $object->fk_contact;
		}
		if (!empty($object->contact_source)) {
			$context['contact_source'] = $this->normalizeContactSource($object->contact_source);
		}

		if ($context['diffusion_id'] > 0 && $context['contact_id'] > 0 && $context['contact_source'] !== '') {
			return $context;
		}

		$iscontactevent = (strpos((string) $action, 'DIFFUSIONCONTACT_') === 0);
		$iscontactobject = (!empty($object->table_element) && $object->table_element === 'diffusion_contact') || (!empty($object->element) && $object->element === 'diffusioncontact');
		if (($iscontactevent || $iscontactobject) && !empty($object->id)) {
			$sql = 'SELECT dc.fk_diffusion, dc.fk_contact, dc.contact_source';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'diffusion_contact as dc';
			$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'diffusion as d ON d.rowid = dc.fk_diffusion';
			$sql .= ' WHERE dc.rowid = '.((int) $object->id);
			$sql .= ' AND d.entity IN ('.getEntity('diffusion').')';

			$resql = $this->db->query($sql);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				if ($obj) {
					$context['diffusion_id'] = (int) $obj->fk_diffusion;
					$context['contact_id'] = (int) $obj->fk_contact;
					$context['contact_source'] = $this->normalizeContactSource($obj->contact_source);
				}
				$this->db->free($resql);
			}
		}

		return $context;
	}

	/**
	 * Check if the trigger object is the parent Diffusion object.
	 *
	 * @param object $object Trigger object
	 * @return bool
	 */
	private function isDiffusionObject($object)
	{
		return (!empty($object->table_element) && $object->table_element === 'diffusion') || (!empty($object->element) && $object->element === 'diffusiondoc');
	}

	/**
	 * Fetch minimal parent Diffusion fields required by the Agenda event.
	 *
	 * @param int $diffusionid Diffusion identifier
	 * @return array{fk_soc:int,fk_project:int,fk_user_creat:int}
	 */
	private function fetchDiffusionAgendaParentContext($diffusionid)
	{
		$diffusionid = (int) $diffusionid;
		if ($diffusionid <= 0) {
			return array('fk_soc' => 0, 'fk_project' => 0, 'fk_user_creat' => 0);
		}

		$sql = 'SELECT d.fk_soc, d.fk_project, d.fk_user_creat';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'diffusion as d';
		$sql .= ' WHERE d.rowid = '.$diffusionid;
		$sql .= ' AND d.entity IN ('.getEntity('diffusion').')';

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return array();
		}

		$obj = $this->db->fetch_object($resql);
		$this->db->free($resql);
		if (!$obj) {
			return array();
		}

		return array(
			'fk_soc' => isset($obj->fk_soc) ? (int) $obj->fk_soc : 0,
			'fk_project' => isset($obj->fk_project) ? (int) $obj->fk_project : 0,
			'fk_user_creat' => isset($obj->fk_user_creat) ? (int) $obj->fk_user_creat : 0,
		);
	}

	/**
	 * Add linked Diffusion contacts as native Agenda participants.
	 *
	 * @param ActionComm $agenda Agenda event
	 * @param int $diffusionid Diffusion identifier
	 * @param int $currentcontactid Current contact identifier for contact triggers
	 * @param string $currentcontactsource Current contact source
	 * @return void
	 */
	private function addDiffusionContactsToAgenda(&$agenda, $diffusionid, $currentcontactid = 0, $currentcontactsource = '')
	{
		$diffusionid = (int) $diffusionid;
		if ($diffusionid > 0 && class_exists('DiffusionContact')) {
			$diffusioncontactstatic = new DiffusionContact($this->db);
			$links = $diffusioncontactstatic->fetchDiffusionContactLinks($diffusionid);
			foreach ((array) $links as $link) {
				$this->addAgendaContactParticipant($agenda, (int) ($link['fk_contact'] ?? 0), (string) ($link['contact_source'] ?? ''));
			}
		}

		$this->addAgendaContactParticipant($agenda, $currentcontactid, $currentcontactsource);
		$this->setLegacyAgendaContactId($agenda);
	}

	/**
	 * Add one internal or external contact to native Agenda participant fields.
	 *
	 * @param ActionComm $agenda Agenda event
	 * @param int $contactid Contact or user identifier
	 * @param string $source Contact source
	 * @return void
	 */
	private function addAgendaContactParticipant(&$agenda, $contactid, $source)
	{
		$contactid = (int) $contactid;
		$source = $this->normalizeContactSource($source);
		if ($contactid <= 0 || $source === '') {
			return;
		}

		if ($source === 'internal') {
			$agenda->userassigned[$contactid] = array('id' => $contactid);
			return;
		}

		if ($source === 'external') {
			$agenda->socpeopleassigned[$contactid] = $contactid;
		}
	}

	/**
	 * Fill deprecated contact_id when exactly one external contact is linked.
	 *
	 * @param ActionComm $agenda Agenda event
	 * @return void
	 */
	private function setLegacyAgendaContactId(&$agenda)
	{
		$externalids = array();
		foreach ((array) $agenda->socpeopleassigned as $id => $value) {
			$id = (int) $id;
			if ($id > 0) {
				$externalids[$id] = $id;
			}
		}

		if (count($externalids) === 1) {
			$agenda->contact_id = (int) reset($externalids);
		}
	}

	/**
	 * Normalize diffusion contact source values.
	 *
	 * @param string $source Raw source
	 * @return string internal, external or empty string
	 */
	private function normalizeContactSource($source)
	{
		$source = strtolower(trim((string) $source));
		if (in_array($source, array('internal', 'user'), true)) {
			return 'internal';
		}
		if (in_array($source, array('external', 'contact', 'socpeople', 'thirdparty'), true)) {
			return 'external';
		}

		return '';
	}
}
