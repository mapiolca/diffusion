# Project overview regression

Run from the Diffusion module root, with PHP 8.0+ and PDO SQLite:

```sh
php -d extension=pdo_sqlite test/project_overview_hooks.php ../dolibarr/htdocs ../lmdbadvancedproject 23.0.2
```

If PDO SQLite is already enabled, omit `-d extension=pdo_sqlite`. The first two arguments are paths to the native core and the companion module. The optional third argument selects a Git ref of **HookManager and `complete_head_from_modules()`** from the core repository. The tab function is extracted unchanged from that ref at runtime; no core implementation is copied into the module. Omitting the ref uses the checked-out sources. No database configuration, `main.inc.php`, business writes, module activation or external call is used.

The runner executes the actual hooks of both modules through the native HookManager in six orders, with two authors and another reader. It covers allocation rights, preservation of an unrelated module's entry, read/write separation, counters, disabled Diffusion and allocation settings, project denial, and entity/project SQL filters. SQLite contains disposable fixtures only. User permissions, shared entities and Diffusion link/status rendering are simulated. The native Project class handles the public-project case; the private-project denial is a controlled test double.

Tab tests use the native `complete_head_from_modules()` function rather than supplying hook parameters manually. They cover the screenshot's **3 + 5 = 8** case through the three project-tab passes (`add/core`, `add/external`, `remove`), one badge and one count query, creation of a missing badge, preservation of other tabs, zero diffusions, missing overview tab, non-project objects, denied readers including administrators, inaccessible projects, disabled Diffusion and shared entities.

Before the fix, either module's positive `completeListOfReferent()` return replaced earlier results. Depending on allocation permissions, LMDB Advanced Project could remove Diffusion entirely. Neither registration hook reads a diffusion's author. Both now return `0` to add results, while the Diffusion rendering hooks retain their replacement return value.

## Evidence and limits — 2026-09-16

The registration conflict was reproduced with the original hook behavior. The badge defect was also reproduced before its fix: native Dolibarr 23.0.2 tab dispatch kept **3** instead of **8**. The original direct badge test supplied `type` manually and therefore missed its absence in older core versions.

The updated runner passes **487 assertions per ref** under PHP 8.4.22 with native HookManager and tab-function sources from Dolibarr **20.0.0, 21.0.0, 22.0.0, 23.0.2 and 24.0.0**. Other loaded core declarations come from the local 25.0.0-alpha checkout; these are isolated hook simulations, **not full compatibility tests** for those Dolibarr/PHP pairs. Tested module baselines: Diffusion 1.3.0 (`4e61a2f` plus this badge patch); LMDB Advanced Project 1.4.0 (`5f8ea79492ade8fd5f597bd6d496ded8786ea158`) with the additive registration fix.

The merge/replacement contract was read in `HookManager::executeHooks()` on the immutable Dolibarr 23.0.4 commit [`cb82037066c1c71f7c867482e9e92975222dfc14`](https://github.com/Dolibarr/dolibarr/blob/cb82037066c1c71f7c867482e9e92975222dfc14/htdocs/core/class/hookmanager.class.php). Native project authorization is checked separately with `Project::restrictedProjectArea()` and entity filters remain applied to Diffusion queries.

The tab hook receives `head` by reference and the native project object. Its `type` parameter is absent in the inspected v20–v23 tags, including [Dolibarr 23.0.2, `functions.lib.php`](https://github.com/Dolibarr/dolibarr/blob/ccef1102e6850b7545be7bad91cf0cc4c74ac6ea/htdocs/core/lib/functions.lib.php), and present in [Dolibarr 24.0.0](https://github.com/Dolibarr/dolibarr/blob/769c7db907099643558e77d7002c109cfda919e5/htdocs/core/lib/functions.lib.php). The Diffusion hook accepts the native Project object on all these versions and still rejects an explicitly different `type`. This uses the existing native hook on the v20 baseline; no newer core feature is reimplemented.

PHP syntax checks were run. PHPStan was unavailable locally: Diffusion has no configuration, and LMDB Advanced Project's configured level-5 runner has no available PHPStan executable/PHAR. No configuration or baseline was weakened.

Before deployment acceptance, verify both modified files are served, compare the affected accounts' actual rights and entity, then test project overview, direct card access, private projects and Multicompany on the real v23 and v24 instances. Deployment, PHP 8.0 execution and real Multicompany behavior have not been tested. The existing published ChangeLog entries and module versions are unchanged.

# Attachment regression — 1.4.0

```sh
php -d extension=pdo_sqlite test/attachments.php ../dolibarr/htdocs
node test/attachment_upload.js
```

The PHP runner uses the actual native `EcmFiles`, `dol_move()`, `dol_move_dir()`, directory and path functions. Its SQL adapter uses disposable in-memory SQLite; its files live in a unique temporary directory under `test/`, removed at shutdown. It never loads `main.inc.php`, ERP configuration or an instance database. Rights, numbering and trigger outcomes are controlled fixtures; extrafields are empty. SQLite strips `FOR UPDATE`, so these tests do not prove real MySQL locking or nested rollback behavior.

Coverage includes exact public-key/ECM-ID preservation and identical file bytes after validation in entities 1 and 2, canonical/legacy layouts, accented names, reference prefixes, main-document paths, SQL/trigger/commit failure recovery, target collisions, stale validation, conservative recovery of a shared orphan with a native neutral mirror, ambiguous public identities, revoked sharing, absent indexes and missing owner-directory configuration, simulated shared-owner scope, traversal and automatic-sharing selection. Public download resolution is checked against the ECM path and file bytes; the HTTP `document.php` endpoint is not executed.

The Node runner checks the actual module completion code with a simulated DOM and Ajax transport: supplier orders, invoices, customer orders, projects and products with the same ID remain untouched; foreign endpoints/IDs, duplicate initialization, multiple files, partial success, invalid/empty responses and PDF failures are covered. No browser request or actual HTTP upload is made.

## Evidence — 2026-10-19

Local results: **86 PHP assertions per source revision**, under **PHP 8.4.22**, and **43 JavaScript assertions** under Node.js. Native source declarations and the optional drag/drop parameter were checked on these revisions:

| Core | Revision | Local result |
|---|---|---|
| 20.0.0 | `697bf01970740a3339cd99cf055b4428fc5e051c` | Passed |
| 21.0.0 | `fd970b582a4d8c5779a2958a4e9f4fce225cf085` | Passed |
| 22.0.0 | `49b9a6d19f3deb6d410c0e9b3310e95be4ea7710` | Passed |
| 23.0.0 | `57a1f05d490a7a80944a8232e9c613e6556d2704` | Passed |
| 24.0.0 | `769c7db907099643558e77d7002c109cfda919e5` | Passed |
| 25.0.0-alpha | `6805f682cb72a0a7e519e0c79f0de5f667eb45e4` | Passed |

For v20–24, the native libraries/classes were read from the pinned Git tags into a disposable test checkout; v25 used the identified local fork checkout. These are isolated integrations, not complete ERP compatibility tests. The new CI matrix pairs PHP 8.0/8.1/8.2/8.3/8.4 with v20/v21/v22/v23/v24 respectively, and PHP 8.4 with upstream v25-alpha at `6033765a10c40a251becb58f4ec266ff9a74e8d3`. CI outcomes must be read on the PR's actual head SHA; a declared job is not evidence of success.

PHP syntax checks pass. PHPStan is not installed/configured in this module or available on PATH; no baseline or analysis level was changed. Before deployment acceptance, test all three tabs on the served 1.4.0 code, read-only/admin-without-right accounts, CSRF refusal and success, actual multipart uploads (including partial failure), public HTTP links before/after validation, two real Multicompany entities with shared-object consultation, parent-project denial, outgoing symlinks, and real SQL/commit failure handling. No instance deployment, browser validation, real Multicompany trial or production repair has been performed.
