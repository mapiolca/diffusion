-- Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
--
-- Default email templates for diffusion module.

UPDATE llx_c_email_templates
SET type_template = 'diffusion'
WHERE module = 'diffusion'
AND type_template IN ('diffusiondoc@diffusion', 'diffusioncontact@diffusion');

INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, enabled, joinfiles, topic, content)
SELECT 1, 'diffusion', 'diffusion', 'fr_FR', 0, NULL, NOW(), 'Envoi d''une diffusion', 100, 1, 'isModEnabled("diffusion")', '1',
	'Diffusion __DIFFUSION_REF__',
	'Bonjour,<br><br>Veuillez trouver ci-joint la diffusion __DIFFUSION_REF__.<br><br>__DIFFUSION_LABEL__<br>__DIFFUSION_URL__<br><br>Cordialement,<br>__SENDEREMAIL_SIGNATURE__'
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM llx_c_email_templates
	WHERE entity = 1 AND lang = 'fr_FR' AND label = 'Envoi d''une diffusion'
);

INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, enabled, joinfiles, topic, content)
SELECT 1, 'diffusion', 'diffusion', 'en_US', 0, NULL, NOW(), 'Send a diffusion', 100, 1, 'isModEnabled("diffusion")', '1',
	'Diffusion __DIFFUSION_REF__',
	'Hello,<br><br>Please find attached diffusion __DIFFUSION_REF__.<br><br>__DIFFUSION_LABEL__<br>__DIFFUSION_URL__<br><br>Regards,<br>__SENDEREMAIL_SIGNATURE__'
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM llx_c_email_templates
	WHERE entity = 1 AND lang = 'en_US' AND label = 'Send a diffusion'
);

INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, enabled, joinfiles, topic, content)
SELECT 1, 'diffusion', 'diffusion', 'fr_FR', 0, NULL, NOW(), 'Notification diffusion', 110, 1, 'isModEnabled("diffusion")', '0',
	'Notification diffusion __DIFFUSION_REF__',
	'Bonjour,<br><br>Un événement a été enregistré pour la diffusion __DIFFUSION_REF__.<br><br>Statut : __DIFFUSION_STATUS__<br>Projet : __DIFFUSION_PROJECT_REF__<br>Tiers : __DIFFUSION_THIRDPARTY_NAME__<br><br>Voir la diffusion : __DIFFUSION_URL__'
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM llx_c_email_templates
	WHERE entity = 1 AND lang = 'fr_FR' AND label = 'Notification diffusion'
);

INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, enabled, joinfiles, topic, content)
SELECT 1, 'diffusion', 'diffusion', 'en_US', 0, NULL, NOW(), 'Diffusion notification', 110, 1, 'isModEnabled("diffusion")', '0',
	'Diffusion notification __DIFFUSION_REF__',
	'Hello,<br><br>An event has been recorded for diffusion __DIFFUSION_REF__.<br><br>Status: __DIFFUSION_STATUS__<br>Project: __DIFFUSION_PROJECT_REF__<br>Third party: __DIFFUSION_THIRDPARTY_NAME__<br><br>Open diffusion: __DIFFUSION_URL__'
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM llx_c_email_templates
	WHERE entity = 1 AND lang = 'en_US' AND label = 'Diffusion notification'
);

INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, enabled, joinfiles, topic, content)
SELECT 1, 'diffusion', 'diffusion', 'fr_FR', 0, NULL, NOW(), 'Notification contact de diffusion', 120, 1, 'isModEnabled("diffusion")', '0',
	'Notification contact diffusion __DIFFUSION_REF__',
	'Bonjour,<br><br>Un événement a été enregistré sur un contact de la diffusion __DIFFUSION_REF__.<br><br>Contact : __DIFFUSIONCONTACT_NAME__<br>E-mail : __DIFFUSIONCONTACT_EMAIL__<br>Source : __DIFFUSIONCONTACT_SOURCE__<br><br>Voir la diffusion : __DIFFUSIONCONTACT_URL__'
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM llx_c_email_templates
	WHERE entity = 1 AND lang = 'fr_FR' AND label = 'Notification contact de diffusion'
);

INSERT INTO llx_c_email_templates (entity, module, type_template, lang, private, fk_user, datec, label, position, active, enabled, joinfiles, topic, content)
SELECT 1, 'diffusion', 'diffusion', 'en_US', 0, NULL, NOW(), 'Diffusion contact notification', 120, 1, 'isModEnabled("diffusion")', '0',
	'Diffusion contact notification __DIFFUSION_REF__',
	'Hello,<br><br>An event has been recorded on a contact of diffusion __DIFFUSION_REF__.<br><br>Contact: __DIFFUSIONCONTACT_NAME__<br>Email: __DIFFUSIONCONTACT_EMAIL__<br>Source: __DIFFUSIONCONTACT_SOURCE__<br><br>Open diffusion: __DIFFUSIONCONTACT_URL__'
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM llx_c_email_templates
	WHERE entity = 1 AND lang = 'en_US' AND label = 'Diffusion contact notification'
);
