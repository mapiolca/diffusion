# CHANGELOG MODULE DIFFUSION FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## 1.4.3 (2026-11-05)

- FR : Un clone est créé en Brouillon avec la référence provisoire native `(PROV{id})` ; sa validation applique le modèle de numérotation configuré au lieu de conserver une référence `Copy_Of_…`.
- EN: Clones start as Draft with the native `(PROV{id})` provisional reference; validation applies the configured numbering model instead of retaining a `Copy_Of_…` reference.
- FR : Le clone d’une diffusion issue d’un modèle conserve `model_source`, le modèle documentaire et les données métier copiées ; il est comptabilisé parmi les diffusions générées du même modèle source.
- EN: Cloning a template-generated diffusion preserves `model_source`, the document model and copied business data; the clone is counted among the same source template’s generated diffusions.
- FR : La date et l’expéditeur d’envoi, l’auteur de modification et le pointeur du document principal de la source sont réinitialisés sur le nouveau brouillon. Les sources inexistantes, modèles ou hors du périmètre autorisé sont refusées.
- EN: Dispatch date/sender, modification author and the source main-document pointer are cleared on the new draft. Missing sources, templates themselves and unauthorized sources are rejected.
- FR : Aucun changement de schéma ni réactivation nécessaire. Les références `Copy_Of_…` historiques ne sont pas modifiées automatiquement. Recette develop et CI Dolibarr 20–25-alpha documentées dans la PR #31 ; PHPStan indisponible.
- EN: No schema change or module reactivation required. Existing historical `Copy_Of_…` references are not modified automatically. Develop acceptance and Dolibarr 20–25-alpha CI are documented in PR #31; PHPStan unavailable.

## 1.4.2 (2026-11-05)

- FR : Publication du correctif complet de conservation des lettres après envoi : copies temporaires FormMail pour le traitement Agenda, conservation des originaux et refus de l’envoi si la copie échoue. Aucun correctif du core ni changement global de configuration.
- EN: Release the complete post-email letter preservation fix: temporary FormMail copies for Agenda processing, preserved originals and sending refused if copying fails. No core patch or global configuration change.
- FR : Présélection des contacts destinataires à l’ouverture du mail et conservation des choix manuels ; événement unique et traduit pour les changements de statut des contacts.
- EN: Preselect marked recipients when opening email and preserve manual choices; contact status changes create one translated native Agenda event.
- FR : Réparation ciblée des libellés Agenda historiques à l’initialisation par entité, utilisant la colonne SQL native `note`, avec conservation des textes personnalisés. Les PDF déjà déplacés et doublons historiques ne sont pas corrigés automatiquement.
- EN: Targeted historical Agenda label repair during per-entity initialization, using native SQL column `note` and preserving customized content. Previously moved PDFs and historical duplicates are not automatically repaired.
- FR : Cette version inclut les compléments fusionnés après le tag 1.4.1 (`9fff081`), qui reste inchangé. La recette develop et la matrice CI Dolibarr 20–25-alpha sont documentées dans `test/README.md` et la PR #27 ; PHPStan n’a pas été exécuté.
- EN: Includes follow-up fixes merged after tag 1.4.1 (`9fff081`), which remains unchanged. Develop acceptance and Dolibarr 20–25-alpha CI coverage are documented in `test/README.md` and PR #27; PHPStan was not run.

## 1.4.1 (2026-11-05)

- FR : Réparation ciblée des anciens libellés Agenda restés sous forme de clé de traduction, avec conservation des titres et descriptions personnalisés et du périmètre d’entité. La réparation s’exécute à l’initialisation du module, dans la langue choisie pour cette entité.
- EN: Targeted repair of historical Agenda labels containing raw translation keys, preserving custom titles/descriptions and entity scope. Repair runs during module initialization in the selected entity language.

- FR : Les changements de statut d’un contact transmettent leur libellé traduit à l’Agenda natif et ne créent plus un second événement manuel pour la même modification. Les doublons historiques restent à examiner séparément.
- EN: Contact status changes supply translated text to native Agenda and no longer create a second manual event for the same update. Historical duplicates require separate review.

- FR : Envoi de copies temporaires via FormMail pour conserver les originaux même si l’Agenda déplace les pièces jointes après SMTP ; répertoire temporaire utilisateur natif, refus de l’envoi si la copie échoue.
- EN: Send temporary copies through FormMail to retain originals even when Agenda moves attachments after SMTP; use native user temporary storage and refuse sending if copying fails.

- FR : Recherche de la lettre dans le répertoire documentaire de l’entité propriétaire lors de la préparation du mail, y compris après génération, sans sélectionner une archive ZIP à sa place ; prise en charge des noms ODT/ODS incluant le modèle et un horodatage.
- EN: Find the letter in its owning entity's document directory when composing email, including after generation, without selecting a ZIP archive instead; support ODT/ODS filenames containing the template name and a timestamp.
- FR : Le retrait individuel ou global des pièces jointes du mail conserve les fichiers sur disque et ne modifie que la sélection du message. Aucun changement de schéma ni réactivation du module nécessaire.
- EN: Removing one or all email attachments preserves the stored files and only changes the message selection. No schema change or module reactivation is required.

- FR : Présélection des contacts externes destinataires par mail à l’ouverture du formulaire d’envoi d’une diffusion, avec conservation des choix manuels, exclusion des contacts sans adresse et respect du périmètre d’entités existant. Aucun changement de schéma ni réactivation du module nécessaire.
- EN: Preselect external contacts marked as email recipients when opening a distribution email, preserving manual choices, excluding contacts without an email address and retaining the existing entity scope. No schema change or module reactivation is required.

## 1.4.0 (2026-10-23)

- FR : Correction de l’interception des uploads des autres modules et ajout du glisser-déposer natif dans les trois onglets documentaires des diffusions, avec conservation de l’upload classique.
- EN: Fixed interference with other modules' uploads and enabled native drag and drop on the three distribution tabs while retaining standard uploads.
- FR : Correction du refus d’aperçu avec le contrôle documentaire par objet de Dolibarr 25, du chargement de la société émettrice après dépôt et des avertissements de recherche du modèle de numérotation ; conservation de l’entité propriétaire sans Multicompany.
- EN: Fixed preview denial with Dolibarr 25's per-object document check, issuer loading after upload and numbering-model lookup warnings; retained document ownership without Multicompany.
- FR : Conservation des URL publiques à la validation, contrôle des collisions documentaires même pour un brouillon sans fichier et restauration après échec ; réparation par diffusion des index récupérables sans réupload ni réactivation des partages révoqués. Diagnostic et réparation administrateur ciblés pour l’index orphelin historique documenté dans `test/README.md`, avec conservation du fichier, des métadonnées et de la clé publique.
- EN: Preserved public URLs during validation, checked document collisions even for empty drafts with failure recovery, and added per-distribution index repair without reuploading or reactivating revoked sharing. Added scoped administrator diagnosis and repair for the historical orphan index documented in `test/README.md`, preserving its file, metadata and public key.
- FR : Affichage dans le PDF des liens publics déjà actifs indépendamment de la création automatique de liens ; application du réglage de l’entité propriétaire aux nouveaux uploads partagés et réparation de la propriété documentaire manquante sans changer la clé publique.
- EN: Included existing active public links in PDFs independently of automatic link creation; applied the owner's entity setting to new shared-object uploads and repaired missing document ownership without changing the public key.
- FR : Contrôles de droits et d’entité renforcés, refus des chemins documentaires indisponibles ; tests de régression et matrice CI Dolibarr 20–25 (v25 alpha identifiée). Aucun changement de schéma ; recharger le module après mise à jour pour actualiser les hooks persistés d’une ancienne installation.
- EN: Strengthened permission/entity checks and rejected unavailable document paths; added regression tests and a Dolibarr 20–25 CI matrix (identified v25 alpha). No schema change; reload the module after upgrading to refresh persisted hooks from an older installation.

## 1.3.0 (16/06/2026)

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

- FR: Correction du pied de page du PDF standard Diffusion pour éviter la création de pages supplémentaires lorsque TCPDF déclenche un saut de page automatique pendant le rendu du footer.
- EN: Fixed the standard Diffusion PDF footer to prevent extra pages when TCPDF triggers an automatic page break while rendering the footer.
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
