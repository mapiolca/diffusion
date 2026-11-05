# Consignes locales — Diffusion

Les règles Dolibarr complètes sont maintenues dans le dépôt canonique https://github.com/mapiolca/dolibarr_agent.md et synchronisées dans le `AGENTS.md` global de Codex.

## Chargement et paquet installable

- Tout point d’entrée PHP livré, y compris sous `test/`, `admin/`, `ajax/` ou dans un diagnostic, doit charger Dolibarr avec au moins deux tentatives explicites adaptées à sa profondeur : installation sous `htdocs/diffusion` et sous `htdocs/custom/diffusion`.
- Utiliser des instructions `include`/`require` distinctes contenant littéralement `main.inc.php` ou `master.inc.php`, comme le modèle ModuleBuilder. Une boucle qui inclut une variable peut fonctionner à l’exécution et néanmoins être refusée par `validateZipFile()` / `analyzeDirContents()`.
- Vérifier les deux dispositions, l’arrêt propre sans environnement et le paquet final avec le validateur natif des versions ciblées. Un simple lint ou un comptage maison ne remplace pas cette vérification.
- Conserver le périmètre de sécurité, les droits et le CSRF du point d’entrée ; ne pas modifier le core ni assouplir le validateur.
- Pour cette correction de packaging demandée, conserver la version **1.4.3**. Ne pas déplacer ni écraser le tag existant ; identifier le commit du ZIP corrigé séparément dans la release.
