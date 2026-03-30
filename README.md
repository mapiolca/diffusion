# Diffusion for Dolibarr ERP/CRM

## 🇫🇷 Français

### Présentation

**Diffusion** est un module externe Dolibarr dédié à la gestion de diffusions de plans et de documents dans le cadre de projets.

Le module permet de créer des fiches de diffusion liées à un projet, de gérer les contacts destinataires, de joindre des documents, de générer des PDF, puis de suivre le cycle de vie complet d’une diffusion (brouillon, validation, envoi, diffusion/remise, retour brouillon, suppression).

### Fonctionnalités principales

- Gestion d’un objet **Diffusion** (création, modification, suppression, changement de statut).
- Liaison d’une diffusion à un **projet** Dolibarr.
- Gestion des **contacts de diffusion**.
- Import des contacts depuis le projet lié.
- Gestion des **modèles de diffusion** (conversion d'une diffusion en modèle, création d'une diffusion à partir d'un modèle).
- Suivi des diffusions générées depuis un modèle (liaison `model_source` et onglet dédié).
- Ajout et suivi des **documents joints**.
- Génération/régénération des documents PDF de diffusion.
- Envoi de diffusion par e-mail.
- Création automatique d’événements agenda sur les actions clés (validation, retour brouillon, envoi e-mail, marquage diffusé/remis, suppression).
- Compatibilité **Multicompany** (partage possible des diffusions et de la numérotation via options dédiées).

### Compatibilité

- Module conçu pour Dolibarr (module externe dans `htdocs/custom/diffusion`).
- Dépendances module :
	- **Projet** (`modProjet`)
	- **Tiers** (`modSociete`)
- Version minimum Dolibarr déclarée : **19+**.
- Version PHP minimum déclarée : **7.1+**.

### Installation

#### 1) Déploiement

Copiez le dossier du module dans :

`htdocs/custom/diffusion`

#### 2) Activation

1. Connectez-vous avec un compte administrateur Dolibarr.
2. Ouvrez **Configuration > Modules/Applications**.
3. Activez le module **Diffusion**.

#### 3) Vérifications après activation

- Vérifiez que les répertoires de données du module sont accessibles en écriture.
- Vérifiez les permissions utilisateurs/groupes liées aux diffusions.
- Vérifiez les constantes de configuration (numérotation, modèles, automatisations agenda).

### Configuration

Le module propose une page de configuration dans l’administration pour :

- définir les options de numérotation et de modèles document,
- ajuster les comportements automatiques (événements agenda),
- paramétrer les options de partage multicompany.

### Permissions

Les droits sont séparés pour les objets **Diffusion** et **Contact de diffusion** avec des niveaux de lecture, création/modification et suppression.

Pensez à attribuer ces droits aux profils concernés avant mise en production.

### Traductions

Les fichiers de langue du module sont disponibles dans :

- `langs/fr_FR/diffusion.lang`
- `langs/en_US/diffusion.lang`
- `langs/de_DE/diffusion.lang`
- `langs/es_ES/diffusion.lang`
- `langs/it_IT/diffusion.lang`

### Arborescence (aperçu)

- `core/modules/modDiffusion.class.php` : descripteur du module.
- `class/` : classes métier (diffusion, contact).
- `admin/` : pages d’administration.
- `lib/` : fonctions utilitaires et intégration interface.
- `sql/` : scripts SQL d’installation/évolution.
- `langs/` : traductions.

### Licence

- Code : **GPL v3** (ou ultérieure).
- Documentation : **GFDL**.

### Ressources

- Site Dolibarr : <https://www.dolibarr.org>
- Dolistore : <https://www.dolistore.com>
- Documentation technique Dolibarr (Doxygen) : <https://doxygen.dolibarr.org/>

---

## 🇺🇸 English (en_US)

### Overview

**Diffusion** is a Dolibarr external module dedicated to distributing plans and documents in project contexts.

The module allows you to create distribution records linked to a project, manage recipient contacts, attach files, generate PDFs, and track the full lifecycle of a distribution (draft, validation, sending, delivered/distributed, back to draft, deletion).

### Main features

- Management of a **Distribution** object (create, update, delete, status changes).
- Link a distribution to a Dolibarr **project**.
- Management of **distribution contacts**.
- Import contacts from the linked project.
- Management of **distribution templates** (convert a distribution to a template, create a distribution from a template).
- Tracking generated distributions from a template (`model_source` linkage and dedicated tab).
- Add and track **attached documents**.
- Generate/regenerate distribution PDF documents.
- Send distributions by email.
- Automatic agenda event creation on key actions (validate, back to draft, email sent, marked as delivered/distributed, delete).
- **Multicompany** compatibility (optional sharing of distributions and numbering).

### Compatibility

- Designed for Dolibarr as an external module in `htdocs/custom/diffusion`.
- Module dependencies:
	- **Project** (`modProjet`)
	- **Third Party** (`modSociete`)
- Declared minimum Dolibarr version: **19+**.
- Declared minimum PHP version: **7.1+**.

### Installation

#### 1) Deployment

Copy the module folder into:

`htdocs/custom/diffusion`

#### 2) Activation

1. Log in with a Dolibarr administrator account.
2. Open **Setup > Modules/Applications**.
3. Enable the **Diffusion** module.

#### 3) Post-activation checks

- Check that module data directories are writable.
- Check user/group permissions for distributions.
- Check module constants (numbering, templates, automatic agenda actions).

### Configuration

The module provides an admin setup page to:

- define numbering and document model options,
- adjust automatic behaviors (agenda events),
- configure multicompany sharing options.

### Permissions

Permissions are split for **Distribution** and **Distribution Contact** objects with read, create/update, and delete levels.

Make sure these rights are assigned to the target user profiles before production use.

### Translations

Module translation files are available in:

- `langs/fr_FR/diffusion.lang`
- `langs/en_US/diffusion.lang`
- `langs/de_DE/diffusion.lang`
- `langs/es_ES/diffusion.lang`
- `langs/it_IT/diffusion.lang`

### Project tree (overview)

- `core/modules/modDiffusion.class.php`: module descriptor.
- `class/`: business classes (distribution, contact).
- `admin/`: admin pages.
- `lib/`: helper and UI integration functions.
- `sql/`: install/upgrade SQL scripts.
- `langs/`: translations.

### License

- Code: **GPL v3** (or later).
- Documentation: **GFDL**.

### Resources

- Dolibarr website: <https://www.dolibarr.org>
- Dolistore: <https://www.dolistore.com>
- Dolibarr technical documentation (Doxygen): <https://doxygen.dolibarr.org/>
