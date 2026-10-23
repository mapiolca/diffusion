<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
require_once __DIR__.'/../lib/diffusion_diffusion.lib.php';

/** Document relocation and conservative ECM recovery; no session or sharing-key generation. */
class DiffusionAttachments
{
	/** @var DoliDB */
	private $db;
	/** @var list<array{0:string,1:string,2:bool}> Successful filesystem moves, in order. */
	private $moves = array();
	/** @var string */
	public $error = '';

	/** @param DoliDB $db Database handler */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Check data scope independently of the caller's functional permission.
	 * @param Diffusion $object Loaded diffusion
	 * @param User $user Actor
	 * @return bool
	 */
	public static function checkObjectScope($object, $user)
	{
		if (empty($object->id) || (int) $object->entity <= 0 || !empty($user->socid)
			|| !in_array((int) $object->entity, array_map('intval', explode(',', getEntity('diffusion'))), true)) {
			return false;
		}
		if (!empty($object->fk_project)) {
			require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
			$project = new Project($object->db);
			if ($project->fetch((int) $object->fk_project) <= 0
				|| !in_array((int) $project->entity, array_map('intval', explode(',', getEntity('project'))), true)
				|| $project->restrictedProjectArea($user, 'read') <= 0) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Refuse outgoing symlinks and directories outside the configured document root.
	 * @param string $directory Existing directory
	 * @param bool $inspectchildren Also reject symlinks inside the directory tree
	 * @return bool
	 */
	public static function isSafeDirectory($directory, $inspectchildren = false)
	{
		$root = realpath(DOL_DATA_ROOT);
		$real = realpath($directory);
		if ($root === false || $real === false) {
			return false;
		}
		$root = rtrim(str_replace('\\', '/', $root), '/').'/';
		$real = rtrim(str_replace('\\', '/', $real), '/').'/';
		if (strpos($real, $root) !== 0) {
			return false;
		}
		for ($ancestor = $directory; strlen($ancestor) >= strlen(rtrim(DOL_DATA_ROOT, '/\\')); $ancestor = dirname($ancestor)) {
			if (is_link($ancestor)) {
				return false;
			}
			if (dirname($ancestor) === $ancestor) {
				break;
			}
		}
		if (!$inspectchildren) {
			return true;
		}
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
		foreach ($iterator as $entry) {
			if ($entry->isLink()) {
				return false;
			}
		}
		return true;
	}

	/** @param string $message Translation key; no physical path is exposed. @return int Always -1. */
	private function fail($message = 'DiffusionAttachmentOperationFailed')
	{
		$this->error = $message;
		return -1;
	}

	/**
	 * Relocate documents inside the validation transaction. The caller owns commit/rollback.
	 * @param Diffusion $object Object before reference change
	 * @param string $newref Final reference
	 * @param User $user Actor
	 * @return int
	 */
	public function relocate($object, $newref, $user)
	{
		if (!$user->hasRight('diffusion', 'diffusiondoc', 'write') || !self::checkObjectScope($object, $user)) {
			return $this->fail();
		}
		$base = diffusionGetDocumentBaseOutputDir($object);
		$source = diffusionGetDocumentUploadDir($object);
		$flat = diffusionGetFlatDocumentUploadDir($object);
		$targetobject = clone $object;
		$targetobject->ref = $newref;
		$target = diffusionGetDocumentUploadDir($targetobject);
		$targetflat = diffusionGetFlatDocumentUploadDir($targetobject);
		if ($base === '' || $source === '' || $target === '' || !self::isSafeDirectory($base)) {
			return $this->fail();
		}
		if (is_dir($source) && is_dir($flat)) {
			return $this->fail('DiffusionAttachmentDirectoryConflict');
		}
		if (!is_dir($source) && is_dir($flat)) {
			$source = $flat;
		}
		// A draft with no directory must not adopt files left under its future reference.
		if (file_exists($target) || ($targetflat !== '' && file_exists($targetflat))) {
			return $this->fail('DiffusionAttachmentPathConflict');
		}
		if (!is_dir($source)) {
			return 0;
		}
		if (!self::isSafeDirectory($source, true)) {
			return $this->fail('DiffusionAttachmentPathConflict');
		}
		$oldrelative = diffusionGetDocumentPathRelativeToDataRoot($source);
		$newrelative = diffusionGetDocumentPathRelativeToDataRoot($target);
		$oldref = dol_sanitizeFileName($object->ref);
		$newref = dol_sanitizeFileName($newref);
		$files = dol_dir_list($source, 'files', 1);
		$names = array();
		$destinations = array();
		foreach ($files as $file) {
			$relative = substr(str_replace('\\', '/', $file['fullname']), strlen(str_replace('\\', '/', $source)) + 1);
			$name = basename($relative);
			$newname = strpos($name, $oldref) === 0 ? $newref.substr($name, strlen($oldref)) : $name;
			$newpath = (dirname($relative) === '.' ? '' : dirname($relative).'/').$newname;
			if (isset($destinations[$newpath])) {
				return $this->fail('DiffusionAttachmentPathConflict');
			}
			$destinations[$newpath] = true;
			$names[$relative] = $newpath;
		}
		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'ecm_files WHERE entity = '.((int) $object->entity);
		$sql .= " AND (filepath = '".$this->db->escape($oldrelative)."' OR filepath LIKE '".$this->db->escape($this->db->escapeforlike($oldrelative))."/%')";
		$resql = $this->db->query($sql);
		if (!$resql) {
			return $this->fail();
		}
		/** @var list<EcmFiles> $entries */
		$entries = array();
		while (is_object($row = $this->db->fetch_object($resql))) {
			$entry = new EcmFiles($this->db);
			if ($entry->fetch((int) $row->rowid) <= 0
				|| (!empty($entry->src_object_id) && ((int) $entry->src_object_id !== (int) $object->id || !in_array($entry->src_object_type, array($object->table_element, $object->element, 'diffusiondoc@diffusion'), true)))) {
				$this->db->free($resql);
				return $this->fail('DiffusionAttachmentIndexConflict');
			}
			$entries[] = $entry;
		}
		$this->db->free($resql);
		if (dol_mkdir(dirname($target)) < 0 || !dol_move_dir($source, $target, 0, 0, 0)) {
			return $this->fail('DiffusionAttachmentMoveFailed');
		}
		$this->moves[] = array($source, $target, true);
		// Rename prefixed files without overwriting another attachment (including rename cycles).
		foreach ($names as $before => $after) {
			if ($before !== $after) {
				if (!dol_move($target.'/'.$before, $target.'/'.$after, '0', 0, 0, 0)) {
					return $this->fail('DiffusionAttachmentMoveFailed');
				}
				$this->moves[] = array($target.'/'.$before, $target.'/'.$after, false);
			}
		}
		foreach ($entries as $entry) {
			$relative = ltrim(substr($entry->filepath, strlen($oldrelative)).'/'.$entry->filename, '/');
			$physical = isset($names[$relative]) ? $relative : $relative.'.noexe';
			if (!isset($names[$physical])) {
				return $this->fail('DiffusionAttachmentIndexedFileMissing');
			}
			$newpath = preg_replace('/\.noexe$/', '', $names[$physical]);
			$entry->filepath = $newrelative.(dirname($newpath) === '.' ? '' : '/'.dirname($newpath));
			$entry->filename = basename($newpath);
			$entry->src_object_type = $object->table_element;
			$entry->src_object_id = $object->id;
			if ($entry->update($user, 1) <= 0) {
				return $this->fail();
			}
		}
		if (!empty($object->last_main_doc) && strpos($object->last_main_doc, $oldrelative.'/') === 0) {
			$relative = substr($object->last_main_doc, strlen($oldrelative) + 1);
			if (isset($names[$relative])) {
				$object->last_main_doc = $newrelative.'/'.$names[$relative];
			}
		}
		return 1;
	}

	/**
	 * Restore filesystem changes after SQL/trigger/commit failure; never overwrite concurrent files.
	 * @return bool
	 */
	public function rollbackMoves()
	{
		$ok = true;
		foreach (array_reverse($this->moves) as $move) {
			if (file_exists($move[0]) || !($move[2] ? dol_move_dir($move[1], $move[0], 0, 0, 0) : dol_move($move[1], $move[0], '0', 0, 0, 0))) {
				$ok = false;
				break;
			}
		}
		if (!$ok) {
			$this->error = 'DiffusionAttachmentRestoreFailed';
			dol_syslog(__METHOD__.' filesystem restoration incomplete', LOG_ERR);
		}
		return $ok;
	}

	/**
	 * @param EcmFiles $entry Candidate native mirror
	 * @return bool
	 */
	private static function isNeutralIndex($entry)
	{
		return empty($entry->share) && $entry->gen_or_uploaded === 'unknown' && empty($entry->acl)
			&& empty($entry->description) && empty($entry->keywords) && empty($entry->note_private)
			&& empty($entry->note_public) && empty($entry->extraparams) && empty($entry->array_options)
			&& empty($entry->cover) && empty($entry->agenda_id);
	}

	/**
	 * Repair this object's ECM rows; ambiguous files are left untouched and reported.
	 * @param Diffusion $object Loaded diffusion
	 * @param User $user Actor
	 * @return array{repaired:int,missing:int,conflicts:int}
	 */
	public function repair($object, $user)
	{
		$result = array('repaired' => 0, 'missing' => 0, 'conflicts' => 0);
		if (!isModEnabled('diffusion') || !$user->hasRight('diffusion', 'diffusiondoc', 'write')
			|| !self::checkObjectScope($object, $user) || !empty($object->is_template)) {
			$this->fail();
			$result['conflicts']++;
			return $result;
		}
		$directory = diffusionGetDocumentUploadDir($object);
		$flat = diffusionGetFlatDocumentUploadDir($object);
		$base = diffusionGetDocumentBaseOutputDir($object);
		if ($base === '' || !self::isSafeDirectory($base)) {
			$this->fail();
			$result['conflicts']++;
			return $result;
		}
		$relative = diffusionGetDocumentPathRelativeToDataRoot($directory);
		$provisional = clone $object;
		$provisional->ref = '(PROV'.((int) $object->id).')';
		$allowedpaths = array($relative, diffusionGetDocumentPathRelativeToDataRoot($flat),
			diffusionGetDocumentPathRelativeToDataRoot(diffusionGetDocumentUploadDir($provisional)),
			diffusionGetDocumentPathRelativeToDataRoot(diffusionGetFlatDocumentUploadDir($provisional)));
		$allowedpaths[] = 'diffusion/diffusiondoc/'.dol_sanitizeFileName($provisional->ref);
		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'ecm_files WHERE entity = '.((int) $object->entity);
		$sql .= " AND ((src_object_type = '".$this->db->escape($object->table_element)."' AND src_object_id = ".((int) $object->id).')';
		foreach ($allowedpaths as $path) {
			$sql .= " OR filepath = '".$this->db->escape($path)."'";
		}
		$sql .= ')';
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->fail();
			$result['conflicts']++;
			return $result;
		}
		/** @var list<EcmFiles> $entries */
		$entries = array();
		while (is_object($row = $this->db->fetch_object($resql))) {
			$entry = new EcmFiles($this->db);
			if ($entry->fetch((int) $row->rowid) > 0) {
				$entries[] = $entry;
			}
		}
		$this->db->free($resql);
		foreach (array_unique(array($directory, $flat)) as $source) {
			if (!is_dir($source)) {
				continue;
			}
			if (!self::isSafeDirectory($source, true)) {
				$result['conflicts']++;
				continue;
			}
			foreach (dol_dir_list($source, 'files', 0) as $file) {
				if (diffusionIsTechnicalAttachedFile($object, $file['name'])) {
					continue;
				}
				$name = preg_replace('/\.noexe$/', '', $file['name']);
				$oldname = strpos($name, dol_sanitizeFileName($object->ref)) === 0
					? dol_sanitizeFileName($provisional->ref).substr($name, strlen(dol_sanitizeFileName($object->ref))) : $name;
				$candidates = array();
				$related = false;
				$checksum = md5_file($file['fullname']);
				if ($checksum === false) {
					$result['conflicts']++;
					continue;
				}
				foreach ($entries as $entry) {
					$owned = (int) $entry->src_object_id === (int) $object->id && in_array($entry->src_object_type, array($object->table_element, $object->element, 'diffusiondoc@diffusion'), true);
					if (in_array($entry->filename, array($name, $oldname), true)) {
						$related = true;
					}
					if (in_array($entry->filepath, $allowedpaths, true) && in_array($entry->filename, array($name, $oldname), true)
						&& ($owned || empty($entry->src_object_id)) && $entry->label === $checksum) {
						$candidates[] = $entry;
					}
				}
				$mirror = null;
				if (count($candidates) === 2) {
					foreach ($candidates as $candidate) {
						if ($candidate->filepath === $relative && $candidate->filename === $name && self::isNeutralIndex($candidate)) {
							$mirror = $candidate;
						}
					}
					$originals = array_values(array_filter($candidates, static function ($candidate) use ($mirror) { return $candidate !== $mirror; }));
					if ($mirror && count($originals) === 1 && !empty($originals[0]->share)) {
						$candidates = $originals;
					} else {
						$mirror = null;
					}
				}
				if (!$candidates && !$related) {
					// Recreate the index only; public sharing remains an explicit native action.
					$index = new EcmFiles($this->db);
					$index->filepath = diffusionGetDocumentPathRelativeToDataRoot($source);
					$index->filename = $name;
					$index->label = $checksum;
					$index->entity = $object->entity;
					$index->src_object_type = $object->table_element;
					$index->src_object_id = $object->id;
					$index->gen_or_uploaded = 'uploaded';
					if ($index->create($user, 1) <= 0) {
						$result['conflicts']++;
						continue;
					}
					$result['missing']++;
					$candidates = array($index);
				}
				if (count($candidates) !== 1 || ($source !== $directory && file_exists($directory.'/'.$file['name']))) {
					$result['conflicts']++;
					continue;
				}
				$entry = $candidates[0];
				if ($entry->filepath === $relative && $entry->filename === $name && $source === $directory
					&& (int) $entry->src_object_id === (int) $object->id && $entry->src_object_type === $object->table_element) {
					continue;
				}
				$this->moves = array();
				if (!$this->db->begin()) {
					$result['conflicts']++;
					continue;
				}
				$lock = $this->db->query('SELECT rowid FROM '.MAIN_DB_PREFIX.'ecm_files WHERE rowid = '.((int) $entry->id).' FOR UPDATE');
				$fresh = new EcmFiles($this->db);
				$ok = $lock && $fresh->fetch((int) $entry->id) > 0 && $fresh->share === $entry->share
					&& $fresh->filename === $entry->filename && $fresh->filepath === $entry->filepath
					&& (int) $fresh->entity === (int) $object->entity && (int) $fresh->src_object_id === (int) $entry->src_object_id
					&& $fresh->src_object_type === $entry->src_object_type
					&& md5_file($file['fullname']) === $checksum;
				if ($lock) {
					$this->db->free($lock);
				}
				if ($ok) {
					$entry = $fresh; // Keep metadata edited since the diagnostic read.
				}
				if ($ok && $mirror) {
					$lock = $this->db->query('SELECT rowid FROM '.MAIN_DB_PREFIX.'ecm_files WHERE rowid = '.((int) $mirror->id).' FOR UPDATE');
					$freshmirror = new EcmFiles($this->db);
					$ok = $lock && $freshmirror->fetch((int) $mirror->id) > 0 && self::isNeutralIndex($freshmirror)
						&& $freshmirror->filepath === $relative && $freshmirror->filename === $name
						&& (int) $freshmirror->entity === (int) $object->entity && $freshmirror->label === $checksum;
					if ($lock) {
						$this->db->free($lock);
					}
					$ok = $ok && $freshmirror->delete($user, 1) > 0;
				}
				if ($source !== $directory) {
					$ok = $ok && dol_mkdir($directory) >= 0 && dol_move($file['fullname'], $directory.'/'.$file['name'], '0', 0, 0, 0);
					if ($ok) {
						$this->moves[] = array($file['fullname'], $directory.'/'.$file['name'], false);
					}
				}
				$entry->filepath = $relative;
				$entry->filename = $name;
				$entry->src_object_type = $object->table_element;
				$entry->src_object_id = $object->id;
				$ok = $ok && $entry->update($user, 1) > 0;
				if ($ok && $this->db->commit() > 0) {
					$result['repaired']++;
				} else {
					$this->db->rollback();
					$this->rollbackMoves();
					$result['conflicts']++;
				}
			}
		}
		return $result;
	}
}
