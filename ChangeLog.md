# CHANGELOG MODULE DIFFUSION FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## 1.3.0 (15/06/2026)

- FR: Renforcement de l'emprise réservée aux pieds de page PDF Diffusion avec mesure réelle du rendu natif et neutralisation des sauts automatiques pendant l'impression du pied.
- EN: Strengthened the reserved area for Diffusion PDF footers with real native footer measurement and disabled automatic page breaks while rendering the footer.
- FR: Recalage des pieds de page PDF Diffusion sur le pattern natif Dolibarr, avec texte libre uniquement sur la dernière page et pied simplifié sur les pages intermédiaires.
- EN: Realigned Diffusion PDF footers with the native Dolibarr pattern, with free text only on the last page and simplified footers on intermediate pages.
- FR: Reprise de la pagination du PDF Diffusion avec marge basse réservée avant rendu, fermeture contrôlée des pages et meilleure prise en compte du texte libre de pied de page.
- EN: Reworked Diffusion PDF pagination with bottom margin reserved before rendering, controlled page finalization and better handling of footer free text.
- FR: Alignement du bloc des derniers événements de la fiche Diffusion sur le réglage natif Dolibarr `MAIN_SIZE_SHORTLIST_LIMIT`.
- EN: Aligned the latest events block on the Diffusion card with the native Dolibarr `MAIN_SIZE_SHORTLIST_LIMIT` setting.
- FR: Correction de l'import des contacts projet depuis la modale Ajax, avec transmission fiable des contacts cochés et ajustement de la taille de la modale au contenu.
- EN: Fixed project contact import from the Ajax modal, with reliable submission of checked contacts and modal sizing adjusted to its content.
- FR: Correction de la pagination du PDF Diffusion pour réserver systématiquement la zone de pied de page lors du rendu des descriptions HTML, tableaux, contacts et pièces jointes.
- EN: Fixed Diffusion PDF pagination to consistently reserve the footer area while rendering HTML descriptions, tables, contacts and attachments.
- FR: Correction des liens d'ouverture des PDF Diffusion pour référencer les fichiers sous `diffusion/<référence>/` au lieu de `diffusion/diffusiondoc/<référence>/`.
- EN: Fixed Diffusion PDF opening links so files are referenced under `diffusion/<reference>/` instead of `diffusion/diffusiondoc/<reference>/`.
- FR: Alignement des switches de modes de contact Diffusion sur le paramètre natif Dolibarr de désactivation JavaScript/Ajax, avec fallback non-Ajax sécurisé et retour fiche.
- EN: Aligned Diffusion contact method switches with the native Dolibarr JavaScript/Ajax disable setting, with a secured non-Ajax fallback and card redirect.
- FR: Correction des contacts de diffusion en laissant l'entité portée par la diffusion parente, sans colonne `entity` sur `diffusion_contact`, pour restaurer l'ajout de contacts et les switches de diffusion.
- EN: Fixed Diffusion contacts by keeping the entity on the parent Diffusion, without an `entity` column on `diffusion_contact`, restoring contact additions and contact switches.
- FR: Migration des variables de descriptions et modèles de courriel vers les substitutions standards Dolibarr (`__REF__`, `__LABEL__`, `__PROJECT_REF__`, `__PROJECT_NAME__`) et ajout des variables `__AUTHOR_*__` basées sur le créateur de la diffusion.
- EN: Migrated description and email template variables to standard Dolibarr substitutions (`__REF__`, `__LABEL__`, `__PROJECT_REF__`, `__PROJECT_NAME__`) and added `__AUTHOR_*__` variables based on the Diffusion creator.
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
- FR: Ajout d'un miroir technique neutre `lang = NULL` pour les modèles de notifications Diffusion afin que `Notify::send()` retrouve le modèle sélectionné quelle que soit la langue du destinataire.
- EN: Added a language-neutral technical mirror with `lang = NULL` for Diffusion notification templates so `Notify::send()` can find the selected template regardless of recipient language.
- FR: Correction du point d'entrée Dolibarr v20 des substitutions Diffusion et localisation des libellés de statut dans la langue de notification.
- EN: Fixed the Dolibarr v20 Diffusion substitution entry point and localized status labels with the notification language.
- FR: Déclaration des variables de substitution Diffusion dans l'aide native des modèles de courriel, avec libellés français et anglais.
- EN: Declared Diffusion substitution variables in native email template help, with French and English labels.
- FR: Ajout de l'aide native des variables Diffusion dans les modèles de courriel et les descriptions de modèles, avec substitution de la description à la création depuis modèle.
- EN: Added native Diffusion variable help in email templates and template descriptions, with description substitution when creating from a template.
- FR: Nettoyage des événements exposés au module Notifications pour masquer les triggers sans action utilisateur active ou trop bruités.
- EN: Cleaned up events exposed to the Notifications module by hiding triggers without active user actions or too noisy.
- FR: Amélioration des listes Diffusion avec tri par défaut métier et meilleure prise en charge des filtres/ordres natifs de `/admin/defaultvalues.php`.
- EN: Improved Diffusion lists with business default sorting and better support for native `/admin/defaultvalues.php` filters/sort orders.
- FR: Amélioration de l'import des contacts projet avec une sélection décochée par défaut et un lien de sélection globale.
- EN: Improved project contact import with unchecked rows by default and a select-all link.
- FR: Alignement de la fiche principale sur la gestion native des fichiers joints et régénération non bloquante du PDF après upload.
- EN: Aligned the main card with native attached-file handling and added non-blocking PDF regeneration after upload.
- FR: Ajout de l'alias natif `Diffusiondoc` pour résoudre correctement les objets liés des événements Agenda `diffusiondoc@diffusion`.
- EN: Added the native `Diffusiondoc` alias so Agenda events linked with `diffusiondoc@diffusion` resolve to the Diffusion object instead of showing as deleted.
- FR: Correction de l'upload natif par glisser-déposer des pièces jointes Diffusion en exposant l'élément `diffusiondoc` aux propriétés documentaires Dolibarr.
- EN: Fixed native drag-and-drop upload for Diffusion attachments by exposing the `diffusiondoc` element to Dolibarr document properties.

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
