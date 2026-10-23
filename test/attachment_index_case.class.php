<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/** Narrow maintenance operation; the web caller fixes the reviewed case and file hash. */
class DiffusionAttachmentIndexCase
{
	/** @var string Translation key, without database details or public keys. */
	public $error = '';

	/**
	 * Reassign one proven orphan, preserving its row, share, metadata and physical file.
	 * @param Diffusion $object Accessible target
	 * @param User $user Authenticated administrator
	 * @param int $indexid Reviewed ECM row
	 * @param int $sourceid Reviewed missing source
	 * @param string $name Reviewed filename
	 * @param string $sha256 Reviewed file digest
	 * @return int 1 repaired, 0 already correct, -1 refused/failed
	 */
	public function repair($object, $user, int $indexid, int $sourceid, string $name, string $sha256): int
	{
		$this->error = 'DiffusionAttachmentCaseRepairFailed';
		if (!$user->admin || !empty($user->socid) || !isModEnabled('diffusion')
			|| !DiffusionAttachments::checkObjectScope($object, $user) || !empty($object->is_template)
			|| $indexid <= 0 || $sourceid <= 0 || $sourceid === (int) $object->id
			|| basename($name) !== $name || preg_match('/^[a-f0-9]{64}$/D', $sha256) !== 1) {
			return -1;
		}
		$db = $object->db;
		if (!$db->begin()) {
			return -1;
		}
		try {
			$lock = $db->query('SELECT rowid FROM '.MAIN_DB_PREFIX.'diffusion WHERE rowid = '.((int) $object->id)
				.' AND entity = '.((int) $object->entity)." AND ref = '".$db->escape($object->ref)."' FOR UPDATE");
			if (!$lock || $db->num_rows($lock) !== 1) {
				throw new RuntimeException('Target changed');
			}
			$db->free($lock);
			$target = clone $object;
			if ($target->fetch((int) $object->id, null, 1, 1) <= 0 || !empty($target->is_template)
				|| !DiffusionAttachments::checkObjectScope($target, $user)) {
				throw new RuntimeException('Target unavailable');
			}
			$directory = diffusionGetDocumentUploadDir($target);
			$file = $directory.'/'.$name;
			if ($directory === '' || !DiffusionAttachments::isSafeDirectory($directory, true)
				|| !is_file($file) || hash_file('sha256', $file) !== $sha256) {
				throw new RuntimeException('File changed');
			}
			$relative = diffusionGetDocumentPathRelativeToDataRoot($directory);
			$query = 'SELECT * FROM '.MAIN_DB_PREFIX.'ecm_files WHERE rowid = '.$indexid.' AND entity = '.((int) $target->entity);
			$lock = $db->query($query.' FOR UPDATE');
			$before = $lock ? $db->fetch_object($lock) : false;
			if ($lock) {
				$db->free($lock);
			}
			$entry = new EcmFiles($db);
			if (!is_object($before) || $entry->fetch($indexid) <= 0 || $entry->filepath !== $relative
				|| $entry->filename !== $name || (int) $entry->entity !== (int) $target->entity
				|| $entry->src_object_type !== $target->table_element || empty($entry->share)
				|| $entry->label !== md5_file($file)
				|| !in_array((int) $entry->src_object_id, array($sourceid, (int) $target->id), true)) {
				throw new RuntimeException('Index changed');
			}
			$duplicates = $db->query('SELECT rowid FROM '.MAIN_DB_PREFIX.'ecm_files WHERE entity = '.((int) $target->entity)
				." AND filepath = '".$db->escape($relative)."' AND filename = '".$db->escape($name)."' FOR UPDATE");
			if (!$duplicates || $db->num_rows($duplicates) !== 1) {
				throw new RuntimeException('Ambiguous index');
			}
			$db->free($duplicates);
			$changed = (int) $entry->src_object_id !== (int) $target->id;
			if ($changed) {
				// Native fetch by primary key checks absence even outside the viewing entity.
				$source = clone $target;
				if ($source->fetch($sourceid, null, 1, 1) !== 0) {
					throw new RuntimeException('Source exists or cannot be read');
				}
				$gap = $db->query('SELECT rowid FROM '.MAIN_DB_PREFIX.'diffusion WHERE rowid = '.$sourceid
					.' AND entity = '.((int) $target->entity).' FOR UPDATE');
				if (!$gap || $db->num_rows($gap) !== 0) {
					throw new RuntimeException('Source changed');
				}
				$db->free($gap);
				$options = $entry->array_options;
				$entry->src_object_id = (int) $target->id;
				$entry->fk_user_m = (int) $user->id;
				if ($entry->update($user, 1) <= 0) {
					throw new RuntimeException('Native update failed');
				}
				$check = $db->query($query);
				$after = $check ? $db->fetch_object($check) : false;
				if ($check) {
					$db->free($check);
				}
				if (!is_object($after) || (int) $after->src_object_id !== (int) $target->id) {
					throw new RuntimeException('Update missing');
				}
				foreach (get_object_vars($before) as $column => $value) {
					if (!in_array($column, array('src_object_id', 'fk_user_m', 'tms'), true) && $after->{$column} !== $value) {
						throw new RuntimeException('Metadata changed');
					}
				}
				$fresh = new EcmFiles($db);
				if ($fresh->fetch($indexid) <= 0 || $fresh->array_options !== $options) {
					throw new RuntimeException('Extrafields changed');
				}
			}
			if (hash_file('sha256', $file) !== $sha256 || $db->commit() <= 0) {
				throw new RuntimeException('Final check failed');
			}
			$this->error = '';
			return $changed ? 1 : 0;
		} catch (Throwable $e) {
			$db->rollback();
			return -1;
		}
	}
}
