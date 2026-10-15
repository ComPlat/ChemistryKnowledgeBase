# WikiFarm

The **WikiFarm** extension turns a single MediaWiki installation into a
multi-tenant wiki farm. It allows you to host many independent wikis
(each with its own database, SOLR core, logo, favicon, user access
list, …) from one shared MediaWiki code base.

A central "main" wiki is used for administration (creating / removing
tenant wikis, managing users, etc.). Each tenant wiki is addressed
through its own URL path (e.g. `/wiki42/mediawiki/...`) and is backed
by its own `env-farm-<wikiId>` folder containing a tenant-specific
`env.php` with the per-wiki configuration.

## Features

- Switch the active wiki per HTTP request based on the URL path
  (see `WikiSwitch.php`).
- Create new tenant wikis from a JSON configuration (database, SOLR
  core, env folder, logo/favicon/wordmark, LDAP groups, symlink into
  the public html folder) — see `WikiGenerator`.
- Create / remove wikis asynchronously using MediaWiki jobs
  (`CreateWikiJob`, `RemoveWikiJob`).
- Manage per-wiki user access via a shared database table
  (`wiki_farm`, `wiki_farm_user`); unauthenticated users are
  redirected to login and unauthorized users are refused access to a
  tenant wiki (see `Setup::checkPrivileges()`).
- `Special:CreateWiki` UI for creating new wikis from the browser.
- Maintenance scripts for scripted administration
  (`maintenance/addUser.php`, `maintenance/addCreateWikiJob.php`,
  …).

## Installation

1. Open console and type
    ```
    composer require diqa/wiki-farm:dev-main --no-update
    composer install --no-dev
    ```
2. In `LocalSettings.php`, before `wfLoadExtension('WikiFarm')`,
   configure the variables documented below and `require_once` the
   wiki switch:

   ```php
   require_once "extensions/WikiFarm/WikiSwitch.php";
   wfLoadExtension('WikiFarm');
   ```

3. Make sure the following external tools are available (the paths
   must be configured as plain PHP globals — they are **not**
   `wgWikiFarm*` settings):

   | Variable            | Purpose                                      |
      |---------------------|----------------------------------------------|
   | `$mysqlBin`         | Path to the `mysql` client binary.           |
   | `$phpBin`           | Path to the `php` CLI binary.                |
   | `$solrBin`          | Path to the Solr `solr` script.              |
   | `$solrCoreTemplate` | Template folder used for new SOLR cores.     |
   | `$publicHtml`       | Public web root (used for the symlink).      |

4. Run the main-wiki installer/updater (`maintenance/update.php`) so
   the shared tables `wiki_farm` and `wiki_farm_user` are created
   (see `WikiRepository::setupTables()`).

## URL layout

Requests are expected to follow the pattern

    /<wikiId>/mediawiki/


The leading `<wikiId>` segment is parsed from `REQUEST_URI` and used
to pick the correct `env-farm-<wikiId>/env.php` to include. The
special wiki id `main` refers to the administration wiki.

Access control is enforced in `Setup::onBeforePageDisplay()`:
anonymous users are redirected to `Special:Userlogin`; logged-in
users without an entry in `wiki_farm_user` for the requested wiki
receive an "Access denied" page.

## Maintenance scripts

All maintenance scripts live in `extensions/WikiFarm/maintenance/`
and must be executed on the main wiki (the extension refuses to run
them unless `Setup::isEnabled()` returns `true`).

- `addUser.php --user=<name> --wiki=<wikiId> [--status=USER]`
  Grants a user access to a tenant wiki.
- `addCreateWikiJob.php --name=<wikiName> --user=<userName>`
  Enqueues a `CreateWikiJob` which calls `WikiGenerator` in the
  background to actually create the DB, SOLR core, env folder and
  symlink.

## Programmatic wiki creation

`DIQA\WikiFarm\WikiGenerator\WikiGenerator` performs the full
creation workflow in `createWiki()`:

1. Validate prerequisites (binaries, free wiki id, DB does not
   exist, SOLR core does not exist, image files exist and are
   readable, …).
2. Create `env-farm-<wikiId>` and copy the template files
   (`favicon`, `logo`, `wordmark`, `env.php`).
3. Create the tenant database (name derived from
   `$wgWikiFarmDBPattern`, see below) and import
   `maintenance/tables-generated.sql`.
4. Create and populate the SOLR core from `$solrCoreTemplate`.
5. Run `setupStore.php`, `update.php`, `WikiImport.php` and
   `importImages.php` against the new wiki.
6. Populate the SOLR index (`EnhancedRetrieval` /
   `FacetedSearch2`).
7. Create a symlink under `$publicHtml/<wikiId>` pointing to
   `$wgWikiFarmEntryPoint`.

The JSON configuration file consumed by
`createNewWikiFromFile()` looks like:

```
{
  "serverName": "[http://example.org](http://example.org)",
  "wikiId": "mywiki",
  "wikiName": "My Wiki",
  "useLdap": true,
  "readForAll": false,
  "favicon": "/path/to/favicon.ico",
  "logo": "/path/to/logo.png",
  "wordmark": "/path/to/wordmark.png",
  "ldap": {
    "groupsForLogin": [
      "cn=users,..."
    ],
    "groupsForWriting": [
      "cn=editors,..."
    ],
    "groupsForAdmin": [
      "cn=admins,..."
    ]
  }
}
```


The `wikiId` must match `^[a-z][a-z_0-9]*$`.

## Configuration variables

All settings below are expected to be defined in `LocalSettings.php`
(or in the per-tenant `env.php`) before `wfLoadExtension('WikiFarm')`
is called.

### `$wgWikiFarmWikiId`

- **Type:** `string|null`
- **Default:** `null`

Identifier of the wiki that is currently being served. It is normally
set from `env.php` of the active tenant and is used by
`Setup::setWikiName()` to look up the wiki's human-readable name
from the shared `wiki_farm` table and assign it to `$wgSitename`.
If `null`, no tenant lookup is performed.

This variable is normally set by `WikiSwitch.php` and is not
expected to be set by the user.

### `$wgWikiFarmDefaultWikiId`

- **Type:** `string`
- **Example:** `'main'`

Fallback wiki id used by `WikiSwitch.php` when the request URL does
not include a wiki segment. This is typically the id of the central
administration wiki.

### `$wgWikiFarmDBPattern`

- **Type:** `string`
- **Example:** `'chem{wiki}'`

Pattern used to derive the MySQL database name of a tenant wiki from
its wiki id. The placeholder `{wiki}` is replaced with the actual
wiki id (see `WikiGenerator::getDBName()`). If this setting is not
defined, the fallback name `<wikiId>_wikidb` is used.

### `$wgWikiFarmScriptPathPattern`

- **Type:** `string`
- **Example:** `'/{wiki}/mediawiki'`

Pattern used to build `$wgScriptPath` for the active tenant. The
placeholder `{wiki}` is replaced with the current wiki id. This
ensures each tenant generates self-links under its own URL prefix.

### `$wgWikiFarmScriptWikiIdPattern`

- **Type:** `string`
- **Example:** `'wiki{wiki}'`

Pattern used to prefix the raw wiki id when composing other wiki-id
derived values (for example, when the numeric id from the
`wiki_farm` table is turned into the on-disk id). The placeholder
`{wiki}` is substituted with the raw id. In the default setup the
on-disk id of wiki number `42` would therefore become `wiki42`.

### `$wgWikiFarmEntryPoint`

- **Type:** `string`
- **Example:** `'/var/www/html/gateway'`

Absolute filesystem path to the shared MediaWiki entry point that
every tenant symlink points to. When a new wiki is created,
`WikiGenerator::createSymlink()` runs

bash sudo ln -s wgWikiFarmEntryPointpublicHtml/


so that a request to `/<wikiId>/mediawiki/...` is handled by the
shared MediaWiki installation.

### `$wgWikiFarmAllowMissingEnv`

- **Type:** `bool`
- **Default:** `false`
- **Example:** `true`

If `true`, `WikiSwitch.php` will **not** abort the request when the
`env-farm-<wikiId>/env.php` file for the requested wiki id is
missing (for example because the wiki has not been created yet or
was removed). Instead, it falls back to the default wiki
(`$wgWikiFarmDefaultWikiId`). Useful on development machines and
during provisioning; should be left `false` in production so that
requests for unknown wikis fail fast.

### `$wgWikiFarmForeignFileRepo`

- **Type:** `string`
- **Example:** `'main'`

Id of the wiki whose `images/` directory is exposed to all other
tenants as a `ForeignFileRepo`. This lets tenant wikis reference
files that were uploaded on the central (main) wiki without having
to duplicate them.

## Database schema

Two tables are created in the shared database
(`$wgSharedDB`), see `WikiRepository::setupTables()`:

- `wiki_farm(id, wiki_name, fk_created_by, wiki_status, created_at)`
  — one row per tenant wiki. `wiki_status` is one of
  `IN_CREATION`, `CREATED`, `TO_BE_DELETED`, `FAILED`.
- `wiki_farm_user(id, fk_user_id, fk_wiki_id, status_enum, created_at)`
  — maps users to the wikis they may access. `status_enum` currently
  only supports the value `USER`.

## Special pages

- **`Special:CreateWiki`** — form-based wiki creation (used from the
  main wiki). Loads the `ext.diqa.wikifarm` ResourceLoader module
  (JavaScript + CSS under `scripts/` and `skins/`) which performs an
  AJAX call that eventually enqueues a `CreateWikiJob`.