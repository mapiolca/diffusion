# CHANGELOG MODULE DIFFUSION FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

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
