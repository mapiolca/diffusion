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
 * @param CommonObject $object Current object
 * @param mixed $parameters Extra parameters
 * @return void
 */
function complete_substitutions_array_diffusion(&$substitutionarray, $langs, $object, $parameters = null)
{
	if (empty($object) || !is_object($object)) {
		return;
	}

	$isDiffusion = (!empty($object->element) && $object->element === 'diffusiondoc')
		|| (!empty($object->table_element) && $object->table_element === 'diffusion');
	if (!$isDiffusion) {
		return;
	}

	$url = '';
	if (!empty($object->id)) {
		$url = dol_buildpath('/diffusion/diffusion_card.php', 2).'?id='.(int) $object->id;
	}

	$substitutionarray['__DIFFUSION_REF__'] = !empty($object->ref) ? (string) $object->ref : '';
	$substitutionarray['__DIFFUSION_LABEL__'] = !empty($object->label) ? (string) $object->label : '';
	$substitutionarray['__DIFFUSION_STATUS__'] = method_exists($object, 'getLibStatut') ? dol_string_nohtmltag($object->getLibStatut(0)) : '';
	$substitutionarray['__DIFFUSION_URL__'] = $url;
	$substitutionarray['__DIFFUSION_PROJECT_REF__'] = '';
	$substitutionarray['__DIFFUSION_AUTHOR_FULLNAME__'] = '';
	$substitutionarray['__DIFFUSION_AUTHOR_EMAIL__'] = '';

	if (!empty($object->fk_project)) {
		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
		$project = new Project($object->db);
		if ($project->fetch((int) $object->fk_project) > 0) {
			$substitutionarray['__DIFFUSION_PROJECT_REF__'] = (string) $project->ref;
		}
	}

	if (!empty($object->fk_user_creat)) {
		require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
		$author = new User($object->db);
		if ($author->fetch((int) $object->fk_user_creat) > 0) {
			$substitutionarray['__DIFFUSION_AUTHOR_FULLNAME__'] = $author->getFullName($langs);
			$substitutionarray['__DIFFUSION_AUTHOR_EMAIL__'] = (string) $author->email;
		}
	}
}
