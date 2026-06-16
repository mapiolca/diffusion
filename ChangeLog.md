# CHANGELOG MODULE DIFFUSION FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## 1.3.0 (16/06/2026)

- FR: Correction des permissions d'accès aux PDF et vignettes de diffusion en alignant les URLs et le stockage documentaire sur le chemin natif `diffusion/diffusiondoc/<REF>/`.
- EN: Fixed access permissions for diffusion PDFs and previews by aligning document URLs and storage on the native `diffusion/diffusiondoc/<REF>/` path.
- FR: Ajout d'une migration conservatrice des documents créés dans l'ancien chemin plat `diffusion/<REF>/`, avec mise à jour ECM et `last_main_doc` uniquement pour les fichiers déplacés sans conflit.
- EN: Added a conservative migration for documents created in the former flat `diffusion/<REF>/` path, updating ECM and `last_main_doc` only for files moved without conflict.
- FR: Remplacement du fallback d'affichage des anciens documents par une migration vers le chemin natif compatible permissions.
- EN: Replaced the old-document display fallback with a migration to the native permission-compatible path.
- FR: Harmonisation des entêtes des onglets Diffusion, Fichiers joints et Événements/Agenda, ajout de la miniature du PDF généré dans l'entête et régénération automatique du PDF après modification des pièces jointes.
- EN: Harmonized the Diffusion, Attached files and Events/Agenda tab headers, added the generated PDF preview to the header and automatically regenerated the PDF after attached-file changes.
- FR: Alignement du bloc des derniers événements de la fiche Diffusion sur le réglage natif Dolibarr `MAIN_SIZE_SHORTLIST_LIMIT`.
- EN: Aligned the latest events block on the Diffusion card with the native Dolibarr `MAIN_SIZE_SHORTLIST_LIMIT` setting.
- FR: Alignement des switches de modes de contact Diffusion sur le paramètre natif Dolibarr de désactivation JavaScript/Ajax, avec fallback non-Ajax sécurisé et retour fiche.
- EN: Aligned Diffusion contact method switches with the native Dolibarr JavaScript/Ajax disable setting, with a secured non-Ajax fallback and card redirect.
- FR: Correction de la métadonnée native d'activation Dolibarr en laissant `_init()` / `_remove()` gérer `MAIN_MODULE_DIFFUSION`, ce qui restaure l'affichage de la dernière version d'activation.
- EN: Fixed native Dolibarr activation metadata by letting `_init()` / `_remove()` manage `MAIN_MODULE_DIFFUSION`, restoring the last activation version display.
- FR: Alignement du socle de compatibilité sur Dolibarr v20 et PHP 8.0, avec ajout d'une page de réglages Compatibilité centralisée.
- EN: Aligned compatibility baseline to Dolibarr v20 and PHP 8.0, with a new centralized Compatibility settings page.
- FR: Renforcement CSRF, droits serveur, mises à jour Ajax whitelistees, intégration Multicompany et chemins documentaires basés sur `getMultidirOutput()`.
- EN: Strengthened CSRF, server-side permissions, whitelisted Ajax updates, Multicompany integration and document paths based on `getMultidirOutput()`.
- FR: Complément des hooks/triggers Notifications et Agenda natifs avec substitutions pour les modèles d'e-mails.
- EN: Completed native Notifications and Agenda hooks/triggers with substitutions for email templates.
- FR: Déclaration des variables de substitution Diffusion dans l'aide native des modèles de courriel, avec libellés français et anglais.
- EN: Declared Diffusion substitution variables in native email template help, with French and English labels.
- FR: Ajout de l'aide native des variables Diffusion dans les modèles de courriel et les descriptions de modèles, avec substitution de la description à la création depuis modèle.
- EN: Added native Diffusion variable help in email templates and template descriptions, with description substitution when creating from a template.
- FR: Amélioration des listes Diffusion avec tri par défaut métier et meilleure prise en charge des filtres/ordres natifs de `/admin/defaultvalues.php`.
- EN: Improved Diffusion lists with business default sorting and better support for native `/admin/defaultvalues.php` filters/sort orders.
- FR: Amélioration de l'import des contacts projet avec une sélection décochée par défaut et un lien de sélection globale.
- EN: Improved project contact import with unchecked rows by default and a select-all link.
- FR: Alignement de la fiche principale sur la gestion native des fichiers joints et régénération non bloquante du PDF après upload.
- EN: Aligned the main card with native attached-file handling and added non-blocking PDF regeneration after upload.
- FR: Correction de l'upload natif par glisser-déposer des pièces jointes Diffusion en exposant l'élément `diffusiondoc` aux propriétés documentaires Dolibarr.
- EN: Fixed native drag-and-drop upload for Diffusion attachments by exposing the `diffusiondoc` element to Dolibarr document properties.
- FR: Et plus....
- EN: And more...

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
