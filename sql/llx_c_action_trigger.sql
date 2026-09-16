-- Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
--
-- Populate business events for diffusion module.

UPDATE llx_c_action_trigger
SET elementtype = 'diffusion@diffusion'
WHERE code IN (
	'DIFFUSION_CREATE',
	'DIFFUSION_VALIDATE',
	'DIFFUSION_SENDMAIL',
	'DIFFUSION_SETDIFFUSED',
	'DIFFUSION_BACKTODRAFT',
	'DIFFUSION_DELETE',
	'DIFFUSION_CANCEL',
	'DIFFUSION_REOPEN',
	'DIFFUSION_DIFFUSION_MODIFY'
);

UPDATE llx_c_action_trigger
SET elementtype = 'diffusion@diffusion'
WHERE code IN (
	'DIFFUSIONCONTACT_INSERT',
	'DIFFUSIONCONTACT_DELETELINE',
	'DIFFUSIONCONTACT_UPDATELINE',
	'DIFFUSIONCONTACT_DELETEALL'
);

UPDATE llx_actioncomm
SET elementtype = 'diffusiondoc@diffusion'
WHERE elementtype = 'diffusion@diffusion'
AND code IN (
	'DIFFUSION_CREATE',
	'DIFFUSION_VALIDATE',
	'DIFFUSION_SENDMAIL',
	'DIFFUSION_SETDIFFUSED',
	'DIFFUSION_BACKTODRAFT',
	'DIFFUSION_DELETE',
	'DIFFUSION_CANCEL',
	'DIFFUSION_REOPEN',
	'DIFFUSION_DIFFUSION_MODIFY'
);

UPDATE llx_actioncomm
SET elementtype = 'diffusioncontact@diffusion'
WHERE elementtype = 'diffusion@diffusion'
AND code IN (
	'DIFFUSIONCONTACT_INSERT',
	'DIFFUSIONCONTACT_DELETELINE',
	'DIFFUSIONCONTACT_UPDATELINE',
	'DIFFUSIONCONTACT_DELETEALL'
);

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang)
SELECT 'DIFFUSION_CREATE', 'Création diffusion', 'Déclenché quand une diffusion est créée.', 'diffusion@diffusion', 2000
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM llx_c_action_trigger WHERE code = 'DIFFUSION_CREATE');

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang)
SELECT 'DIFFUSION_VALIDATE', 'Validation diffusion', 'Déclenché quand une diffusion passe au statut validé.', 'diffusion@diffusion', 2001
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM llx_c_action_trigger WHERE code = 'DIFFUSION_VALIDATE');

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang)
SELECT 'DIFFUSION_SENDMAIL', 'Envoi e-mail diffusion', 'Déclenché quand un e-mail est envoyé depuis une diffusion.', 'diffusion@diffusion', 2002
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM llx_c_action_trigger WHERE code = 'DIFFUSION_SENDMAIL');

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang)
SELECT 'DIFFUSION_SETDIFFUSED', 'Diffusion remise', 'Déclenché quand une diffusion passe au statut diffusé/remis.', 'diffusion@diffusion', 2003
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM llx_c_action_trigger WHERE code = 'DIFFUSION_SETDIFFUSED');

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang)
SELECT 'DIFFUSION_BACKTODRAFT', 'Retour brouillon diffusion', 'Déclenché quand une diffusion repasse en brouillon.', 'diffusion@diffusion', 2004
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM llx_c_action_trigger WHERE code = 'DIFFUSION_BACKTODRAFT');

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang)
SELECT 'DIFFUSION_DELETE', 'Suppression diffusion', 'Déclenché quand une diffusion est supprimée.', 'diffusion@diffusion', 2005
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM llx_c_action_trigger WHERE code = 'DIFFUSION_DELETE');

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang)
SELECT 'DIFFUSION_CANCEL', 'Annulation diffusion', 'Déclenché quand une diffusion est annulée.', 'diffusion@diffusion', 2006
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM llx_c_action_trigger WHERE code = 'DIFFUSION_CANCEL');

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang)
SELECT 'DIFFUSION_REOPEN', 'Réouverture diffusion', 'Déclenché quand une diffusion est rouverte.', 'diffusion@diffusion', 2007
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM llx_c_action_trigger WHERE code = 'DIFFUSION_REOPEN');

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang)
SELECT 'DIFFUSION_DIFFUSION_MODIFY', 'Modification diffusion', 'Déclenché quand une diffusion est modifiée.', 'diffusion@diffusion', 2008
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM llx_c_action_trigger WHERE code = 'DIFFUSION_DIFFUSION_MODIFY');

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang)
SELECT 'DIFFUSIONCONTACT_INSERT', 'Ajout contact diffusion', 'Déclenché quand un contact est ajouté à une diffusion.', 'diffusion@diffusion', 2010
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM llx_c_action_trigger WHERE code = 'DIFFUSIONCONTACT_INSERT');

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang)
SELECT 'DIFFUSIONCONTACT_DELETELINE', 'Retrait contact diffusion', 'Déclenché quand un contact est retiré d’une diffusion.', 'diffusion@diffusion', 2011
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM llx_c_action_trigger WHERE code = 'DIFFUSIONCONTACT_DELETELINE');

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang)
SELECT 'DIFFUSIONCONTACT_UPDATELINE', 'Modification contact diffusion', 'Déclenché quand un statut de contact de diffusion est modifié.', 'diffusion@diffusion', 2012
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM llx_c_action_trigger WHERE code = 'DIFFUSIONCONTACT_UPDATELINE');

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang)
SELECT 'DIFFUSIONCONTACT_DELETEALL', 'Suppression contacts diffusion', 'Déclenché quand tous les contacts d’une diffusion sont supprimés.', 'diffusion@diffusion', 2013
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM llx_c_action_trigger WHERE code = 'DIFFUSIONCONTACT_DELETEALL');
