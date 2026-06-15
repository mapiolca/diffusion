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
		if (empty($object) || !is_object($object) || empty($object->id)) {
			return 0;
		}
		if (!class_exists('ActionComm')) {
			return 0;
		}

		$eventdef = ActionsDiffusion::getBusinessEventDefinition($action);
		$elementtype = !empty($eventdef['agenda_elementtype']) ? (string) $eventdef['agenda_elementtype'] : ActionsDiffusion::AGENDA_ELEMENTTYPE_DIFFUSION;
		$sql = "SELECT id FROM ".MAIN_DB_PREFIX."actioncomm";
		$sql .= " WHERE elementtype = '".$this->db->escape($elementtype)."'";
		$sql .= " AND fk_element = ".((int) $object->id);
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
		$agenda->fk_element = (int) $object->id;
		$agenda->userownerid = !empty($user->id) ? (int) $user->id : (!empty($object->fk_user_creat) ? (int) $object->fk_user_creat : 0);
		if (!empty($object->socid)) {
			$agenda->socid = (int) $object->socid;
		} elseif (!empty($object->fk_soc)) {
			$agenda->socid = (int) $object->fk_soc;
		}
		if (!empty($object->fk_project)) {
			$agenda->fk_project = (int) $object->fk_project;
		}

		$result = $agenda->create($user);
		if ($result < 0) {
			$this->error = $agenda->error;
			$this->errors = $agenda->errors;
			return -1;
		}

		return 1;
	}
}
