<?php
/* Copyright (C) 2026 Pierre Ardoin; SPDX-License-Identifier: GPL-3.0-or-later */
require __DIR__.'/attachments.php';

$startchecks = $checks;
$object = attachmentFixture(2);
$directory = diffusionGetDocumentUploadDir($object);
$filename = '(PROV4)-électricité.pdf';
$conf->entity = 1;
$conf->global->DIFFUSION_ALLOW_EXTERNAL_DOWNLOAD = 1;
$db->query('CREATE TABLE fixture_const(name TEXT, value TEXT, entity INTEGER)');
$db->query("INSERT INTO fixture_const VALUES('DIFFUSION_ALLOW_EXTERNAL_DOWNLOAD', '0', 2)");
$db->query('UPDATE fixture_ecm_files SET share = NULL WHERE rowid = 1');
checkAttachment(diffusionEnsureAttachedFilesIndexedAndShared($db, $object, $directory, $user, array($filename), true), 0, 'Disabled owner sharing is respected from an enabled viewing entity');
checkAttachment(attachmentRow($db)->share, null, 'Viewing entity cannot expose an owner attachment');
checkAttachment((int) $conf->entity, 1, 'Owner setting lookup preserves the authenticated entity');
checkAttachment(getDolGlobalInt('DIFFUSION_ALLOW_EXTERNAL_DOWNLOAD'), 1, 'Owner lookup preserves the viewing configuration');
$db->query("UPDATE fixture_const SET value = '1' WHERE entity = 2");
$conf->global->DIFFUSION_ALLOW_EXTERNAL_DOWNLOAD = 0;
checkAttachment(diffusionEnsureAttachedFilesIndexedAndShared($db, $object, $directory, $user, array($filename), true), 1, 'Enabled owner sharing applies from a disabled viewing entity');
$key = attachmentRow($db)->share;
checkAttachment(is_string($key) && $key !== '', true, 'The native key is created for the new owner upload');
checkAttachment((int) attachmentRow($db)->entity, 2, 'ECM ownership stays with the source entity');
checkAttachment(getDolGlobalInt('DIFFUSION_ALLOW_EXTERNAL_DOWNLOAD'), 0, 'Owner sharing does not change the viewing configuration');
$db->query("UPDATE fixture_const SET value = '0' WHERE entity = 2");
checkAttachment(diffusionEnsureAttachedFilesIndexedAndShared($db, $object, $directory, $user, array($filename)), 0, 'Regeneration does not change an existing share');
checkAttachment(attachmentRow($db)->share, $key, 'Existing public key survives disabled automatic creation');
$db->query('DELETE FROM fixture_const');
$db->query('UPDATE fixture_ecm_files SET share = NULL WHERE rowid = 1');
$conf->global->DIFFUSION_ALLOW_EXTERNAL_DOWNLOAD = 1;
checkAttachment(diffusionEnsureAttachedFilesIndexedAndShared($db, $object, $directory, $user, array($filename), true), 0, 'Missing owner setting does not inherit the viewing entity');
checkAttachment(attachmentRow($db)->share, null, 'Missing owner configuration leaves sharing disabled');
clearAttachmentFixture();
echo ($checks - $startchecks)." owner-sharing assertions passed; native constant lookup and ECM fixtures.\n";
