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
			case 'DIFFUSION_DELETE':
				// Business events are consumed by Notification and Agenda modules.
				return 0;

			case 'DIFFUSION_SENDMAIL':
				// Automatically mark validated diffusion as sent when email sending succeeds.
				if (empty(getDolGlobalInt('DIFFUSION_AUTO_SET_SENT_ON_MAIL'))) {
					return 0;
				}

				if (!empty($object) && is_object($object) && !empty($object->element) && $object->element === 'diffusion') {
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
}
