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
php -d extension=pdo_sqlite test/document_context.php ../dolibarr/htdocs
php -d extension=pdo_sqlite test/numbering.php ../dolibarr/htdocs
node test/attachment_upload.js
```

The PHP runner uses the actual native `EcmFiles`, `dol_move()`, `dol_move_dir()`, directory and path functions. Its SQL adapter uses disposable in-memory SQLite; its files live in a unique temporary directory under `test/`, removed at shutdown. It never loads `main.inc.php`, ERP configuration or an instance database. Rights, numbering and trigger outcomes are controlled fixtures; extrafields are empty. SQLite strips `FOR UPDATE`, so these tests do not prove real MySQL locking or nested rollback behavior.

Coverage includes exact public-key/ECM-ID preservation and identical file bytes after validation in entities 1 and 2, canonical/legacy layouts, accented names, reference prefixes, main-document paths, SQL/trigger/commit failure recovery, target collisions, stale validation, conservative recovery of a shared orphan with a native neutral mirror, ambiguous public identities, revoked sharing, absent indexes and missing owner-directory configuration, simulated shared-owner scope, traversal and automatic-sharing selection. Public download resolution is checked against the ECM path and file bytes; the HTTP `document.php` endpoint is not executed.

The Node runner checks the actual module completion code with a simulated DOM and Ajax transport: supplier orders, invoices, customer orders, projects and products with the same ID remain untouched; foreign endpoints/IDs, duplicate initialization, multiple files, partial success, invalid/empty responses and PDF failures are covered. No browser request or actual HTTP upload is made.

`document_context.php` reuses the attachment fixtures (114 assertions), then adds **17 assertions on v25, 10 on older cores** with the native object resolver, `CommonObject::fetchCommon()`, HookManager and PDF model. On a core exposing `checkUserAccessToDocumentObject()`, it executes that function and `restrictedArea()` too; older versions exercise the existing native document permission check. The v25 preview refusal was reproduced before the fix (reader has only `diffusion.diffusiondoc.read`, result 0). The existing `elementproperties` registration loads the hook class; the native manager retains its loaded hooks for the subsequent area check. No new global hook is added. Older installations must reload the module to refresh persisted registrations, as confirmed on develop. The tests cover reader/admin denial, external and unshared owners, successive hook results, write denial, native issuer preparation, controlled missing-issuer failure, uploaded-file preservation and actual Ajax bootstrap flags. They do not render a complete PDF or execute an HTTP endpoint.

## Numbering lookup follow-up — 2026-10-22

The user reported missing-file warnings on **Dolibarr 25.0.0-alpha** and explicitly had not tested other instances. The old loader was reproduced at `class/diffusion.class.php:1429`: `@include_once` still reached the error handler for absent core/third-party candidates before finding Diffusion's model. The corrected loader resolves complete filenames with native `dol_buildpath(..., 0, 2)`, loads only existing readable files, stops on the selected provider, and checks the expected numbering contract. It retains provider order, bundled models and the historical standard default without changing configuration. Invalid selections return a translated object error, without HTML or physical paths.

`numbering.php` reuses the attachment checks and adds **24 assertions** against the real loader, standard and advanced models, with disposable external-provider fixtures. Missing-file warnings are exceptions even when suppressed with `@`. It covers absent/empty/default settings, repeated calls, missing registrations, alternate roots, missing/invalid models, mask errors, entity counters, no counter writes, and validation rollback preserving the specific error and attachments. Only MySQL `SUBSTRING(ref FROM n)` is adapted to SQLite syntax; this does not test real MySQL concurrency.

These checks pass locally under **PHP 8.4.22** on the six source revisions listed below (including local v25-alpha). Dolibarr 20 emits its existing native nullable-parameter deprecation under PHP 8.4; the numbering assertions still pass. The six-version CI also runs this new test with each supported PHP pair. These are isolated integrations, not trials on other user instances. The native resolver's third parameter exists in each inspected revision. PHP syntax, the 17 v25 document-context assertions and 43 JavaScript assertions pass; PHPStan remains unavailable.

The numbering correction was deployed to develop through cPanel at **`012102f3120148c62b3087229ebbb989a3e33010`**. All six CI jobs passed on that SHA ([run 39738030168](https://github.com/mapiolca/diffusion/actions/runs/39738030168)). A new recipe **21**, `RECETTE NUMÉROTATION — 22 octobre 2026`, was created without recipients or files; recipe 20 already had concurrent user uploads and was left unchanged. Recipe 21 validated as `LTR2610-0005` without the reported numbering includes in the returned page's DOM.

This trial exposed a separate collision gap: `relocate()` returned early for a draft without a source directory, before checking the occupied target. Recipe 21 therefore adopted the pre-existing canonical directory and native validation regenerated its main PDF. Its existing attached quote remained listed. This was an unintended effect of the trial; no backup of the previous main PDF was captured, so its previous content has not been restored. Recipe 21 and that directory are retained for review; deleting the recipe could remove pre-existing documents. Do not treat this trial as a successful attachment relocation or public-link preservation check.

The additional correction checks both canonical and legacy target directories **before** returning for an empty draft. The fixture suite now has **114 assertions**, including 28 new checks of empty/populated drafts against occupied canonical/legacy targets, preservation of file bytes and ECM ownership/share keys, rollback, and successful validation of an empty draft to a free target. These 114 checks pass locally on v25-alpha; the pinned CI covers the other PHP/core pairs. No automatic global data repair or removal is performed.

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

PHP syntax checks pass. PHPStan is not installed/configured in this module or available on PATH; no baseline or analysis level was changed. Before deployment acceptance, complete all three tabs on the served 1.4.0 code, read-only/admin-without-right accounts, CSRF refusal and success, multipart partial failure, public HTTP links after successful validation, two real Multicompany entities with shared-object consultation, parent-project denial, outgoing symlinks, and real SQL/commit failure handling. These scenarios remain unverified unless explicitly listed below.

## Partial live trial — develop, 2026-10-19

- Authorized test instance: `develop.lesmetiersdubatiment.fr`, Dolibarr **25.0.0-alpha**, core **`6033765a10c40a251becb58f4ec266ff9a74e8d3`** as reported by its cPanel checkout, PHP **8.3.35** as displayed by the native PHP information page, Multicompany enabled, TEST 1, authenticated `superAdmin`. This is not a read-only-account or cross-entity trial.
- Module **1.4.0**, functional commits through **`efe7692b6e2bd5e1d0cbf8e16341b572ed045388`**, pulled into the develop module checkout through cPanel GitControl. The initial preview refusal was reproduced. The corrected PDF opened after the native module reload refreshed persisted hook registrations; the module remained enabled. No core or other module was changed by this task.
- Created draft **20**, labelled `RECETTE PJ 1.4.0 — 19 octobre 2026`, with no recipients. Two non-business text files were uploaded together using the native file chooser and standard multipart form. Both were listed, the draft PDF was generated, and a public HTTP link returned the expected test content. A first attempt using dot-prefixed fixture names was rejected by the native filename rule, with no attachment created; corrected names were then used.
- Validation was refused before relocation: the native numbering model returned **`LTR2610-0005`**, whose canonical document directory already existed. The native journal at **15:16:10 and 15:19:09 UTC** showed this reference followed by **ROLLBACK**. The draft, files and public HTTP content remained intact. The existing directory was inspected without changing it. This validates collision protection and rollback, **not** successful public-link preservation across a real validation; that scenario remains covered only by fixtures until the existing-data conflict is resolved.
- Browser automation could use keyboard controls and DOM inspection, but pointer actions and screenshot capture repeatedly failed. Physical drag and drop, the post-drop Ajax endpoint, visual PDF inspection and a second real entity remain to be tested. No screenshot proof was obtained. Draft 20 and its harmless test files remain available for review; no existing diffusion was deleted or globally repaired.
- The six pinned CI jobs passed on `efe7692`; inspect the PR's latest head separately after subsequent documentation changes. Initial failures of the new test on v20–24 were corrected by respecting their older document permission contract; the missing-issuer log also no longer calls a helper absent from v20.
