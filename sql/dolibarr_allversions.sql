--
-- Script run when an upgrade of Dolibarr is done. Whatever is the Dolibarr version.
--

ALTER TABLE llx_diffusion_contact ADD COLUMN fk_type_contact integer;
ALTER TABLE llx_diffusion ADD COLUMN entity integer DEFAULT 1 NOT NULL;
ALTER TABLE llx_diffusion ADD INDEX idx_diffusion_entity (entity);
ALTER TABLE llx_diffusion ADD COLUMN date_expedition datetime;
ALTER TABLE llx_diffusion ADD COLUMN fk_user_exped integer;
ALTER TABLE llx_diffusion ADD INDEX idx_diffusion_fk_user_exped (fk_user_exped);

UPDATE llx_c_action_trigger
SET elementtype = 'diffusion@diffusion'
WHERE code IN ('DIFFUSION_CREATE', 'DIFFUSION_VALIDATE', 'DIFFUSION_SENDMAIL', 'DIFFUSION_SETDIFFUSED', 'DIFFUSION_BACKTODRAFT', 'DIFFUSION_DELETE', 'DIFFUSION_CANCEL', 'DIFFUSION_REOPEN', 'DIFFUSION_DIFFUSION_MODIFY');

UPDATE llx_c_action_trigger
SET elementtype = 'diffusion@diffusion'
WHERE code IN ('DIFFUSIONCONTACT_INSERT', 'DIFFUSIONCONTACT_DELETELINE', 'DIFFUSIONCONTACT_UPDATELINE', 'DIFFUSIONCONTACT_DELETEALL');

UPDATE llx_c_email_templates
SET type_template = 'diffusion@diffusion'
WHERE module = 'diffusion'
AND type_template IN ('diffusion', 'diffusiondoc@diffusion', 'diffusioncontact@diffusion');

UPDATE llx_c_email_templates
SET topic = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(topic, '__DIFFUSION_REF__', '__REF__'), '__DIFFUSION_LABEL__', '__LABEL__'), '__DIFFUSION_PROJECT_REF__', '__PROJECT_REF__'), '__DIFFUSION_PROJECT_LABEL__', '__PROJECT_NAME__'), '__DIFFUSION_AUTHOR_FULLNAME__', '__AUTHOR_FULLNAME__'), '__DIFFUSION_AUTHOR_EMAIL__', '__AUTHOR_EMAIL__')
WHERE module = 'diffusion'
AND topic IS NOT NULL
AND (
	INSTR(topic, '__DIFFUSION_REF__') > 0
	OR INSTR(topic, '__DIFFUSION_LABEL__') > 0
	OR INSTR(topic, '__DIFFUSION_PROJECT_REF__') > 0
	OR INSTR(topic, '__DIFFUSION_PROJECT_LABEL__') > 0
	OR INSTR(topic, '__DIFFUSION_AUTHOR_FULLNAME__') > 0
	OR INSTR(topic, '__DIFFUSION_AUTHOR_EMAIL__') > 0
);

UPDATE llx_c_email_templates
SET content = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(content, '__DIFFUSION_REF__', '__REF__'), '__DIFFUSION_LABEL__', '__LABEL__'), '__DIFFUSION_PROJECT_REF__', '__PROJECT_REF__'), '__DIFFUSION_PROJECT_LABEL__', '__PROJECT_NAME__'), '__DIFFUSION_AUTHOR_FULLNAME__', '__AUTHOR_FULLNAME__'), '__DIFFUSION_AUTHOR_EMAIL__', '__AUTHOR_EMAIL__')
WHERE module = 'diffusion'
AND content IS NOT NULL
AND (
	INSTR(content, '__DIFFUSION_REF__') > 0
	OR INSTR(content, '__DIFFUSION_LABEL__') > 0
	OR INSTR(content, '__DIFFUSION_PROJECT_REF__') > 0
	OR INSTR(content, '__DIFFUSION_PROJECT_LABEL__') > 0
	OR INSTR(content, '__DIFFUSION_AUTHOR_FULLNAME__') > 0
	OR INSTR(content, '__DIFFUSION_AUTHOR_EMAIL__') > 0
);

UPDATE llx_c_email_templates
SET content_lines = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(content_lines, '__DIFFUSION_REF__', '__REF__'), '__DIFFUSION_LABEL__', '__LABEL__'), '__DIFFUSION_PROJECT_REF__', '__PROJECT_REF__'), '__DIFFUSION_PROJECT_LABEL__', '__PROJECT_NAME__'), '__DIFFUSION_AUTHOR_FULLNAME__', '__AUTHOR_FULLNAME__'), '__DIFFUSION_AUTHOR_EMAIL__', '__AUTHOR_EMAIL__')
WHERE module = 'diffusion'
AND content_lines IS NOT NULL
AND (
	INSTR(content_lines, '__DIFFUSION_REF__') > 0
	OR INSTR(content_lines, '__DIFFUSION_LABEL__') > 0
	OR INSTR(content_lines, '__DIFFUSION_PROJECT_REF__') > 0
	OR INSTR(content_lines, '__DIFFUSION_PROJECT_LABEL__') > 0
	OR INSTR(content_lines, '__DIFFUSION_AUTHOR_FULLNAME__') > 0
	OR INSTR(content_lines, '__DIFFUSION_AUTHOR_EMAIL__') > 0
);

UPDATE llx_actioncomm
SET elementtype = 'diffusiondoc@diffusion'
WHERE elementtype = 'diffusion@diffusion'
AND code IN ('DIFFUSION_CREATE', 'DIFFUSION_VALIDATE', 'DIFFUSION_SENDMAIL', 'DIFFUSION_SETDIFFUSED', 'DIFFUSION_BACKTODRAFT', 'DIFFUSION_DELETE', 'DIFFUSION_CANCEL', 'DIFFUSION_REOPEN', 'DIFFUSION_DIFFUSION_MODIFY');

UPDATE llx_actioncomm
SET elementtype = 'diffusioncontact@diffusion'
WHERE elementtype = 'diffusion@diffusion'
AND code IN ('DIFFUSIONCONTACT_INSERT', 'DIFFUSIONCONTACT_DELETELINE', 'DIFFUSIONCONTACT_UPDATELINE', 'DIFFUSIONCONTACT_DELETEALL');
