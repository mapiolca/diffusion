# Project overview regression

Run from the Diffusion module root, with PHP 8.0+ and PDO SQLite:

```sh
php -d extension=pdo_sqlite test/project_overview_hooks.php ../dolibarr/htdocs ../lmdbadvancedproject 23.0.4
```

If PDO SQLite is already enabled, omit `-d extension=pdo_sqlite`. The first two arguments are paths to the native core and the companion module. The optional third argument selects a Git ref of **HookManager only** from the core repository. Omitting it uses the checked-out HookManager. No database configuration, `main.inc.php`, business writes, module activation or external call is used.

The runner executes the actual hooks of both modules through the native HookManager in six orders, with two authors and another reader. It covers allocation rights, preservation of an unrelated module's entry, read/write separation, counters, disabled Diffusion and allocation settings, project denial, and entity/project SQL filters. SQLite contains disposable fixtures only. User permissions, shared entities and Diffusion link/status rendering are simulated. The native Project class handles the public-project case; the private-project denial is a controlled test double.

Before the fix, either module's positive `completeListOfReferent()` return replaced earlier results. Depending on allocation permissions, LMDB Advanced Project could remove Diffusion entirely. Neither registration hook reads a diffusion's author. Both now return `0` to add results, while the Diffusion rendering hooks retain their replacement return value.

## Evidence and limits — 2026-09-16

The conflict was reproduced with the original hook behavior. The regression runner passes under PHP 8.4.22 with native HookManager sources from Dolibarr 20.0.0, 23.0.4 and 24.0.0. Other loaded core declarations come from the local 25.0.0-alpha checkout; these are isolated hook simulations, **not full compatibility tests** for those Dolibarr/PHP pairs.

The merge/replacement contract was read in `HookManager::executeHooks()` on the immutable Dolibarr 23.0.4 commit [`cb82037066c1c71f7c867482e9e92975222dfc14`](https://github.com/Dolibarr/dolibarr/blob/cb82037066c1c71f7c867482e9e92975222dfc14/htdocs/core/class/hookmanager.class.php). Native project authorization is checked separately with `Project::restrictedProjectArea()` and entity filters remain applied to Diffusion queries.

PHP syntax checks were run. PHPStan was unavailable locally: Diffusion has no configuration, and LMDB Advanced Project's configured level-5 runner has no available PHPStan executable/PHAR. No configuration or baseline was weakened.

Before deployment acceptance, verify both modified files are served, compare the affected accounts' actual rights and entity, then test project overview, direct card access, private projects and Multicompany on the real v23 and v24 instances. Deployment, PHP 8.0 execution and real Multicompany behavior have not been tested. The existing published ChangeLog entries and module versions are unchanged.
