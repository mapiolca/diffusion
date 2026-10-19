# Diffusion for Dolibarr ERP/CRM

Version du code / Code version: **1.4.0**.

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
- Envoi de diffusion par e-mail avec modèles de courriel natifs sous le type unique **Diffusions**.
- Création automatique d’événements agenda sur les actions clés (validation, retour brouillon, envoi e-mail, marquage diffusé/remis, suppression).
- Événements métier exposés aux Notifications natives Dolibarr avec modèles de courriel sélectionnables sous le même type **Diffusions**.
- Compatibilité **Multicompany** (partage possible des diffusions et de la numérotation via options dédiées).

### Compatibilité

- Module conçu pour Dolibarr (module externe dans `htdocs/custom/diffusion`).
- Dépendances module :
	- **Projet** (`modProjet`)
	- **Tiers** (`modSociete`)
- Version minimum Dolibarr déclarée : **20+**.
- Version PHP minimum déclarée : **8.0+**.

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

### Pièces jointes et liens publics

Après déploiement, effectuer un rechargement forcé du navigateur pour remplacer l’ancien script éventuellement en cache.

Recharger également Diffusion depuis la liste des modules pour actualiser les hooks enregistrés d’une ancienne installation, en conservant les réglages existants.

Le glisser-déposer natif est disponible dès Dolibarr 20 sur les onglets **Diffusion**, **Fichiers joints** et **Événements/Agenda**, pour une diffusion enregistrée et un utilisateur disposant des droits de lecture et d’écriture. Il suit le réglage JavaScript/Ajax de Dolibarr. L’upload classique reste disponible dans Fichiers joints. Les modèles de diffusion ne reçoivent pas de pièces jointes.

L’aperçu et le téléchargement d’un document existant utilisent le droit de lecture de la diffusion, y compris avec le contrôle par objet de Dolibarr 25. La régénération après dépôt charge la société émettrice native ; une société indisponible produit un message explicite et conserve les fichiers ajoutés.

Une validation conserve les clés publiques existantes des pièces jointes, y compris avec Multicompany. Les nouveaux fichiers suivent l’option de partage automatique ; une validation ou une réparation ne crée pas de nouvelle clé et ne réactive pas un partage révoqué.

Pour les anciens liens cassés, utiliser **Fichiers joints > Réparer les liens des pièces jointes**. Cette action concerne uniquement la diffusion affichée et ne nécessite aucun réupload. Elle conserve l’ancienne URL lorsque son index et sa clé peuvent être identifiés avec certitude. Si la clé a disparu, une nouvelle URL doit être créée avec le partage natif. Les conflits sont signalés sans fusion automatique des identités publiques. Consulter [les preuves et limites des tests](test/README.md#attachment-regression--140).

### Configuration

Le module propose une page de configuration dans l’administration pour :

- définir les options de numérotation et de modèles document,
- ajuster les comportements automatiques (événements agenda et notifications),
- paramétrer les options de partage multicompany.

### Permissions

Les droits sont séparés pour les objets **Diffusion** et **Contact de diffusion** avec des niveaux de lecture, création/modification et suppression.

Pensez à attribuer ces droits aux profils concernés avant mise en production.

Dans la vue d’ensemble d’un projet accessible, le bloc Diffusions et ses compteurs respectent le droit de lecture Diffusion et les entités autorisées, quel que soit le créateur des diffusions. La création et la déliaison restent soumises au droit d’écriture. Les hooks ajoutent leur bloc sans remplacer les contributions des autres modules ; déployer également la correction du hook de LMDB Advanced Project lorsque les deux modules sont utilisés. Voir [les tests de cette intégration](test/README.md) pour le scénario de non-régression et les limites de validation.

Le badge de l’onglet **Vue d’ensemble** ajoute une seule fois les diffusions accessibles au compteur natif : trois éléments et cinq diffusions donnent **8**. L’identification du projet utilise l’objet natif transmis au hook, y compris sur Dolibarr 20 à 23 qui ne transmettent pas le paramètre `type` introduit en v24.

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
- Send distributions by email with native email templates under the single **Diffusions** type.
- Automatic agenda event creation on key actions (validate, back to draft, email sent, marked as delivered/distributed, delete).
- Business events exposed to native Dolibarr Notifications with selectable email templates under the same **Diffusions** type.
- **Multicompany** compatibility (optional sharing of distributions and numbering).

### Compatibility

- Designed for Dolibarr as an external module in `htdocs/custom/diffusion`.
- Module dependencies:
	- **Project** (`modProjet`)
	- **Third Party** (`modSociete`)
- Declared minimum Dolibarr version: **20+**.
- Declared minimum PHP version: **8.0+**.

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

### Attachments and public links

Native drag and drop is available from Dolibarr 20 on the **Diffusion**, **Attached files** and **Events/Agenda** tabs for a saved distribution and a user with read/write permission. It follows Dolibarr's JavaScript/Ajax setting; the standard upload remains available. Distribution templates do not accept attachments.

After upgrading, reload Diffusion from the module list to refresh persisted hooks from an older installation while retaining existing settings, then refresh the browser cache.

Previewing and downloading existing documents use the distribution read permission, including Dolibarr 25's per-object check. Regeneration after upload loads the native issuing company; an unavailable issuer produces an explicit error and preserves uploaded files.

Validation preserves existing public attachment URLs, including across entities. New uploads follow the automatic-sharing setting; validation and repair do not generate keys or reactivate revoked sharing. Use **Attached files > Repair attachment links** to recover a reliable old index without reuploading. Missing original keys require a new URL through native sharing. Ambiguous public identities are reported unchanged. See the [test evidence and limits](test/README.md#attachment-regression--140).

### Configuration

The module provides an admin setup page to:

- define numbering and document model options,
- adjust automatic behaviors (agenda events and notifications),
- configure multicompany sharing options.

### Permissions

Permissions are split for **Distribution** and **Distribution Contact** objects with read, create/update, and delete levels.

Make sure these rights are assigned to the target user profiles before production use.

On an accessible project's overview, the Diffusion block and counters require Diffusion read permission and respect authorized entities, regardless of who created the distributions. Creation and unlinking still require write permission. The hooks add their entry without replacing other modules' contributions; deploy the corresponding LMDB Advanced Project hook correction when both modules are used. See the [integration regression tests](test/README.md) for coverage and validation limits.

The **Overview** tab badge adds accessible distributions to the native counter once: three elements and five distributions give **8**. The hook identifies the project from the native object, including on Dolibarr 20–23, which do not provide the `type` parameter introduced in v24.

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
