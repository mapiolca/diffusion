-- Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
--
-- Populate business events for diffusion module.

UPDATE llx_c_action_trigger
SET elementtype = 'diffusion@diffusion'
WHERE code IN ('DIFFUSION_CREATE', 'DIFFUSION_VALIDATE', 'DIFFUSION_SENDMAIL', 'DIFFUSION_SETDIFFUSED', 'DIFFUSION_BACKTODRAFT', 'DIFFUSION_DELETE');

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
