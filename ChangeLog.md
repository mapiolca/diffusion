# CHANGELOG MODULE DIFFUSION FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## 1.3.0 (15/06/2026)

- FR: Correction de la métadonnée native d'activation Dolibarr en laissant `_init()` / `_remove()` gérer `MAIN_MODULE_DIFFUSION`, ce qui restaure l'affichage de la dernière version d'activation.
- EN: Fixed native Dolibarr activation metadata by letting `_init()` / `_remove()` manage `MAIN_MODULE_DIFFUSION`, restoring the last activation version display.
- FR: Alignement du socle de compatibilité sur Dolibarr v20 et PHP 8.0, avec ajout d'une page de réglages Compatibilité centralisée.
- EN: Aligned compatibility baseline to Dolibarr v20 and PHP 8.0, with a new centralized Compatibility settings page.
- FR: Renforcement CSRF, droits serveur, mises à jour Ajax whitelistees, intégration Multicompany et chemins documentaires basés sur `getMultidirOutput()`.
- EN: Strengthened CSRF, server-side permissions, whitelisted Ajax updates, Multicompany integration and document paths based on `getMultidirOutput()`.
- FR: Complément des hooks/triggers Notifications et Agenda natifs avec substitutions pour les modèles d'e-mails.
- EN: Completed native Notifications and Agenda hooks/triggers with substitutions for email templates.
- FR: Correction des événements Agenda Diffusion avec type natif `AC_OTH_AUTO`, clé `actioncomm.id`, `elementtype` aligné et modèles d'e-mails par défaut pour l'envoi manuel et les notifications.
- EN: Fixed Diffusion Agenda events with native `AC_OTH_AUTO` type, `actioncomm.id` key, aligned `elementtype`, and default email templates for manual sending and notifications.
- FR: Regroupement des modèles de courriel du module sous le type unique `diffusion@diffusion`, avec migration des anciens types visibles et synchronisation technique cachée pour les Notifications natives Dolibarr.
- EN: Grouped module email templates under the single `diffusion@diffusion` type, with migration from previous visible types and hidden technical synchronization for native Dolibarr Notifications.
- FR: Correction de l'activation des modèles de courriel en respectant la clé unique native `entity, label, lang` et en suffixant les miroirs techniques cachés.
- EN: Fixed email template activation by respecting the native `entity, label, lang` unique key and suffixing hidden technical mirrors.
- FR: Restauration du picto Diffusion dans les Notifications natives en utilisant `diffusion@diffusion` comme type visible unique des modèles de courriel.
- EN: Restored the Diffusion picto in native Notifications by using `diffusion@diffusion` as the single visible email template type.
- FR: Fusion idempotente des anciens types visibles de modèles de courriel Diffusion vers `diffusion@diffusion`, avec archivage des doublons hérités et conservation des choix administrateur.
- EN: Added idempotent merging of legacy visible Diffusion email template types into `diffusion@diffusion`, with archived legacy duplicates and preserved administrator choices.
- FR: Déclaration explicite des propriétés `trackid` et `diffusion` pour éviter les dépréciations PHP liées aux propriétés dynamiques.
- EN: Explicitly declared `trackid` and `diffusion` properties to avoid PHP dynamic property deprecation notices.

## 1.2.4 (15/06/2026)

- FR: Correction du sélecteur de projet sur la fiche diffusion pour afficher en édition les projets liés à un tiers comme en création.
- EN: Fixed the project selector on the diffusion card so projects linked to a third party are available in edit mode as they are in create mode.

## 1.2.3 (31/05/2026)

- FR: corrige l'intégration aux projets via l'utilisation des types de contacts du module "Projet" plutôt que les types de contacts "Diffusion" quand celui-ci est actif
- EN: fix projects integration using project contact type when "Project" module is active instead of "Diffusion" contacts type.

## 1.2.2

- FR: Refonte du rendu PDF de la description HTML avec pagination robuste des tableaux : découpe multi-pages, répétition de l'en-tête à chaque page et respect systématique de la zone avant pied de page.
- EN: Reworked HTML description PDF rendering with robust table pagination: multi-page splitting, header repetition on each page, and strict respect of the area before footer.
- FR: Normalisation avancée des tableaux HTML pour le PDF : conversion automatique de la première ligne en en-tête répétable quand nécessaire et alignement stable des colonnes.
- EN: Added advanced HTML table normalization for PDF: automatic conversion of first row into repeatable header when needed and stable column alignment.
- FR: Harmonisation du style tableau PDF (en-têtes grisés, bordures fines, cellules alignées à gauche/centrées verticalement) et meilleure compatibilité TCPDF via styles inline.
- EN: Harmonized PDF table styling (gray headers, thin borders, left-aligned/middle cells) and improved TCPDF compatibility through inline styles.
- FR: Amélioration du calcul des largeurs de colonnes basé sur le contenu non césurable (mot le plus long), avec plafonnement de la colonne de référence à 75mm, plancher à 5mm et redistribution équitable du reste entre toutes les colonnes.
- EN: Improved column width computation based on non-breakable content (longest word), with a 75mm reference-column cap, 5mm floor, and equal redistribution of remaining width across all columns.
- FR: Encadrement renforcé des images HTML dans la description PDF (hauteur max 100mm et largeur max limitée à la zone utile).
- EN: Strengthened HTML image constraints in PDF descriptions (max height 100mm and max width limited to usable area).
- FR: Correction du rendu de la description sur la card diffusion pour afficher systématiquement le HTML saisi.
- EN: Fixed description rendering on the diffusion card to always display entered HTML content.

## 1.2.1

- FR: Passage du formulaire de description diffusion sur le profil d'éditeur `dolibarr_mailings` avec alignement du rendu HTML sur la constante `FCKEDITOR_ENABLE_MAILINGS`.
- EN: Switched the diffusion description form to the `dolibarr_mailings` editor profile and aligned HTML rendering with the `FCKEDITOR_ENABLE_MAILINGS` setting.

## 1.2

- FR: Ajout de la gestion des modèles de diffusion (`is_template`) avec conversion d'une diffusion en modèle et création d'une diffusion à partir d'un modèle.
- EN: Added diffusion template management (`is_template`) with conversion from diffusion to template and creation of a diffusion from a template.
- FR: Ajout du suivi de provenance (`model_source`) et d'un onglet « Diffusions générées » pour visualiser les diffusions créées depuis un modèle.
- EN: Added source tracking (`model_source`) and a “Generated diffusions” tab to view diffusions created from a template.
- FR: Adaptation des listes/cartes pour les modèles (colonnes dédiées, restrictions d'actions, statut « Modèle », masquage des zones non pertinentes).
- EN: Adapted lists/cards for templates (dedicated columns, action restrictions, “Template” status, hidden non-relevant areas).

## 1.1.1

- FR: Correction des contrôles de droits dans les hooks projet pour prendre en charge la permission `diffusiondoc` et rétablir l'affichage des diffusions liées pour les utilisateurs non administrateurs.
- EN: Fixed permission checks in project hooks to include `diffusiondoc` rights and restore linked diffusion visibility for non-admin users.
- FR: Amélioration de la mise en page PDF standard diffusion : pagination robuste des sections contacts et documents joints avec reprise sur page suivante avant le pied de page.
- EN: Improved standard diffusion PDF layout with robust pagination for contacts and attachments sections, continuing on next page before footer overlap.
- FR: Ajout de la clé de traduction `Diffusion@diffusion` pour afficher correctement le libellé du module dans la page des notifications.
- EN: Added `Diffusion@diffusion` translation key to correctly render module label on the notifications page.
## 1.1

- FR: Ajout de l'intégration du module Diffusion dans le menu de création rapide (quick add) avec une entrée de création directe.
- EN: Added Diffusion module integration into the quick add dropdown with a direct create entry.

## 1.0.1

- FR: Correction de la gestion des liens de contacts diffusion pour éviter les mauvaises associations avec un projet quand les identifiants coïncident.
- EN: Fix contact link handling for diffusion contacts to avoid wrong project associations when IDs match.
- FR: Correction de l'inclusion manquante de la classe `DiffusionContact` sur le point d'entrée AJAX on/off.
- EN: Fix missing `DiffusionContact` class include in AJAX on/off endpoint.

## 1.0

- FR: Version initiale.
- EN: Initial version.
