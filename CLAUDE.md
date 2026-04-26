# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository role

This repo is the `glial/glial` Composer **library** (`composer.json` → `type: library`, PSR-4 `Glial\\` → `Glial/`). It is not a runnable application on its own. A host project pulls it in as a dependency and is responsible for providing `ROOT`, `CONFIG`, `APP_DIR`, `TMP`, `WWW_ROOT`, `DB_DEFAULT`, `LANGUAGE_AVAILABLE`, `IS_CLI`, `AUTH_ACTIVE`, `ENVIRONEMENT`, `LOG_FILE`, `ROUTE_DEFAULT`, `ROUTE_LOGIN`, etc. — see the top of `Glial/Bootstrap.php` for the full set. To scaffold a host project, use [glial-new](https://github.com/Esysteme/glial-new).

Because there is no runnable app in this tree, **do not try to `php -S` or curl endpoints here** — there are no routes to exercise. Validate changes by reading code, running the targeted PHPUnit tests under `Glial/**/Test/`, or by installing the library in a consumer app.

## Common commands

```bash
composer install                                    # install deps into vendor/
vendor/bin/phpunit Glial/Synapse/Test/              # run Synapse tests
vendor/bin/phpunit Glial/Sgbd/Sql/Mysql/Test/       # run MySQL driver tests (needs a reachable MySQL)
vendor/bin/phpunit Glial/Cli/Test/SshTest.php       # run a single test file
```

There is no phpunit.xml/phpunit.xml.dist, no lint config, and no build step. Tests are scattered as `Test/` subdirectories within each package (e.g. `Glial/Synapse/Test/`, `Glial/Sgbd/Sql/Mysql/Test/`, `Glial/Cli/Test/`) — there is no single aggregated suite, so invoke PHPUnit per-path. Tests that hit databases or SSH require real services; there are no mocks.

`composer.json` declares a `pre-install-cmd` of `php ./glial install update`. That script is not in this repo (it lives in the host/`glial-new` project) — running `composer install` standalone here may warn but still works.

## Architecture — how a request flows

Glial is an **HMVC** framework that runs in both Apache and CLI from the same code path. The mental model:

1. **`Glial/Bootstrap.php`** is the entry point the host app includes. It wires up `Config` → `Sgbd` → `I18n` → `Router` → `Acl`/`Auth` → `FactoryController`, then calls `FactoryController::rootNode($controller, $action, $param)` and echoes the returned HTML (or, in CLI, dispatches from `argv`).

2. **`Glial/Router.php`** parses URLs of the shape `/<lg>/<controller>/<action>/<params...>`. The first segment is always the language (redirects if missing/invalid). `key:value` params become `$_GET[key] = value`. A URL containing `>` flips `IS_AJAX` on — in which case only a single MVC node renders (no layout).

3. **`Glial/Synapse/FactoryController`** (`rootNode` / `addNode`) is the HMVC engine. `rootNode` runs once per request and owns the layout; `addNode` lets a view embed another controller/action as a sub-node. `addNode`'s `$out` flag (`DISPLAY | EXPORT | CALCUL | RESULT`) chooses whether to stream output, return HTML, return the controller's return value, or just run side-effects.

4. **`Glial/Synapse/Controller`** resolves concrete controllers at `\App\Controller\<Camelized>` under `APP_DIR` (the host app's namespace), calls `before()` → action → `after()`, then `require`s the view at `APP_DIR/view/<Controller>/<view>.view.php`. Layouts live at `APP_DIR/layout/<layout_name>.layout.php`. `$this->set($k, $v)` / `$this->get()` pass data to the view; `$this->layout_name = false` disables the layout (always disabled in CLI).

5. **Dependency injection** is a static registry on `FactoryController::$di` — populated in `Bootstrap.php` (`config`, `log`, `acl`, `js`, `auth`) and reachable as `$this->di['…']` inside controllers. Use `addDi()` once per key; it throws `GLI-019` on duplicate.

### Database layer (`Glial/Sgbd`)

- `Sgbd` is a **static facade** over connection config. Never instantiate it — use `Sgbd::sql(DB_DEFAULT)` / `Sgbd::sql($name, $num)`. It lazy-connects on first use and caches per `(name, num)` so you can hold multiple distinct connections to the same server (useful when you need a second connection with a different current database).
- `FactorySql::connect()` dispatches to `Glial\Sgbd\Sql\<Driver>\<Driver>` (Mysql, Pgsql, Oracle, Sybase). All drivers extend the abstract `Sql` base in `Glial/Sgbd/Sql/Sql.php` — add a method to the abstract first if you're extending the contract.
- Passwords in `db.config.ini.php` may be stored `crypted=1`; they're decrypted via `Glial\Security\Crypt\Crypt` using `CRYPT_KEY` at connect time.
- MySQL has extra toolkit modules under `Glial/Sgbd/Sql/Mysql/` (`Backup`, `Compare`, `MasterSlave`, `Monitoring`, `Parser`, `Tools`) — these are used by the `Neuron` admin/monitoring controllers.

### Config convention

`Config::load(CONFIG)` scans the host's config directory for two patterns:

- `*.config.php` — `require`d as PHP (use these to `define()` constants).
- `*.config.ini.php` — parsed as INI (sections → associative arrays), keyed by the leading filename segment (`db.config.ini.php` → `$config->get('db')`).

So `db.config.ini.php`, `acl.config.ini`, `router.config.php` etc. live in the **host app's** `CONFIG` directory, not here.

### Model convention (by reflection on the DB schema)

Glial does not have model classes — it reads the DB at runtime to infer relations. This imposes naming rules on the host app's schema:

- Foreign keys: `id_<table>` (single reference) or `id_<table>__<role>` (multiple refs to same table, e.g. `id_user__customer`, `id_user__provider`).
- Join tables: `<tableA>__<tableB>`, names sorted alphabetically (e.g. `mail_message__user`).

`UserGuide.MD` documents an older `link__a__b` form — the README supersedes it. Prefer the README convention.

### CLI vs HTTP

The same controllers run under both. In CLI: `php <host>/glial <controller> <action> [params...]` — `IS_CLI` is set, no session, no layout, language forced to `en`. CLI-only helpers live under `Glial/Cli/` (`Daemon`, `Multithread`, `ProcessManager`, `ProgressBar`, `Shmop`, `Ssh`, `Table`, `Color`). If you add features that assume `$_SERVER`/session state, guard them with `if (!IS_CLI)`.

## Error code convention

Exceptions thrown by the framework use codes of the form `GLI-NNN` in the message. Known codes are tracked in `Exception.MD` — when you add a new thrown exception, pick the next free code and add a line there.

## Coding conventions you'll actually see

- **Class/directory names are StudlyCaps**, per PSR-0~4 — despite the README's historical note about lowercase/singular. Follow StudlyCaps for new code.
- `Inflector::camelize()` is used to turn URL segments like `error_web` into controller class names like `ErrorWeb`.
- French / English mix in comments and variable names is normal; don't rename incidentally.
- The existing code uses `var` declarations, `Throw new` (capitalized), and other non-idiomatic PHP — match local style in the file you're editing rather than modernizing drive-by.
