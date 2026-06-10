# XenForo 2.3 Add-on Development — Complete Reference

> A single-file, end-to-end guide to building a working XenForo add-on without errors.
> Target: **XenForo 2.3.x** (latest stable line, 2.3.10 as of 2026). The MVC, entity,
> finder, schema, template, listener and class-extension systems described here are stable
> across XF 2.0–2.3; version-specific notes are flagged inline.
>
> Source of truth: the official XenForo Developer Documentation (`xenforo.com/docs/dev/`,
> repo `github.com/xenforo-ltd/docs`) plus the XF source conventions. Code in this guide is
> copied from / verified against those docs.

---

## Table of contents

1. [System requirements & local environment](#1-system-requirements--local-environment)
2. [Installing XenForo (CLI)](#2-installing-xenforo-cli)
3. [config.php: debug & development mode](#3-configphp-debug--development-mode)
4. [Add-on fundamentals (IDs, versioning, structure)](#4-add-on-fundamentals)
5. [addon.json reference](#5-addonjson-reference)
6. [CLI command reference](#6-cli-command-reference)
7. [Core concepts (autoloader, namespaces, short names, the \XF facade)](#7-core-concepts)
8. [The class extension system (XFCP)](#8-the-class-extension-system-xfcp)
9. [Code event listeners](#9-code-event-listeners)
10. [The Setup class (install / upgrade / uninstall)](#10-the-setup-class)
11. [Managing the schema (DB adapter + SchemaManager)](#11-managing-the-schema)
12. [Entities](#12-entities)
13. [Finders](#13-finders)
14. [Repositories](#14-repositories)
15. [Routing](#15-routing)
16. [Controllers & reply types](#16-controllers--reply-types)
17. [Templates](#17-templates)
18. [Phrases](#18-phrases)
19. [Template modifications](#19-template-modifications)
20. [Widgets & widget positions](#20-widgets--widget-positions)
21. [Permissions](#21-permissions)
22. [Options](#22-options)
23. [Services (setup-and-go)](#23-services)
24. [Jobs / deferred tasks](#24-jobs--deferred-tasks)
25. [Cron entries](#25-cron-entries)
26. [Admin navigation](#26-admin-navigation)
27. [Handlers & content types](#27-handlers--content-types)
28. [The criteria system](#28-the-criteria-system)
29. [Styles, LESS & designer mode](#29-styles-less--designer-mode)
30. [REST API & webhooks](#30-rest-api--webhooks)
31. [Building & releasing](#31-building--releasing)
32. [Error-prevention checklist & common gotchas](#32-error-prevention-checklist--common-gotchas)
33. [Quick reference cheat-sheet](#33-quick-reference-cheat-sheet)

---

## 1. System requirements & local environment

Recommended server stack for XF 2.3:

- **PHP**: 7.2 minimum, **8.4 recommended**. (XF 2.3 supports PHP 8.x; keep your add-on's required PHP as low as your code allows.)
- **MySQL**: 5.7+ (MariaDB and Percona are compatible).
- **PHP extensions**: MySQLi, GD (with JPEG support), PCRE, cURL, SPL, SimpleXML, DOM, JSON, iconv, ctype.

Local setup options:
1. Install Apache/nginx + MySQL/MariaDB + PHP yourself (most control).
2. A pre-built stack (Bitnami LAMP/MAMP/WAMP, etc.).
3. A pre-built VM / container.

Download XF from your Customer Area (`xenforo.com/customers`). Extract the ZIP; inside is an
`upload/` directory. Upload **the contents of `upload/`** to your web root (`public_html`,
`htdocs`, or `www`).

**File permissions**: XF must be able to write to `data/` and `internal_data/` (and their
sub-dirs) at runtime. When you also use the CLI, both the web-server user and the CLI user
need write access. Easiest dev options:
- Run the CLI as the same user PHP runs as, **or**
- Apply ACLs to `data/` and `internal_data/`, **or**
- Force a writable chmod via config:
  ```php
  $config['chmodWritableValue'] = 0666;
  ```
When developing add-ons, the `_output` directory inside your add-on must also be writable by
both users (commonly `chmod 0777` on `src/addons/<id>/_output`).

---

## 2. Installing XenForo (CLI)

If installing via CLI you must create `src/config.php` manually first. **It must live in
`src/` — the `library/` directory is legacy only.**

```php title="src/config.php"
<?php

$config['db']['host'] = 'localhost';
$config['db']['port'] = '3306';
$config['db']['username'] = 'root';
$config['db']['password'] = 'mypassword';
$config['db']['dbname'] = 'xf2';
```

Run the installer from the XF root (where `cmd.php` lives):

```sh
php cmd.php xf:install
```

You'll be prompted for the initial admin username/password and board title, then XF imports
its tables and master data.

**Reinstall** (destructive — clears all `xf_`-prefixed tables): delete the contents of
`data/` and `internal_data/`, then:

```sh
php cmd.php xf:install --clear
```

**Verify file integrity** (whole install, just `XF`, or a specific add-on):

```sh
php cmd.php xf:file-check [addon_id]
```

**Add-on lifecycle from the CLI:**

```sh
php cmd.php xf:addon-install   [addon_id]   # install
php cmd.php xf:addon-upgrade   [addon_id]   # upgrade
php cmd.php xf:addon-rebuild   [addon_id]   # re-import master data
php cmd.php xf:addon-uninstall [addon_id]   # uninstall
```

If development output is available, these commands ask whether to import from it instead of
the exported `_data` XML.

---

## 3. config.php: debug & development mode

### Debug mode

```php title="src/config.php"
$config['debug'] = true;
```

Enables: ACP development tools (Routes, Permissions, admin navigation, code event listeners,
class extensions, content types, etc.), plus an on-page footer with execution time, query
count, memory use, and a clickable query/stack-trace inspector.

### Development mode

```php title="src/config.php"
$config['development']['enabled'] = true;
$config['development']['defaultAddOn'] = 'SomeCompany/MyAddOn';
```

- Enabling development mode **auto-enables debug mode**.
- It writes your add-on's data to its `_output` directory and enables filesystem template
  editing (changes written to disk are imported & recompiled on next load).
- `defaultAddOn` (optional) pre-selects your add-on in ACP "create content" screens.

Useful companion settings for dev installs:

```php title="src/config.php"
$config['enableMail'] = false;        // stop all outgoing mail (use copies of live data safely)
$config['cookie']['prefix'] = 'dev1_'; // avoid cookie clashes between multiple installs on one domain
```

Stop frequent logouts on dynamic IP / VPN (dev only — never on production):

```php title="src/config.php"
$c->extend('session', function(\XF\Session\Session $session)
{
    $session->setConfig(['ipv4CidrMatch' => 0, 'ipv6CidrMatch' => 0]);
    return $session;
});
```

### Variable dumping (debugging helpers)

```php
\XF::dump($var);        // Symfony VarDumper — rich, collapsible HTML output
\XF::dumpSimple($var);  // plain-text var_dump wrapped in <pre>
```

---

## 4. Add-on fundamentals

### Add-on IDs

Every add-on has a unique ID that **determines its filesystem location** and **becomes your
class namespace prefix**.

- **Simple ID** (e.g. `Demo`): only `a–z`/`A–Z`, may contain `0–9` (not at the start), no
  slashes/dashes/underscores. Stored in `src/addons/Demo`.
- **Vendor ID** (e.g. `SomeVendor/Demo`): exactly one `/` (not at start/end), same character
  rules per part. Stored in `src/addons/SomeVendor/Demo`.

The ID maps directly to namespaces: `Demo/Portal` → classes namespaced `Demo\Portal\…` in
`src/addons/Demo/Portal/…`.

### Versioning

Use semantic `MAJOR.MINOR.PATCH` for `version_string`. The `version_id` is an **integer used
for internal comparisons** and **must increase every release**. XF's recommended format is
`aabbccde`:

| Part | Meaning |
|------|---------|
| `aa` | major version |
| `bb` | minor version |
| `cc` | patch version |
| `d`  | state: `1`=alpha, `3`=beta, `5`=release candidate, `7`=stable |
| `e`  | state version |

Examples: `1.7.3 RC4` → `1070354`; XF `2.0.0` stable → `2000070`; `1.5.0 Beta 3` → `1050033`.

### Special files & directories

```
src/addons/Vendor/AddOn/
├── addon.json          # REQUIRED manifest (identify/display the add-on)
├── hashes.json         # auto-generated file-integrity hashes (build step)
├── Setup.php           # optional install/upgrade/uninstall logic
├── build.json          # optional build customization
├── _data/              # exported master data (one XML per data type) — SHIPPED
├── _output/            # dev-mode output (JSON/TXT/HTML) — DEV ONLY, do NOT ship
├── _no_upload/         # bundled in ZIP beside upload/ but NOT uploaded (README, LICENSE…)
├── _stubs/             # custom stubs for xf-make:* generators
├── _files/             # source for build.json additional_files (checked first when copying)
├── _build/             # temp build dir (created during build-release)
└── _releases/          # finished release ZIPs: <ADDON ID>-<VERSION STRING>.zip
```

- **`_data`**: master data XML, one file per type; hashes recorded in `hashes.json` so install
  is verified as complete/consistent.
- **`_output`**: each data item stored separately — mostly JSON, **phrases as `.txt`**,
  **templates as `.html`/`.css`/`.less`**. Templates are editable directly on disk and
  re-imported on load. Only used when development mode is enabled. **Never ship it.**
- **`_no_upload`**: files placed in the ZIP alongside `upload/` (so they are NOT uploaded to
  the server) — ideal for README/CHANGELOG/LICENSE.
- **`_stubs`**: override the boilerplate the `xf-make:*` generators emit. Publish core stubs
  to customize: `php cmd.php xf-make:stub-publish entity --addon=Vendor/AddOn`.

### hashes.json

Generated automatically during the build. It records a content hash for each shipped file so
the ACP/CLI file-health check can detect corruption or tampering. You never edit it by hand.

---

## 5. addon.json reference

Minimum valid manifest:

```json title="addon.json"
{
    "title": "My Add-on by Some Company",
    "version_string": "2.0.0",
    "version_id": 2000070,
    "dev": "Some Company"
}
```

A fuller, realistic example (as produced/edited via the tutorial):

```json title="src/addons/Demo/Portal/addon.json"
{
    "legacy_addon_id": "",
    "title": "Demo - Portal",
    "description": "Add-on which will display featured threads on the forum home page.",
    "version_id": 1000010,
    "version_string": "1.0.0 Alpha",
    "dev": "You!",
    "dev_url": "",
    "faq_url": "",
    "support_url": "",
    "extra_urls": [],
    "require": [],
    "icon": "fa-home"
}
```

### All properties

| Property | Description |
|----------|-------------|
| `legacy_addon_id` | Enables automatic handling of an ID change when upgrading from an XF1 add-on. |
| `title` | Add-on title (shown in ACP). |
| `description` | Description (shown in ACP). |
| `version_id` | Internal integer; **must increment every release**. |
| `version_string` | Human-readable version (shown in ACP). |
| `composer_autoload` | Relative path (from add-on root) to a Composer vendor dir containing `autoload_psr4.php` etc.; XF registers it with its autoloader when active. |
| `dev` | Developer name (shown in ACP). |
| `dev_url` | If set, developer name links here. |
| `faq_url` | If set, an FAQ link appears. |
| `support_url` | If set, a support link appears. |
| `extra_urls` | Object of `{ "Link text": "https://target" }` for extra links (bug tracker, manual…). |
| `require` | Install/upgrade requirements (see below). |
| `icon` | Font Awesome name (e.g. `fa-shopping-bag`) **or** a path to an image file. |

### The `require` property

Blocks install/upgrade unless the environment matches. Each entry: `name: [version, "human text"]`.

```json title="addon.json"
"require": {
    "XF":           [2030070, "XenForo 2.3.0+"],
    "php":          ["8.1.0", "PHP 8.1.0+"],
    "php-ext/json": ["*", "JSON extension"]
}
```

| Requirement name | Refers to | Value |
|------------------|-----------|-------|
| `XF` | XenForo version | XF version ID, e.g. `2030070`. Check `\XF::$versionId` or the top of `src/XF.php`. |
| `php` | PHP version | e.g. `8.1.0`. Keep as low as your code allows. |
| `php-ext/<ext>` | A PHP extension | Extension version (compared via `version_compare`); use `*` for any. |
| `<any addon ID>` | Another add-on | That add-on's version ID. |

Validate at any time: `php cmd.php xf-addon:validate-json [addon_id]`.

---

## 6. CLI command reference

> All `xf-dev:*` and `xf-addon:*` step/import/export commands require **development mode**.
> Use a VCS (git) for your add-on — `_output`/DB desync can cause data loss.

### Add-on management

```sh
php cmd.php xf-addon:create                       # interactive: ID, title, version id/string, Setup?
php cmd.php xf-addon:export        [addon_id]      # export DB data → _data XML files
php cmd.php xf-addon:bump-version  [addon_id] --version-id 1020370 --version-string 1.2.3
php cmd.php xf-addon:sync-json     [addon_id]      # import manual addon.json edits without a destructive rebuild
php cmd.php xf-addon:validate-json [addon_id]      # validate addon.json structure/fields
php cmd.php xf-addon:build-release [addon_id]      # export + collect files + hashes.json + ZIP → _releases
```

`xf-addon:create` prompts for: ID, title, version ID (e.g. `1000010`), version string (e.g.
`1.0.0 Alpha`), whether to write `Setup.php`, and whether the Setup should support multiple
steps.

`xf-addon:bump-version`: if you pass only `--version-id` and it matches the recommended
`aabbccde` format, the version string is inferred. Updates `addon.json` **and** the DB.

### Run individual Setup steps (StepRunner-based Setups only)

```sh
php cmd.php xf-addon:install-step   [addon_id] [step]
php cmd.php xf-addon:upgrade-step   [addon_id] [version] [step]
php cmd.php xf-addon:uninstall-step [addon_id] [step]
```

### Development import/export

```sh
php cmd.php xf-dev:export --addon [addon_id]   # DB → _output files
php cmd.php xf-dev:import --addon [addon_id]   # _output files → DB
```

### Code generators (`xf-make:*`)

Scaffold boilerplate classes (entity, controller, service, repository, finder, job, cron,
etc.). They consult your add-on's `_stubs/` first, then core stubs. Example:

```sh
php cmd.php xf-make:entity Demo/Portal Demo\\Portal:FeaturedThread xf_demo_portal_featured_thread
php cmd.php xf-make:stub-publish entity --addon=Demo/Portal   # publish a stub to customize
```

> Run `php cmd.php list` to see every available command, and
> `php cmd.php help <command>` for a specific command's arguments/options.

### Designer mode (styles) — see §29

```sh
php cmd.php xf-designer:enable  [style_id] [designer_mode_id]
php cmd.php xf-designer:disable [designer_mode_id] [--clear]
php cmd.php xf-designer:export  [designer_mode_id]
php cmd.php xf-designer:import  [designer_mode_id]
php cmd.php xf-designer:sync-templates  [designer_mode_id]
php cmd.php xf-designer:touch-template  [designer_mode_id] [type:title] [--custom]
php cmd.php xf-designer:revert-template [designer_mode_id] [type:title]
```

---

## 7. Core concepts

### Autoloader & "class per file"

XF uses a Composer-generated autoloader. The autoload root for add-ons is `src/addons`. XF
enforces **one class per file**, and the class name maps exactly to the path:

- `src/addons/Demo/Setup.php` → class `Demo\Setup`
- `Demo\Entity\Thing` → `src/addons/Demo/Entity/Thing.php`

### Namespaces

```php title="src/addons/Demo/Entity/Thing.php"
<?php

namespace Demo\Entity;

class Thing
{
}
```

Classes in the same namespace reference each other by short name (no leading `\`).

### Short class names

XF resolves `Prefix:Suffix` short names to full class names, inserting a context-dependent
**infix**:

- `XF:User` as an **entity** → `XF\Entity\User`
- `XF:User` as a **repository** → `XF\Repository\User`
- `Demo:Thing` entity → `Demo\Entity\Thing`; repository → `Demo\Repository\Thing`

```php
\XF::em()->create('Demo:Thing');   // → Demo\Entity\Thing
\XF::repository('Demo:Thing');     // → Demo\Repository\Thing
\XF::finder('Demo:Thing');         // → uses Demo\Finder\Thing if present, else generic Finder on the entity
```

For controllers/views, the infix comes from the app type: a **public** route → `Pub`
(`Demo\Pub\Controller\X`), an **admin** route → `Admin` (`Demo\Admin\Controller\X`).

### The `\XF` facade — the methods you'll use constantly

```php
\XF::app()           // the Application container
\XF::db()            // database adapter (\XF\Db\AbstractAdapter)
\XF::em()            // entity manager (\XF\Mvc\Entity\Manager)
\XF::finder('XF:User')         // a Finder for an entity
\XF::repository('XF:User')     // a Repository
\XF::visitor()       // current user entity (\XF\Entity\User); user_id 0 = guest
\XF::options()       // board options object: \XF::options()->boardTitle
\XF::option('boardTitle')      // single option
\XF::phrase('phrase_name', ['param' => $x])   // a \XF\Phrase
\XF::language()      // current language
\XF::config('debug') // a config.php value
\XF::$time           // request timestamp (int)
\XF::$versionId      // running XF version id
\XF::repository(...)->...       // repo methods
\XF::asVisitor($user, function() { ... });   // run a closure as another user (permission context)
\XF::extendClass('XF\\Foo')     // resolve the final (extended) class name — use before `new`
\XF::dump($x); \XF::dumpSimple($x);
```

### Type hinting (IDE help with factory methods)

Factory calls return base types as far as the IDE knows, so annotate:

```php
/** @var \Demo\Repository\Thing $repo */
$repo = \XF::repository('Demo:Thing');
```

When you extend a class with XFCP, XF writes `_output/extension_hint.php` so the IDE
understands `$this` inside your extended class. (PHP ignores it; only the IDE reads it.)

---

## 8. The class extension system (XFCP)

XF lets you override core (and other add-ons') classes **without editing them**, and lets
multiple add-ons stack extensions of the same class via the "XenForo Class Proxy" (XFCP).

**Process:**
1. Create a class that extends `XFCP_<ClassName>` (a dynamically-built proxy — **not** the
   real parent directly). This builds the inheritance chain.
2. Register a **Class extension** in ACP → Development → Class extensions (base class name +
   your extension class name).

**Convention for file placement** — mirror the target's path under a directory named after
the target add-on's ID. Extending `XF\Pub\Controller\Member` from add-on `Demo`:

```php title="src/addons/Demo/XF/Pub/Controller/Member.php"
<?php

namespace Demo\XF\Pub\Controller;

class Member extends XFCP_Member
{
    // Add a brand-new action:
    public function actionHelloWorld()
    {
        return $this->message('Hello world!');
    }
}
```

Register the extension with base `XF\Pub\Controller\Member` and extension
`Demo\XF\Pub\Controller\Member`. Visit `index.php?members/hello-world` to see it.

**Overriding an existing method** — always call `parent::` and respect the original return
type. For controller actions, modify the returned reply rather than replacing the action:

```php
public function actionExample()
{
    $reply = parent::actionExample();

    if ($reply instanceof \XF\Mvc\Reply\View)
    {
        $reply->setParam('hello', 'Bonjour');
    }

    return $reply;
}
```

> **Always** type-check the reply before mutating it (it might be a Redirect/Error instead of
> a View). For non-controller methods, inspect the parent to learn the expected
> arguments/return (some are `void` — return nothing, just call `parent::`).

You can extend a **finder class even if it doesn't exist yet** — same mechanism.

Programmatically, when instantiating a class that may be extended, resolve the final class
first:

```php
$class = \XF::extendClass('XF\\Foo\\Bar');
$obj = new $class($arg);
```

---

## 9. Code event listeners

For events that aren't class methods (entity structure changes, lifecycle hooks, app
init…), register a **Code event listener** in ACP → Development → Code event listeners.

Each listener specifies: the **event ID**, an optional **event hint** (to scope it — e.g. the
specific entity class), a **callback class**, a **callback method**, an execute order, and the
owning add-on. By convention, all of an add-on's listeners live in one `Listener` class at the
add-on root.

The dev-output JSON for a listener looks like:

```json title="src/addons/Demo/Portal/_output/code_event_listeners/entity_structure_[hash].json"
{
    "event_id": "entity_structure",
    "execute_order": 10,
    "callback_class": "Demo\\Portal\\Listener",
    "callback_method": "forumEntityStructure",
    "active": true,
    "hint": "XF\\Entity\\Forum",
    "description": "Extends the XF\\Entity\\Forum structure"
}
```

**Adding columns/relations to a core entity** via `entity_structure` (hint = the entity
class, so the listener only fires for that entity):

```php title="src/addons/Demo/Portal/Listener.php"
<?php

namespace Demo\Portal;

use XF\Mvc\Entity\Entity;

class Listener
{
    public static function forumEntityStructure(\XF\Mvc\Entity\Manager $em, \XF\Mvc\Entity\Structure &$structure)
    {
        $structure->columns['demo_portal_auto_feature'] = ['type' => Entity::BOOL, 'default' => false];
    }

    public static function threadEntityStructure(\XF\Mvc\Entity\Manager $em, \XF\Mvc\Entity\Structure &$structure)
    {
        $structure->columns['demo_portal_featured'] = ['type' => Entity::BOOL, 'default' => false];

        $structure->relations['FeaturedThread'] = [
            'entity'     => 'Demo\Portal:FeaturedThread',
            'type'       => Entity::TO_ONE,
            'conditions' => 'thread_id',
            'primary'    => true,
        ];
    }
}
```

> The `&$structure` is passed **by reference** — modify it in place. The exact callback
> signature for any event is shown in the ACP under the event selector.

**Lifecycle via listeners** (`entity_post_save`, `entity_post_delete`, hint = entity class):

```php
public static function threadEntityPostSave(\XF\Mvc\Entity\Entity $entity)
{
    if ($entity->isUpdate())
    {
        // isStateChanged returns 'enter' | 'leave' | false
        if ($entity->isStateChanged('discussion_state', 'visible') === 'leave')
        {
            $featuredThread = $entity->FeaturedThread;
            if ($featuredThread)
            {
                $featuredThread->delete();
                $entity->fastUpdate('demo_portal_featured', false);
            }
        }
    }
}

public static function threadEntityPostDelete(\XF\Mvc\Entity\Entity $entity)
{
    $featuredThread = $entity->FeaturedThread;
    if ($featuredThread)
    {
        $featuredThread->delete();
    }
}
```

### Commonly used events

| Event ID | Typical use (hint where applicable) |
|----------|-------------------------------------|
| `app_setup` | Run code early in app boot. |
| `app_pub_complete` / `app_admin_complete` | After a request completes. |
| `entity_structure` | Add/alter columns, getters, relations, behaviors. Hint = entity class. |
| `entity_pre_save` / `entity_post_save` | React to inserts/updates. |
| `entity_pre_delete` / `entity_post_delete` | React to deletions. |
| `repository_*` | Hook repository behaviour. |
| `home_page_url` | Change the "Home" tab target (see below). |
| `criteria_user` / `criteria_page` | Handle custom criteria rules (see §28). |
| `criteria_template_data` | Add extra data to criteria templates. |
| `templater_setup` / `templater_template_pre_render` / `templater_template_post_render` | Hook the templater (register template functions, post-process output). |
| `template_macro_pre_render` / `template_macro_post_render` | Hook macro rendering. |
| `dispatcher_match` | Influence routing/dispatch. |
| `visitor_setup` | Adjust the visitor / permissions context. |
| `job_list` | Register recurring/internal jobs. |
| `cron` | (Cron entries are usually defined as data, see §25.) |

Example — repoint the Home tab to a custom route:

```php
public static function homePageUrl(&$homePageUrl, \XF\Mvc\Router $router)
{
    $homePageUrl = $router->buildLink('canonical:portal');
}
```

---

## 10. The Setup class

`Setup.php` (add-on root) holds install/upgrade/uninstall logic. Extend
`\XF\AddOn\AbstractSetup` and implement `install()`, `upgrade()`, `uninstall()`.

### Simple (single-method) form

```php title="src/addons/Demo/Setup.php"
<?php

namespace Demo;

class Setup extends \XF\AddOn\AbstractSetup
{
    public function install(array $stepParams = [])
    {
        $this->schemaManager()->createTable('xf_demo', function(\XF\Db\Schema\Create $table)
        {
            $table->addColumn('demo_id', 'int');
        });
    }

    public function upgrade(array $stepParams = [])
    {
        if ($this->addOn->version_id < 1000170)
        {
            $this->schemaManager()->alterTable('xf_demo', function(\XF\Db\Schema\Alter $table)
            {
                $table->addColumn('foo', 'varchar', 10)->setDefault('');
            });
        }
    }

    public function uninstall(array $stepParams = [])
    {
        $this->schemaManager()->dropTable('xf_demo');
    }
}
```

### Multi-step form (recommended) — StepRunner traits

The traits implement `install()/upgrade()/uninstall()` for you; you just add numbered step
methods. Upgrade steps embed the target **version ID**: `upgrade<versionId>Step<n>()`.

```php title="src/addons/Demo/Portal/Setup.php"
<?php

namespace Demo\Portal;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;

use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    // ---- INSTALL ----
    public function installStep1()
    {
        $this->schemaManager()->alterTable('xf_forum', function(Alter $table)
        {
            $table->addColumn('demo_portal_auto_feature', 'tinyint')->setDefault(0);
        });
    }

    public function installStep2()
    {
        $this->schemaManager()->alterTable('xf_thread', function(Alter $table)
        {
            $table->addColumn('demo_portal_featured', 'tinyint')->setDefault(0);
        });
    }

    public function installStep3()
    {
        $this->schemaManager()->createTable('xf_demo_portal_featured_thread', function(Create $table)
        {
            $table->addColumn('thread_id', 'int');
            $table->addColumn('featured_date', 'int');
            $table->addPrimaryKey('thread_id');
        });
    }

    public function installStep4()
    {
        // createWidget(widgetKey, definitionId, config)
        $this->createWidget('demo_portal_view_members_online', 'members_online', [
            'positions' => ['demo_portal_view_sidebar' => 10],
        ]);
    }

    // ---- UPGRADE (example: changes shipped in version 1000170) ----
    public function upgrade1000170Step1()
    {
        $this->schemaManager()->alterTable('xf_demo_portal_featured_thread', function(Alter $table)
        {
            $table->addColumn('feature_user_id', 'int')->setDefault(0);
        });
    }

    // ---- UNINSTALL ----
    public function uninstallStep1()
    {
        $this->schemaManager()->alterTable('xf_forum', function(Alter $table)
        {
            $table->dropColumns('demo_portal_auto_feature');
        });
    }

    public function uninstallStep2()
    {
        $this->schemaManager()->alterTable('xf_thread', function(Alter $table)
        {
            $table->dropColumns('demo_portal_featured');
        });
    }

    public function uninstallStep3()
    {
        $this->schemaManager()->dropTable('xf_demo_portal_featured_thread');
    }
}
```

Run individual steps during development:

```sh
php cmd.php xf-addon:install-step   Demo/Portal 1
php cmd.php xf-addon:upgrade-step   Demo/Portal 1000170 1
php cmd.php xf-addon:uninstall-step Demo/Portal 1
```

### Useful AbstractSetup helpers

- `$this->schemaManager()` → schema operations (see §11).
- `$this->db()` → DB adapter.
- `$this->app` / `$this->addOn` (with `$this->addOn->version_id`).
- `$this->query($sql, $params)` → raw query.
- `$this->createWidget($key, $definitionId, $config)` → ship default widget instances.
- `$this->applyGlobalPermission($group, $perm, ...)`, `$this->applyGlobalPermissionInt(...)`,
  `$this->applyContentPermission(...)` → set default permission values during upgrades.
- `$this->renameOption(...)`, etc.

> **Data created in the ACP and associated with your add-on (options, permissions, widget
> positions, phrases, routes, etc.) is removed automatically on uninstall.** You only need
> uninstall steps for **schema** changes (dropping columns/tables you added) and any external
> artifacts. Widgets attached to a position you created are removed with the position.

> **Always remember to actually run your install/upgrade steps** during development — a common
> mistake is writing the schema code but never executing it.

---

## 11. Managing the schema

### The database adapter

```php
$db = \XF::db();
```

Read helpers (always use `?` placeholders — these are prepared statements):

```php
$user      = $db->fetchRow('SELECT * FROM xf_user WHERE user_id = ?', 1);          // one row → assoc array
$username  = $db->fetchOne('SELECT username FROM xf_user WHERE user_id = ?', 1);   // single scalar
$users     = $db->fetchAll('SELECT * FROM xf_user LIMIT 10');                       // rows, numeric keys
$usersById = $db->fetchAllKeyed('SELECT * FROM xf_user LIMIT 10', 'user_id');       // rows keyed by a column
$names     = $db->fetchAllColumn('SELECT username FROM xf_user LIMIT 10');          // array of one column
$db->query('DELETE FROM xf_user WHERE user_id = ?', 1);                             // no result needed
$safe      = $db->quote($value);                                                    // manual escaping if ever needed
```

> **`fetchAllKeyed`: the 2nd argument is the key column; params go in the 3rd argument.**
>
> **SQL safety:** raw queries are NOT auto-sanitized. Always parameterize with `?` (pass
> multiple params as an array). Never concatenate user input.

### SchemaManager (preferred)

```php
$sm = \XF::db()->getSchemaManager();
```

**Create a table:**

```php
$sm->createTable('xf_some_table', function(\XF\Db\Schema\Create $table)
{
    $table->addColumn('some_id', 'int')->autoIncrement();   // becomes PRIMARY KEY automatically
    $table->addColumn('some_name', 'varchar', 50);
    $table->addColumn('user_id', 'int')->setDefault(0);
    $table->addColumn('created_date', 'int')->setDefault(0);
    $table->addKey('user_id');                              // index
    // $table->addUniqueKey(['a', 'b'], 'key_name');
});
```

**Alter a table** (existing column definitions are retained — specify only what changes):

```php
$sm->alterTable('xf_some_existing_table', function(\XF\Db\Schema\Alter $table)
{
    $table->addColumn('new_column', 'int')->setDefault(0);
    $table->changeColumn('some_existing_column')->length(250);
    // $table->renameColumn('old', 'new');
    // $table->dropColumns(['col_a', 'col_b']);
});
```

**Drop / rename tables:**

```php
$sm->dropTable('xf_some_table');
$sm->renameTable('xf_old', 'xf_new');
$exists = $sm->tableExists('xf_some_table');
$sm->alterTableAddColumnIfNotExists(/* ... */); // guard helpers exist for idempotency
```

### Automatic inference (and the one rule you must not break)

- `int` columns are **UNSIGNED** by default → reverse with `->unsigned(false)`.
- columns are **NOT NULL** by default → reverse with `->nullable(true)`.
- auto-increment columns become the **primary key** automatically.
- table config (engine, charset) is added automatically (`InnoDB` default).
- **You MUST set a default value** on every column you add/alter
  (`->setDefault(0)` / `->setDefault('')`), or you will hit errors when querying the table.

### Naming rules (critical)

- **Prefix every table with `xf_`** — a clean reinstall drops all `xf_`-prefixed tables,
  including add-on tables, so this guarantees cleanup. (e.g. `xf_demo_portal_featured_thread`.)
- **Prefix columns you add to core tables** with your add-on slug (e.g.
  `demo_portal_auto_feature`) to avoid collisions and to make ownership obvious.

---

## 12. Entities

An entity is an object wrapper around a database row. It defines its table/columns/relations
in a `Structure` and manages reading, writing, validation and the save/delete lifecycle.

### A complete entity

```php title="src/addons/Demo/Portal/Entity/FeaturedThread.php"
<?php

namespace Demo\Portal\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int   $thread_id
 * @property int   $featured_date
 *
 * RELATIONS
 * @property \XF\Entity\Thread $Thread
 */
class FeaturedThread extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_demo_portal_featured_thread';
        $structure->shortName = 'Demo\Portal:FeaturedThread';
        $structure->primaryKey = 'thread_id';
        $structure->columns = [
            'thread_id'     => ['type' => self::UINT, 'required' => true],
            'featured_date' => ['type' => self::UINT, 'default' => \XF::$time],
        ];
        $structure->getters = [];
        $structure->relations = [
            'Thread' => [
                'entity'     => 'XF:Thread',
                'type'       => self::TO_ONE,
                'conditions' => 'thread_id',
                'primary'    => true,
            ],
        ];

        return $structure;
    }
}
```

### Structure properties

| Property | Purpose |
|----------|---------|
| `table` | DB table name (used for read/write and join building). |
| `shortName` | The entity's short class name, e.g. `Demo\Portal:FeaturedThread`. |
| `contentType` | Links this entity to the content-type system (see §27). Often unset. |
| `primaryKey` | Column name, or an array for composite keys. |
| `columns` | Map of column → definition (see below). |
| `getters` | Map of pseudo-field → `true` (calls `getX()`); can also override real columns. |
| `relations` | Map of relation name → definition. |
| `behaviors` | Reusable change-time behaviors (e.g. `XF:Likeable`, `XF:ChangeLoggable`). |
| `options` | Per-entity flags read by lifecycle code (e.g. `admin_edit`). |
| `defaultWith` | Relations to always eager-load. |
| `withAliases` | Named groups of `with` relations. |

### Column types (constants on `Entity`)

`self::INT`, `self::UINT`, `self::FLOAT`, `self::BOOL`, `self::STR`, `self::BINARY`,
`self::JSON` / `self::JSON_ARRAY` (encoded/decoded automatically),
`self::SERIALIZED` / `self::SERIALIZED_ARRAY`, `self::LIST_COMMA`, `self::LIST_LINES`.
XF encodes/decodes based on the type (e.g. `json_encode` an array on write, `json_decode` on
read), so reads/writes return native PHP types.

### Column definition keys

```php
'username' => [
    'type'      => self::STR,
    'maxLength' => 50,
    'required'  => 'please_enter_valid_name', // true, or a phrase name for the error
],
'user_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true, 'changeLog' => false],
'style_id' => ['type' => self::UINT, 'default' => 0],
'state' => ['type' => self::STR, 'default' => 'visible',
    'allowedValues' => ['visible', 'moderated', 'deleted']],
```

Common keys: `type`, `default`, `required` (bool or phrase), `maxLength`, `allowedValues`,
`unique`, `autoIncrement`, `nullable`, `changeLog`, `censor`, `match` (e.g. `'email'`).

**Per-field verification:** if a setter named `verify<Column>()` exists, it runs before the
value is written. Return `true` to accept (optionally transforming `$value` by reference), or
throw/return false to reject:

```php
protected function verifyStyleId(&$value)
{
    if ($value && !$this->em()->find('XF:Style', $value))
    {
        $this->error('Invalid style.');
        return false;
    }
    return true;
}
```

### Getters

```php
$structure->getters = [
    'is_super_admin' => true,   // calls getIsSuperAdmin()
    'last_activity'  => true,   // overrides the stored column value
];

public function getLastActivity()
{
    // e.g. read from session activity instead of the cached column
    return $this->SessionActivity ? $this->SessionActivity->view_date : $this->last_activity_;
}
```

Bypass a getter and read the **raw** stored value by suffixing an underscore:
`$user->last_activity_`.

### Relations

```php
$structure->relations = [
    // one-to-one
    'Admin' => [
        'entity'     => 'XF:Admin',
        'type'       => self::TO_ONE,
        'conditions' => 'user_id',
        'primary'    => true,
    ],
    // one-to-many, keyed by a column
    'ConnectedAccounts' => [
        'entity'     => 'XF:UserConnectedAccount',
        'type'       => self::TO_MANY,
        'conditions' => 'user_id',
        'key'        => 'provider',
    ],
];
```

- Access a `TO_ONE` relation directly: `$user->Admin->last_login` (lazy-loads with one query
  if not eager-joined).
- A `TO_MANY` relation returns a collection (lazy-loaded). You **cannot** eager-join an entire
  `TO_MANY`, but you can join a **single** keyed member: `->with('ConnectedAccounts|facebook')`.
- `conditions` may be a single column (shorthand) or an array of explicit match expressions.

### Lifecycle

Override these protected methods on the entity (or use `entity_*` listeners for core
entities):

- `_preSave()` — validate / set derived values before saving.
- `_postSave()` — after save, before transaction commit (enqueue jobs, update related rows).
- `_preDelete()` / `_postDelete()` — around deletion.

```php
protected function _preSave()
{
    if ($this->isChanged('user_group_id') || $this->isChanged('secondary_group_ids'))
    {
        $this->display_style_group_id =
            $this->getUserGroupRepo()->getDisplayGroupIdForUser($this);
    }
}

protected function _postSave()
{
    if ($this->isUpdate() && $this->isChanged('username') && $this->getExistingValue('username') !== null)
    {
        $this->app()->jobManager()->enqueue('XF:UserRenameCleanUp', [
            'originalUserId'   => $this->user_id,
            'originalUserName' => $this->getExistingValue('username'),
            'newUserName'      => $this->username,
        ]);
    }
}
```

### Entity state & mutation methods

| Method | Purpose |
|--------|---------|
| `isInsert()` / `isUpdate()` | New row vs existing row. |
| `isChanged('col')` | Whether a field changed since last save. |
| `isStateChanged('col', 'value')` | Returns `'enter'`, `'leave'`, or `false`. |
| `getExistingValue('col')` | The pre-change value. |
| `exists()` | Whether the row exists in the DB. |
| `save()` / `delete()` | Persist / remove (run full lifecycle + validation). |
| `fastUpdate('col', $value)` | Write a single column immediately, skipping the full save cycle (good for cached counters/flags). |
| `setError($msg)` / `getErrors()` | Validation errors. |
| `getRelationOrDefault('Rel', $cache = true)` | Return the related entity, **creating a default (unsaved) one** if none exists (with FK/defaults filled). |
| `hydrateRelation('Rel', $entity)` | Manually attach a fetched relation. |
| `addCascadedSave($entity)` | Save another entity within the same transaction. |

### Creating, finding & saving

```php
// create new
$featured = \XF::em()->create('Demo\Portal:FeaturedThread');
$featured->thread_id = $thread->thread_id;
$featured->save();

// find by primary key
$thread = \XF::em()->find('XF:Thread', 123);

// create-or-default via a relation (used in services)
/** @var \Demo\Portal\Entity\FeaturedThread $featured */
$featured = $thread->getRelationOrDefault('FeaturedThread');
$featured->save();
$thread->fastUpdate('demo_portal_featured', true);
```

---

## 13. Finders

The Finder builds queries programmatically and returns **entities** (not arrays).

```php
$finder = \XF::finder('XF:User');
$user   = $finder->where('user_id', 1)->fetchOne();   // one Entity (or null)
$users  = \XF::finder('XF:User')->limit(10)->fetch(); // an ArrayCollection of entities
```

### where / whereOr

`where($column, $operator, $value)`; two args implies `=`. Operators: `=`, `<>`, `!=`, `>`,
`>=`, `<`, `<=`, `LIKE`, `BETWEEN`.

```php
$users = \XF::finder('XF:User')
    ->where('user_state', 'valid')
    ->where('register_date', '>=', \XF::$time - 86400 * 7)
    ->fetch();

// array form (AND)
$users = \XF::finder('XF:User')->where([
    'user_state' => 'valid',
    ['register_date', '>=', \XF::$time - 86400 * 7],
])->fetch();

// OR group
$users = \XF::finder('XF:User')->whereOr(
    ['user_state', '<>', 'valid'],
    ['message_count', 0]
)->fetch();
```

For `LIKE`, escape wildcards: `$finder->where('username', 'LIKE', $finder->escapeLike($q, '%?%'))`.

### with (joins, requires defined relations)

```php
// 2nd arg true => INNER JOIN (must exist); default is LEFT JOIN
$thread = \XF::finder('XF:Thread')
    ->with('Forum', true)
    ->with('User')
    ->where('thread_id', 123)
    ->fetchOne();

// nested + multiple
$finder->with(['Forum', 'User'], true);
$finder->with('Thread.Forum.Node.Permissions|' . \XF::visitor()->permission_combination_id);
$finder->with('ConnectedAccounts|facebook'); // single keyed TO_MANY member
```

### order / limit / pagination

```php
$finder->order('message_count', 'DESC')->order('register_date');   // multi-column
$finder->limit(10);                 // or pass into fetch(): $finder->fetch(10)
$finder->limit(10, 100);            // limit, offset
$finder->limitByPage(3, 20);        // page 3, 20/page → limit 20 offset 40
$finder->limitByPage(3, 20, 1);     // over-fetch by 1 (detect "has next page")
$finder->setDefaultOrder('featured_date', 'DESC'); // overridable default ordering
```

### Fetching & terminal methods

```php
$collection = $finder->fetch();           // ArrayCollection of entities
$entity     = $finder->fetchOne();        // single entity or null
$total      = $finder->total();           // COUNT(*) for the current conditions
$names      = $finder->pluckFrom('username')->fetch(); // array of one column
$names      = $finder->pluckFrom('username', 'user_id')->fetch(); // keyed
$sql        = $finder->getQuery();        // inspect the SQL the finder will run
```

`ArrayCollection` is iterable and offers `count()`, `first()`, `last()`, `filter()`,
`pluckNamed()`, `groupBy()`, `toArray()`, `sliceToPage($page, $perPage)`, etc.:

```php
$visible = $finder->fetch()
    ->filter(fn(\Demo\Portal\Entity\FeaturedThread $f) => $f->Thread->canView())
    ->sliceToPage($page, $perPage);

$threads = $visible->pluckNamed('Thread');                  // collection of related Thread entities
$posts   = $threads->pluckNamed('FirstPost', 'first_post_id'); // keyed collection
```

### Custom finder classes

Optional; add reusable query helpers. Resolved from the short name (`Demo\Portal:FeaturedThread`
→ `Demo\Portal\Finder\FeaturedThread`). Return `$this` so calls chain.

```php title="src/addons/Demo/Portal/Finder/FeaturedThread.php"
<?php

namespace Demo\Portal\Finder;

use XF\Mvc\Entity\Finder;

class FeaturedThread extends Finder
{
    public function applyFeaturedOrder($direction = 'ASC')
    {
        if (\XF::options()->demoPortalDefaultSort == 'featured_date')
        {
            $this->setDefaultOrder('featured_date', $direction);
        }
        else
        {
            $this->setDefaultOrder('Thread.post_date', $direction);
        }
        return $this;
    }
}
```

```php
\XF::finder('XF:User')->isRecentlyActive(20)->order('message_count', 'DESC')->limit(10);
```

---

## 14. Repositories

Repositories hold reusable query builders (returning **finders**, not results — so add-ons can
extend them and modify before fetching) plus cache-rebuild logic. Resolved
`Demo\Portal:FeaturedThread` → `Demo\Portal\Repository\FeaturedThread`.

```php title="src/addons/Demo/Portal/Repository/FeaturedThread.php"
<?php

namespace Demo\Portal\Repository;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class FeaturedThread extends Repository
{
    /** @return Finder */
    public function findFeaturedThreadsForPortalView()
    {
        $visitor = \XF::visitor();

        $finder = $this->finder('Demo\Portal:FeaturedThread');
        $finder
            ->setDefaultOrder('featured_date', 'DESC')
            ->with('Thread', true)
            ->with('Thread.User')
            ->with('Thread.Forum', true)
            ->with('Thread.Forum.Node.Permissions|' . $visitor->permission_combination_id)
            ->with('Thread.FirstPost', true)
            ->with('Thread.FirstPost.User')
            ->where('Thread.discussion_type', '<>', 'redirect')
            ->where('Thread.discussion_state', 'visible');

        return $finder; // caller can add ->limitByPage(), ->limit(), etc.
    }
}
```

Use it:

```php
/** @var \Demo\Portal\Repository\FeaturedThread $repo */
$repo   = \XF::repository('Demo\Portal:FeaturedThread');
$finder = $repo->findFeaturedThreadsForPortalView()->limitByPage($page, $perPage);
$rows   = $finder->fetch();
```

> Inside a controller, prefer `$this->repository('...')` and `$this->finder('...')` which use
> the request's app container.

---

## 15. Routing

Routes are managed in ACP → Development → Routes, in two groups: **Public** and **Admin**.
A route consists of:

- **Route prefix** — the bit after `index.php?` and before the first `/` (e.g. `account`,
  `members`, `portal`). First step in choosing a controller.
- **Section context** — which nav item highlights for pages on this route. Public: the
  top-level nav ID; Admin: the most specific admin-nav ID.
- **Controller** — short class name, e.g. `XF:Account` → `XF\Pub\Controller\Account` (infix
  `Pub` inferred from the route type). For an add-on: `Demo\Portal:Portal` →
  `Demo\Portal\Pub\Controller\Portal`.

The part of the URL after the prefix selects the **action**: `account/account-details` →
`actionAccountDetails()`. No action → `actionIndex()`.

### Route format (extracting parameters)

```
:int<user_id,username>/:page
```

- `:int<user_id,username>` — an integer param; outgoing links use `user_id`, and a `username`
  (if present) is slugified and prepended (`your-name.1`). Incoming, only the integer matters
  (so `not-your-name.1` still resolves, then redirects to canonical).
- `:page` — handles the `page-123` segment automatically (pulled from / pushed to params).
- `:str<name>` — a string param.

Members sub-name example: `members/:int<user_id,username>/following/:page`.

### ParameterBag

Every action receives a `ParameterBag` holding route-matched params (kept separate from
ordinary query params):

```php
public function actionView(\XF\Mvc\ParameterBag $params)
{
    $userId = $params->user_id;
    // ...
}
```

### Sub-names

A route may define sub-names so a deeper URL maps to different params/controllers, tested
before the basic route. e.g. `members/following` matches a `following` sub-name of `members`
rather than treating `following` as an action. Used by Resource Manager / Media Gallery.

### Building links (in PHP and templates)

```php
$url = $this->buildLink('threads', $thread);            // controller helper
$url = $this->buildLink('threads', $thread, ['page' => 2]);
$url = \XF::app()->router('public')->buildLink('canonical:portal');
```

```html
{{ link('threads', $thread) }}
{{ link('forums', $thread.Forum) }}
{{ link('canonical:portal') }}
```

---

## 16. Controllers & reply types

A controller's `action*` methods handle a request and **return a Reply object**. Public
controllers extend `\XF\Pub\Controller\AbstractController`; admin controllers extend
`\XF\Admin\Controller\AbstractController`.

```php title="src/addons/Demo/Portal/Pub/Controller/Portal.php"
<?php

namespace Demo\Portal\Pub\Controller;

class Portal extends \XF\Pub\Controller\AbstractController
{
    public function actionIndex()
    {
        $page    = $this->filterPage();
        $perPage = $this->options()->demoPortalFeaturedPerPage;

        /** @var \Demo\Portal\Repository\FeaturedThread $repo */
        $repo   = $this->repository('Demo\Portal:FeaturedThread');
        $finder = $repo->findFeaturedThreadsForPortalView()->limit($perPage * 3);

        $featuredThreads = $finder->fetch()
            ->filter(fn(\Demo\Portal\Entity\FeaturedThread $f) => $f->Thread->canView())
            ->sliceToPage($page, $perPage);

        // Batch-load attachments to avoid N+1 queries
        $threads = $featuredThreads->pluckNamed('Thread');
        $posts   = $threads->pluckNamed('FirstPost', 'first_post_id');
        /** @var \XF\Repository\Attachment $attachRepo */
        $attachRepo = $this->repository('XF:Attachment');
        $attachRepo->addAttachmentsToContent($posts, 'post');

        $viewParams = [
            'featuredThreads' => $featuredThreads,
            'total'           => $finder->total(),
            'page'            => $page,
            'perPage'         => $perPage,
        ];
        return $this->view('Demo\Portal:View', 'demo_portal_view', $viewParams);
    }
}
```

### Reply types

**View** — render a template. Args: view class short name (need not exist; it's an extension
point), template name, view params.

```php
return $this->view('Demo:Example', 'demo_example', ['hello' => 'Hello', 'world' => 'world!']);
```

**Redirect** — after a successful action.

```php
return $this->redirect($this->buildLink('demo/example'), 'Saved.'); // temporary (303)
return $this->redirect($url, '', 'permanent');                      // permanent (301)
return $this->redirectPermanently($this->buildLink('demo/example'));
```

The message arg only shows on AJAX submissions that suppress the redirect (a flash message);
default is "Your changes have been saved."

**Error** — message + HTTP code.

```php
return $this->error('Could not be found.', 404);
return $this->error(\XF::phrase('demo_portal_some_error'));
```

**Message** — like Error but not styled as an error (success/info).

```php
return $this->message('Done!');
```

**Exception** — interrupt flow; must wrap another reply and be **thrown**, not returned.

```php
throw $this->exception($this->error('An unexpected error occurred'));
throw $this->exception($this->notFound());
throw $this->exception($this->noPermission());
```

**Reroute** — render a different controller/action without redirecting or changing the URL.

```php
return $this->rerouteController(__CLASS__, 'error');
return $this->rerouteController('XF:Forum', 'index', ['node_id' => 2]); // 3rd arg: params/ParameterBag
```

### Input filtering

```php
$value   = $this->filter('key', 'type');           // single value
$values  = $this->filter(['a' => 'int', 'b' => 'str']); // many at once
$page    = $this->filterPage();                    // current page number
$input   = $this->filter('foo_criteria', 'array');
$flag    = $this->filter('demo_portal_auto_feature', 'bool');
$set     = $this->filter('_xfSet', 'array-bool');
```

Filter types include: `int`, `uint`, `float`, `bool`, `str`, `string`, `array`, `array-bool`,
`json-array`, `datetime`, `dateTime`, `unsigned`, plus array variants like `uint[]`.

### Assertions & guards (throw replies automatically)

```php
$thread = $this->assertRecordExists('XF:Thread', $params->thread_id, ['Forum', 'User']);
$this->assertPostOnly();          // require POST
$this->assertValidCsrfToken();    // CSRF protection
$this->assertRegistrationRequired();
$this->assertCanonicalUrl($this->buildLink('threads', $thread));
if (!$thread->canView())
{
    return $this->noPermission();
}
```

### Common controller helpers

`$this->view()`, `$this->redirect()`, `$this->error()`, `$this->message()`,
`$this->notFound()`, `$this->noPermission()`, `$this->buildLink()`, `$this->filter()`,
`$this->filterPage()`, `$this->repository()`, `$this->finder()`, `$this->em()`,
`$this->options()`, `$this->app()`, `$this->plugin()`, `$this->service('XF:Foo\Bar', ...)`,
`$this->isPost()`, `$this->request()`.

### Extending a core controller action (properly)

```php
public function actionExample()
{
    $reply = parent::actionExample();

    if ($reply instanceof \XF\Mvc\Reply\View)
    {
        $reply->setParam('hello', 'Bonjour');
    }
    return $reply;
}
```

### FormAction (admin CRUD extension point)

Many admin "save" flows build a `\XF\Mvc\FormAction` with phases that run in order:
`setup` → `validate` → `apply` → `complete`. Extend a save method and attach behavior:

```php title="src/addons/Demo/Portal/XF/Admin/Controller/Forum.php"
<?php

namespace Demo\Portal\XF\Admin\Controller;

use XF\Mvc\FormAction;

class Forum extends XFCP_Forum
{
    protected function saveTypeData(FormAction $form, \XF\Entity\Node $node, \XF\Entity\AbstractNode $data)
    {
        parent::saveTypeData($form, $node, $data);

        $form->setup(function() use ($data)
        {
            $data->demo_portal_auto_feature = $this->filter('demo_portal_auto_feature', 'bool');
        });
    }
}
```

---

## 17. Templates

Three types — **Public**, **Admin**, **Email** — same syntax, stored/rendered separately with
different globals. Any type may also be a CSS/LESS template (compiled as a stylesheet). In dev
mode, templates live in `_output/templates/<type>/<name>.html` and are editable on disk.

### Syntax

- `{$variable}` — output a variable (**auto-escaped**).
- `{{ expression }}` — evaluate: function calls, filters, math, ternaries.
- `<xf:tag />` — control structures, UI components, page structure.

```html
<h1>{$title}</h1>
<p>{{ phrase('welcome_message') }}</p>
<p>{{ date($xf.time, 'M j, Y') }}</p>
```

> Use `{$var}` for plain output, `{{ }}` for expressions. Mixing them up is the most common
> template mistake.

### Escaping & safety

All output is auto-escaped (XSS-safe). To output **already-trusted** HTML use `|raw`; for
attribute context use `|for_attr`:

```html
{$renderedHtml|raw}
<div title="{$description|for_attr}">…</div>
```

> Never use `|raw` on user-supplied content — only on sanitized/trusted values.

### Data flow & globals

Controllers pass `$viewParams`; each key becomes a variable. There is **no** way for a
template to query the DB or call arbitrary PHP (keeps logic in controllers/services). Every
template also has the `$xf` global:

| Variable | Description |
|----------|-------------|
| `$xf.visitor` | Current user entity |
| `$xf.visitor.user_id` | Current user ID (0 = guest) |
| `$xf.visitor.is_admin` | Admin flag |
| `$xf.options.{name}` | Option values |
| `$xf.time` | Current timestamp |
| `$xf.language` / `$xf.style` | Language / style objects |
| `$xf.debug` | Debug mode flag |
| `$xf.versionId` | XF version ID |

### Phrases in templates

```html
<h1>{{ phrase('demo_page_header') }}</h1>
{{ phrase('greeting_with_name', {'username': $xf.visitor.username}) }}
{{ phrase_dynamic($phraseName) }}   {# when the name is runtime-determined #}
```

The phrase name in `phrase()` must be a string literal (compile-time tracking); use
`phrase_dynamic()` otherwise.

### Page structure tags

```html
<xf:title>{{ phrase('demo_page') }}</xf:title>
<xf:h1>Visible heading (overrides title's h1)</xf:h1>
<xf:description>{{ phrase('...') }}</xf:description>
<xf:breadcrumb href="{{ link('demo') }}">{{ phrase('demo') }}</xf:breadcrumb>
<xf:sidebar>
    <div class="block"><div class="block-container">
        <h3 class="block-header">{{ phrase('info') }}</h3>
        <div class="block-body block-row">Sidebar content</div>
    </div></div>
</xf:sidebar>
```

### Control structures

```html
<xf:if is="$active">
    <p>{{ phrase('the_feature_is_active') }}</p>
<xf:elseif is="$other" />
    ...
<xf:else />
    ...
</xf:if>

<xf:foreach loop="$items" value="$item" key="$k" i="$i">  {# $i is 1-based #}
    <xf:set var="$rowClass" value="{{ $i % 2 == 1 ? 'odd' : 'even' }}" />
    <div class="block-row block-row--{$rowClass}">
        <h3>{$item.title}</h3>
        <p>{{ date($item.created_date, 'M j, Y') }} — {{ $item.value | number(2) }}</p>
    </div>
<xf:else />
    <div class="block-row">{{ phrase('no_items_found') }}</div>
</xf:foreach>
```

`<xf:foreach>` supports an `<xf:else />` empty-state branch.

### Reusing template code

| Approach | Use when |
|----------|----------|
| **Macro** | Repeatable component called with different data; own scope. |
| **Include** | Pull in a full template sharing the current scope. |
| **Extends** | Pages share a layout; children override sections. |
| **Wrap** | A content template names the layout that surrounds it. |

```html
{# Macro definition (arg with "!" is required) #}
<xf:macro id="item_row" arg-item="!" arg-showDate="true">
    <div class="block-row">
        <h3>{$item.title}</h3>
        <xf:if is="$showDate"><span class="u-muted">{{ date($item.created_date, 'M j, Y') }}</span></xf:if>
    </div>
</xf:macro>

{# Call from another template: template::macroId #}
<xf:macro id="demo_macros::item_row" arg-item="{$item}" />

{# Include with variable remap/inject #}
<xf:include template="demo_shared_header">
    <xf:map from="$items" to="$headerItems" />
    <xf:set var="$showCount" value="true" />
</xf:include>

{# Inheritance #}
<xf:extends template="demo_base_layout" />
<xf:extension id="content">
    <xf:extensionparent />   {# append to parent instead of replacing #}
    <p>Extra content.</p>
</xf:extension>

{# Wrapping (wrapper receives {$innerContent|raw}) #}
<xf:wrap template="demo_account_wrapper">
    <xf:set var="$activeTab" value="settings" />
</xf:wrap>
```

> Tutorial macros also use the `name="…"` attribute form (e.g.
> `<xf:macro name="thread_block" arg-thread="!">`). Both `id`/`name` forms appear in XF
> templates; `name` is widely used for in-template macros, and `template::macro` / `id` for
> cross-template calls.

### CSS & JS

```html
<xf:css src="demo_styles.less" />
<xf:js src="demo/addon/main.js" />
```

Both support inline content and `prod`/`dev`/`min` options for `<xf:js>`.

### Forms

Use `*row` tags (label + control + hint + error + a11y in one). `ajax="true"` enables inline
validation + success flash without a reload.

```html
<xf:form action="{{ link('demo/save') }}" ajax="true" class="block">
  <div class="block-container"><div class="block-body">

    <xf:textboxrow name="title" value="{$title}"
        label="{{ phrase('title') }}" explain="{{ phrase('enter_a_title') }}" />

    <xf:selectrow name="category" value="{$categoryId}" label="{{ phrase('category') }}">
        <xf:options source="{$categories}" />
    </xf:selectrow>

    <xf:checkboxrow label="{{ phrase('options') }}">
        <xf:option name="is_enabled" value="1" selected="{$isEnabled}">{{ phrase('enabled') }}</xf:option>
    </xf:checkboxrow>

    <xf:numberboxrow name="per_page" value="{$perPage}" label="..." />
    <xf:textarearow name="body" value="{$body}" label="..." />

    <xf:submitrow submit="{{ phrase('save') }}" sticky="true" icon="save" />
  </div></div>
</xf:form>
```

Each `*row` has a standalone control variant (`<xf:textbox />`, `<xf:select />`, …). When no
specialized row fits, wrap custom HTML with `<xf:formrow>`. Other useful tags: `<xf:hiddenval>`,
`<xf:hint>`, `<xf:afterhtml>`, `<xf:option>` with nested inputs, `<xf:pagenav>`:

```html
<xf:pagenav page="{$page}" perpage="{$perPage}" total="{$total}" link="portal" wrapperclass="block" />
```

### Widget position in a template

```html
<xf:widgetpos id="demo_portal_view_sidebar" position="sidebar" />
```

### Template callbacks (escape hatch)

```html
<xf:callback class="Demo\Template" method="renderStats" params="['sidebar']"></xf:callback>
```

The method name must start with a read-only prefix (`get`, `render`, `is`, `has`) so callbacks
can't mutate state. Prefer passing data from the controller; use callbacks only when needed
(e.g. a sidebar across many pages).

### Common template functions / filters

Functions: `phrase()`, `phrase_dynamic()`, `link()`, `date()`, `date_time()`, `time()`,
`number()`, `bb_code()`, `bb_code_clean()`, `avatar()`, `username()`, `prop()` (style prop),
`property()`, `unique_id()`, `entity()`. Filters: `|raw`, `|for_attr`, `|number`, `|number(2)`,
`|date`, `|escape`, `|nl2br`, `|json`, `|first`, `|last`, `|count`, `|wrap`.

### Rendering BB code / posts (example from the portal macro)

```html
{{ bb_code($post.message, 'post', $post.User, {
    'attachments':     $post.attach_count ? $post.Attachments : [],
    'viewAttachments': $thread.canViewAttachments()
}) }}
```

---

## 18. Phrases

All user-visible strings are phrases (translatable, admin-editable). Create them in ACP →
Appearance → Phrases (with your add-on selected). In dev mode they export to
`_output/phrases/<title>.txt`.

- **Title** = the phrase name (referenced in code/templates), e.g. `demo_portal_featured`.
- **Text** = the displayed value, e.g. `Featured`.
- Parameters use `{name}` placeholders: text `Hello, {username}!`.

Reference:

```html
{{ phrase('demo_portal_featured') }}
{{ phrase('greeting_with_name', {'username': $xf.visitor.username}) }}
```

```php
\XF::phrase('demo_portal_featured');
\XF::phrase('greeting_with_name', ['username' => $user->username]);
$text = \XF::phrase('demo_portal_featured')->render(); // force to string
```

> Convention: prefix phrase titles with your add-on slug (`demo_portal_…`) to avoid clashes.
> Phrases you create in templates/options are auto-detected; phrases used **only inside
> template-modification replacements** can't be auto-detected, so create those manually.

Naming conventions XF itself uses: option titles/descriptions, permission titles, etc. follow
specific patterns (e.g. `option_explain.<id>`), but for general UI text any unique title works.

---

## 19. Template modifications

Modify existing templates (core or third-party) **without editing them**, so multiple add-ons
can coexist. ACP → Appearance → Template modifications → choose **Public**/**Admin** tab → Add.

Fields: **Template** (name to modify), **Modification key** (unique, convention: addon +
template), **Description**, **Search type** (Simple / Regular expression), **Find**, **Replace**,
**Execute order**, add-on.

In a replacement, `$0` re-inserts the matched text (so you can wrap/prepend/append instead of
removing it). Use the **Test** button to preview (green = inserted).

### Simple replacement (prepend a checkbox before another option)

```html title="Find"
<xf:option name="allow_posting"
```

```html title="Replace"
<xf:option name="demo_portal_auto_feature" selected="$forum.demo_portal_auto_feature"
    label="Automatically feature threads in this forum"
    hint="If selected, any new threads posted in this forum will be automatically featured." />
$0
```

### Inserting into an existing row/tag

When you must place markup **inside** an existing tag (an include won't work there), write
template code directly in the Replace field:

```html title="Find"
<xf:if is="$thread.canLockUnlock()">
```

```html title="Replace"
<xf:if is="($thread.isInsert() AND !$thread.Forum.demo_portal_auto_feature AND $thread.canFeatureUnfeature())
    OR ($thread.isUpdate() && $thread.canFeatureUnfeature())">
    <xf:option label="{{ phrase('demo_portal_featured') }}" name="featured" value="1" selected="{$thread.demo_portal_featured}">
        <xf:hint>{{ phrase('demo_portal_featured_hint') }}</xf:hint>
        <xf:afterhtml>
            <xf:hiddenval name="_xfSet[featured]" value="1" />
        </xf:afterhtml>
    </xf:option>
</xf:if>
$0
```

### Regular-expression replacement

Set Search type to "Regular expression". Example — append to the very end of a template
(used to add a custom criteria tab/pane to `helper_criteria`): Find `/$/`, Replace = your
macros. Standard PCRE with delimiters; remember `$0` is still the whole match.

> Phrases referenced only inside a template modification's Replace must be **created
> manually** (they can't be auto-extracted).

---

## 20. Widgets & widget positions

### Widget positions

Define where widgets can render. ACP → Development → Widget positions → Add. Give a **Position
ID** (e.g. `demo_portal_view_sidebar`), title, description, enable it, select your add-on.

Render the position in a template:

```html
<xf:widgetpos id="demo_portal_view_sidebar" position="sidebar" />
```

`position="sidebar"` tells XF the rendering context (sidebar styling). Use a unique position
context where appropriate.

### Shipping default widgets

Widgets themselves are **not** owned by add-ons, so create default instances in your Setup
class (so they appear on install and are removed when your position is removed):

```php
public function installStep4()
{
    $this->createWidget('demo_portal_view_members_online', 'members_online', [
        'positions' => ['demo_portal_view_sidebar' => 10],
    ]);
    $this->createWidget('demo_portal_view_new_posts', 'new_posts', [
        'positions' => ['demo_portal_view_sidebar' => 20],
    ]);
    $this->createWidget('demo_portal_view_forum_statistics', 'forum_statistics', [
        'positions' => ['demo_portal_view_sidebar' => 40],
    ]);
}
```

`createWidget($widgetKey, $definitionId, $config)` — `$config['positions']` maps position ID →
display order. You don't need an uninstall step for these; removing the position removes them.

### Custom widget definitions

To create a brand-new widget type, add a **Widget definition** (ACP → Development) pointing at
a renderer class extending `\XF\Widget\AbstractWidget`, implementing `render()` (return
`$this->renderer('template', $viewParams)`) and optionally `getOptionsTemplate()` /
`verifyOptions()` for configurable widgets.

```php title="src/addons/Demo/Portal/Widget/FeaturedCount.php"
<?php

namespace Demo\Portal\Widget;

use XF\Widget\AbstractWidget;

class FeaturedCount extends AbstractWidget
{
    protected $defaultOptions = ['limit' => 5];

    public function render()
    {
        $total = \XF::finder('Demo\Portal:FeaturedThread')->total();
        return $this->renderer('demo_portal_widget_featured_count', ['total' => $total]);
    }
}
```

---

## 21. Permissions

Define permissions in ACP → Development → Permission definitions → Add permission.

Fields: **Permission group** (e.g. `forum`), **Permission ID** (e.g. `demoPortalFeature`),
**Title**, **Permission type** (Flag / Integer), **Interface group** (where it appears, e.g.
`Forum moderator permissions`), display order, add-on.

### Checking permissions

```php
// Global / group permission
\XF::visitor()->hasPermission('forum', 'demoPortalFeature');

// Per-node (content) permission
\XF::visitor()->hasNodePermission($nodeId, 'demoPortalFeature');

// Other content permissions
\XF::visitor()->hasContentPermission($contentType, $contentId, $permissionId);
```

Example: gate a Thread entity method behind a node permission (added via class extension):

```php title="src/addons/Demo/Portal/XF/Entity/Thread.php"
<?php

namespace Demo\Portal\XF\Entity;

class Thread extends XFCP_Thread
{
    public function canFeatureUnfeature()
    {
        return \XF::visitor()->hasNodePermission($this->node_id, 'demoPortalFeature');
    }
}
```

> **Permissions have no default value** — a new permission is effectively "not granted" until
> an admin sets it (or you set defaults in Setup via `applyGlobalPermission()` /
> `applyContentPermission()` during install/upgrade). This is why a newly added checkbox may
> "disappear" until you grant yourself the permission.

The permission-combination cache is what `with('...Node.Permissions|' . $visitor->permission_combination_id)`
joins against to evaluate `canView()`/node permissions efficiently in list queries.

---

## 22. Options

Group options, then add options. ACP → Setup → Options → Add option group, then Add option.

- **Option group**: Group ID (e.g. `demoPortal`), title, description, display order.
- **Option**: Option ID (e.g. `demoPortalFeaturedPerPage`), title, **Edit format** (Text box,
  Spin box, On/off checkbox, Radio buttons, Select, Textarea, PHP/Template callback…),
  **Data type** (string/integer/positive integer/unsigned/boolean/array…), **Default value**,
  and optional **Format parameters**.

Radio/select **Format parameters** map value → label (phrases allowed):

```title="Format parameters"
featured_date={{ phrase('demo_portal_featured_date') }}
post_date={{ phrase('demo_portal_post_date') }}
```

### Reading options

```php
$perPage = $this->options()->demoPortalFeaturedPerPage;  // in a controller
$sort    = \XF::options()->demoPortalDefaultSort;        // anywhere
```

```html
<xf:if is="$xf.options.demoPortalDefaultSort == 'featured_date'">
    <xf:date time="{$featuredThread.featured_date}" />
<xf:else />
    <xf:date time="{$thread.post_date}" />
</xf:if>
```

> Option IDs are global — prefix with your add-on slug. Options you create are auto-removed on
> uninstall.

---

## 23. Services (setup-and-go)

Services encapsulate multi-step operations ("set up your config, then call a method to run").
Core examples: `XF:Thread\Creator`, `XF:Thread\Editor`, `XF:Thread\Replier`,
`XF:User\Registration`, `XF:Post\Preparer`. Instantiate with `\XF::service()` /
`$this->service()` and pass dependencies.

```php
/** @var \XF\Service\Thread\Creator $creator */
$creator = $this->service('XF:Thread\Creator', $forum);
$creator->setContent($title, $message);
if (!$creator->validate($errors))
{
    return $this->error($errors);
}
$thread = $creator->save();
```

### Extending a service to hook its save (from the tutorial)

```php title="src/addons/Demo/Portal/XF/Service/Thread/Creator.php"
<?php

namespace Demo\Portal\XF\Service\Thread;

class Creator extends XFCP_Creator
{
    protected $featureThread;

    public function setFeatureThread($featureThread)
    {
        $this->featureThread = $featureThread;
    }

    protected function _save()
    {
        $thread = parent::_save();

        if ($this->featureThread && $thread->discussion_state == 'visible')
        {
            /** @var \Demo\Portal\Entity\FeaturedThread $featuredThread */
            $featuredThread = $thread->getRelationOrDefault('FeaturedThread');
            $featuredThread->save();
            $thread->fastUpdate('demo_portal_featured', true);
        }

        return $thread;
    }
}
```

The controller that sets up the service opts in:

```php title="src/addons/Demo/Portal/XF/Pub/Controller/Forum.php"
protected function setupThreadCreate(\XF\Entity\Forum $forum)
{
    /** @var \Demo\Portal\XF\Service\Thread\Creator $creator */
    $creator = parent::setupThreadCreate($forum);

    if ($forum->demo_portal_auto_feature)
    {
        $creator->setFeatureThread(true);
    }
    else
    {
        $setOptions = $this->filter('_xfSet', 'array-bool');
        if ($setOptions)
        {
            $thread = $creator->getThread();
            if ($thread->canFeatureUnfeature() && isset($setOptions['featured']))
            {
                $creator->setFeatureThread($this->filter('featured', 'bool'));
            }
        }
    }

    return $creator;
}
```

> Writing your own service: extend `\XF\Service\AbstractService`, take dependencies in the
> constructor, expose `setX()` configurers, and a terminal method (`save()`/`run()`). Use a
> `_save()` that runs inside a DB transaction where appropriate.

---

## 24. Jobs / deferred tasks

Run long/large work outside the request (rebuilds, mass updates, cleanup). Enqueue by class +
params:

```php
\XF::app()->jobManager()->enqueue('Demo\Portal:RebuildFeatured', [
    'start' => 0,
]);

// unique recurring/queued job:
\XF::app()->jobManager()->enqueueUnique('demoPortalRebuild', 'Demo\Portal:RebuildFeatured', []);

// run immediately/manually:
\XF::app()->jobManager()->runUnique(...);
```

A job class extends `\XF\Job\AbstractJob` and returns a `JobResult`. Implement batching so each
call processes a slice and reports progress:

```php title="src/addons/Demo/Portal/Job/RebuildFeatured.php"
<?php

namespace Demo\Portal\Job;

use XF\Job\AbstractJob;

class RebuildFeatured extends AbstractJob
{
    protected $defaultData = ['start' => 0, 'batch' => 100];

    public function run($maxRunTime)
    {
        $start = $this->data['start'];
        $startTime = microtime(true);

        $threadIds = $this->app->db()->fetchAllColumn(
            'SELECT thread_id FROM xf_demo_portal_featured_thread WHERE thread_id > ? ORDER BY thread_id LIMIT ?',
            [$start, $this->data['batch']]
        );

        if (!$threadIds)
        {
            return $this->complete();
        }

        foreach ($threadIds as $threadId)
        {
            // ... do work ...
            $this->data['start'] = $threadId;

            if (microtime(true) - $startTime >= $maxRunTime)
            {
                break;
            }
        }

        return $this->resume(); // more to do
    }

    public function getStatusMessage()
    {
        return 'Rebuilding featured threads...';
    }

    public function canCancel() { return true; }
    public function canTriggerByChoice() { return false; }
}
```

`$this->complete()` ends the job; `$this->resume()` re-runs with updated `$this->data`.

---

## 25. Cron entries

Scheduled tasks. ACP → Setup → Cron entries → Add (visible with debug mode). A cron entry
points at a static callback and defines a run schedule (minute/hour/day/day-of-week). Exported
to `_output/cron/<entry_id>.json`.

```php title="src/addons/Demo/Portal/Cron/Featured.php"
<?php

namespace Demo\Portal\Cron;

class Featured
{
    public static function cleanUp()
    {
        \XF::app()->jobManager()->enqueueUnique(
            'demoPortalCleanup',
            'Demo\Portal:CleanUp',
            []
        );
    }
}
```

Then create a cron entry: callback class `Demo\Portal\Cron\Featured`, method `cleanUp`, and a
schedule. XF runs due cron entries via simulated cron on traffic (or a real system cron hitting
`cmd.php xf:cron`).

---

## 26. Admin navigation

Admin pages appear in the ACP via **Admin navigation** entries (ACP → Development → Admin
navigation). Each entry has: navigation ID, parent navigation ID, link (relative ACP route,
e.g. `demo-portal/settings`), title (phrase), display order, icon, and an optional **admin
permission**. Exported to `_output/admin_navigation/<id>.json`.

For a public top tab, use **public navigation** instead (ACP → Appearance → Navigation), or set
your route's **section context** to an existing tab (as the portal does with `home`).

> An admin route + an admin controller (`Vendor\AddOn\Admin\Controller\X`) + an admin
> navigation entry whose link matches the route prefix = a working ACP page. Section context on
> the admin route should be the navigation entry's ID so it highlights.

---

## 27. Handlers & content types

Shared systems (attachments, alerts, reactions, reports, etc.) plug in per content type via
**handlers**. Pattern: an abstract base class defines the contract; each content type provides
a concrete handler; a **content type field** registers the handler class against the content
type + system.

`xf_content_type_field` columns: `content_type` (e.g. `post`), `field_name` (the system, e.g.
`attachment_handler_class`), `field_value` (FQCN of the handler).

Register via ACP → Development → Execution manipulation → Content types (dev mode). Exported to
`_output/content_type_fields/{content_type}-{field_name}.json`:

```json title="_output/content_type_fields/demo_item-attachment_handler_class.json"
{
    "content_type": "demo_item",
    "field_name": "attachment_handler_class",
    "field_value": "Demo\\Portal\\Attachment\\ItemHandler"
}
```

Your entity must declare the matching content type:

```php
$structure->contentType = 'demo_item';
```

Discover/instantiate handlers (note `extendClass` so extensions apply):

```php
$handlers     = \XF::app()->getContentTypeField('attachment_handler_class'); // ['post' => 'XF\Attachment\PostHandler', ...]
$handlerClass = \XF::app()->getContentTypeFieldValue('post', 'attachment_handler_class');

$handlerClass = \XF::extendClass($handlerClass);
$handler = new $handlerClass($type);
```

### Handler systems (field name → base class)

| System | Field name | Base class |
|--------|-----------|-----------|
| Activity log | `activity_log_handler_class` | `XF\ActivityLog\AbstractHandler` |
| Alerts | `alert_handler_class` | `XF\Alert\AbstractHandler` |
| Approval queue | `approval_queue_handler_class` | `XF\ApprovalQueue\AbstractHandler` |
| Attachments | `attachment_handler_class` | `XF\Attachment\AbstractHandler` |
| Bookmarks | `bookmark_handler_class` | `XF\Bookmark\AbstractHandler` |
| Change log | `change_log_handler_class` | `XF\ChangeLog\AbstractHandler` |
| Content voting | `content_vote_handler_class` | `XF\ContentVote\AbstractHandler` |
| Edit history | `edit_history_handler_class` | `XF\EditHistory\AbstractHandler` |
| Featured content | `featured_content_handler_class` | `XF\FeaturedContent\AbstractHandler` |
| Find new | `find_new_handler_class` | `XF\FindNew\AbstractHandler` |
| Inline moderation | `inline_mod_handler_class` | `XF\InlineMod\AbstractHandler` |
| Moderator log | `moderator_log_handler_class` | `XF\ModeratorLog\AbstractHandler` |
| News feed | `news_feed_handler_class` | `XF\NewsFeed\AbstractHandler` |
| Polls | `poll_handler_class` | `XF\Poll\AbstractHandler` |
| Reactions | `reaction_handler_class` | `XF\Reaction\AbstractHandler` |
| Reports | `report_handler_class` | `XF\Report\AbstractHandler` |
| Sitemap | `sitemap_handler_class` | `XF\Sitemap\AbstractHandler` |
| Stats | `stats_handler_class` | `XF\Stats\AbstractHandler` |
| Tags | `tag_handler_class` | `XF\Tag\AbstractHandler` |
| Trending content | `trending_content_handler_class` | `XF\TrendingContent\AbstractHandler` |
| Warnings | `warning_handler_class` | `XF\Warning\AbstractHandler` |
| Webhook events | `webhook_handler_class` | `XF\Webhook\Event\AbstractHandler` |

---

## 28. The criteria system

Used by trophies, user-group promotions, notices, etc. to test something (user/page/post)
against admin-selected conditions. Two built-in types: **User criteria** and **Page criteria**;
add-ons can add their own.

A **criterion** = a `rule` (snake_case string) + optional `data` array. At match time the rule
is converted to a camel-case method `_match<Rule>` on the criteria type class.

### Storing selected criteria

Filter the form input and save into a `JSON_ARRAY` column:

```php
$fooCriteriaInput = $this->filter('foo_criteria', 'array');
$form->basicEntitySave($entity, ['foo_criteria' => $fooCriteriaInput]);
```

```php
'foo_criteria' => ['type' => self::JSON_ARRAY, 'default' => [],
    'required' => 'please_select_criteria_that_must_be_met'],
```

### Matching

```php
/** @var \Qux\Criteria\Foo $fooCriteria */
$fooCriteria = \XF::app()->criteria('Qux:Foo', $entity->foo_criteria);
$fooCriteria->setMatchOnEmpty(false);          // empty selection → no match (important for destructive ops!)

if ($fooCriteria->isMatched(\XF::visitor()))
{
    // matches all selected criteria
}
```

`isMatched()` maps each rule to `_match<Rule>()`. Unknown rules fall back to
`isUnknownMatched()` (default false) which fires the `criteria_<type>` event so other add-ons
can handle them. With no criteria selected it returns `$matchOnEmpty` (default true).

### Custom criterion on an existing type (User) — via listener

Add the input through a `helper_criteria` template modification, then handle the unknown rule:

```php
public static function criteriaUser($rule, array $data, \XF\Entity\User $user, &$returnValue)
{
    switch ($rule)
    {
        case 'likes_on_single':
            $likes = \XF::db()->fetchOne(
                'SELECT likes FROM xf_post WHERE user_id = ? ORDER BY likes DESC LIMIT 1',
                [$user->user_id]
            );
            $returnValue = is_int($likes) ? ($likes >= $data['likes']) : false;
            break;
    }
}
```

Register it with the `criteria_user` event, callback `YourAddOn\Listener::criteriaUser`.

### A whole new criteria type

Extend `\XF\Criteria\AbstractCriteria`, add `_match<Rule>()` methods, and (for non-User
entities) provide an `isMatched<Entity>()` variant:

```php
<?php

namespace PostsRemover\Criteria;

use XF\Criteria\AbstractCriteria;

class Post extends AbstractCriteria
{
    protected function _matchLikeCount(array $data, \XF\Entity\Post $post)
    {
        return ($post->likes && $post->likes >= $data['likes']);
    }

    public function isMatchedPost(\XF\Entity\Post $post)
    {
        if (!$this->criteria) { return $this->matchOnEmpty; }

        foreach ($this->criteria AS $criterion)
        {
            $rule = $criterion['rule'];
            $data = $criterion['data'];

            $method = '_match' . \XF\Util\Php::camelCase($rule);
            if (method_exists($this, $method))
            {
                if (!$this->$method($data, $post)) { return false; }
            }
            else if (!$this->isUnknownMatchedPost($rule, $data, $post))
            {
                return false;
            }
        }
        return true;
    }

    protected function isUnknownMatchedPost($rule, array $data, \XF\Entity\Post $post) { return false; }
}
```

Reusable selection UI lives in the admin `helper_criteria` template (per-type `*_tabs` /
`*_panes` macros). Extra template data via `getExtraTemplateData()` or the
`criteria_template_data` event. (Use a Job when matching against large datasets — never
`finder('XF:Post')->fetch()` everything.)

---

## 29. Styles, LESS & designer mode

Your add-on ships CSS as LESS templates (CSS-type templates) and includes them with
`<xf:css src="..." />`. Use **style properties** (`{{ property('...') }}` / `prop()`) for
theme-aware values. XF's framework classes (`block`, `block-container`, `message`,
`contentRow`, `listInline`, `u-muted`, etc.) keep add-on UIs consistent.

**Designer mode** edits a style's templates/properties on disk (for VCS/collaboration):

```php title="src/config.php"
$config['designer']['enabled'] = true;
$config['designer']['basePath'] = 'src/styles'; // optional (default)
```

```sh
php cmd.php xf-designer:enable [style_id] [designer_mode_id]
php cmd.php xf-designer:touch-template [designer_mode_id] public:my_template   # copy into style to edit
php cmd.php xf-designer:touch-template [designer_mode_id] public:my_template --custom  # brand-new template
php cmd.php xf-designer:sync-templates [designer_mode_id]
php cmd.php xf-designer:disable [designer_mode_id] [--clear]
```

A style only contains what is **modified in that style**; designer output reflects only those
modifications. Templates export as editable HTML (auto-imported on load); style properties
export as JSON (not auto-imported — don't hand-edit).

---

## 30. REST API & webhooks

Base URL: `<board URL>/api/` (e.g. `https://example.com/community/api/`). All endpoints are
prefixed by it.

### Authentication

- **API key** header: `XF-Api-Key: <key>`. Key types: Guest, User, Super user.
- **OAuth 2.0** (`XF-OAuth2`): authorization-code flow; token URL `…/api/oauth2/token`,
  authorize URL `…/oauth2/authorize`. Scopes are granular: `thread:read`, `thread:write`,
  `user:read`, `user:write`, `attachment:write`, `conversation:read`, `node:write`, etc.
- Act as a specific user with a user/super-user key via the `XF-Api-User` header.
- Super-user keys can bypass the context user's permissions per-request with
  `api_bypass_permissions=1`.

### Request / response format

- Request bodies: `application/x-www-form-urlencoded` (or `multipart/form-data` for uploads).
  Params may also go in the query string (prefer the body for non-GET). UTF-8 only.
- Responses are JSON (except binary downloads). Success = 200; redirect = 300-range; error =
  400-range.

Error shape:

```json
{
    "errors": [
        { "code": "api_key_not_found", "message": "API key provided in request was not found.", "params": [] }
    ]
}
```

API keys are created in ACP → Setup → API keys.

### Add-on REST endpoints

Add your own API routes via **API route** entries (admin) pointing at controllers extending
`\XF\Api\Controller\AbstractController`, returning `\XF\Api\Result\…` / `$this->apiResult(...)`.
Define **webhook events** by registering a `webhook_handler_class` content-type field (see §27)
so your content can fire outgoing webhooks.

Common built-in resources (each with GET/POST/DELETE variants): `users`, `threads`, `posts`,
`forums`/`nodes`, `conversations`, `conversation-messages`, `attachments`, `alerts`,
`profile-posts`, `media`/`media-albums`/`media-categories` (XFMG), `resources` & related
(XFRM), `search`, `me`, `stats`, `oauth2`.

---

## 31. Building & releasing

One command exports data, gathers files, computes `hashes.json`, and writes the ZIP:

```sh
php cmd.php xf-addon:build-release Demo/Portal
```

It runs `xf-addon:export`, copies into a temp `_build/`, writes the ZIP (including
`hashes.json`) to `_releases/<ADDON ID>-<VERSION STRING>.zip`.

### build.json (optional customization)

```json title="build.json"
{
    "additional_files": [
        "js/demo/portal"
    ],
    "minify": [
        "js/demo/portal/a.js",
        "js/demo/portal/b.js"
    ],
    "rollup": {
        "js/demo/portal/ab-rollup.js": [
            "js/demo/portal/a.min.js",
            "js/demo/portal/b.min.js"
        ]
    },
    "exec": [
        "echo '{title} version {version_string} ({version_id}) has been built successfully!' > 'src/addons/Demo/Portal/_build/built.txt'"
    ]
}
```

- `additional_files`: copy assets that live outside the add-on dir into the build. During dev
  you may keep them in the add-on's `_files/` dir (checked first when copying).
- `minify`: list files, or `"*"` to minify everything under `js/`. Output gets a `.min.js`
  suffix; originals are kept.
- `rollup`: combine multiple JS files into one (key = output, value = inputs).
- `exec`: shell commands to run before finalizing. Placeholders like `{title}`,
  `{version_string}`, `{version_id}` are filled from the `AddOn` object / `addon.json`.

### Release workflow

1. `xf-dev:export --addon Demo/Portal` (ensure `_output` is current).
2. `xf-addon:bump-version Demo/Portal --version-id … --version-string …`.
3. Commit to VCS.
4. `xf-addon:build-release Demo/Portal`.
5. Distribute the ZIP from `_releases`.

---

## 32. Error-prevention checklist & common gotchas

**Schema**
- [ ] Every added/altered column has an explicit `->setDefault(...)`. (Omitting it breaks
      queries.)
- [ ] All add-on tables are prefixed `xf_`; columns on core tables are prefixed with your slug.
- [ ] Ran your install/upgrade steps (`xf-addon:install-step …`) — writing schema code isn't
      enough; you must execute it.
- [ ] Uninstall steps drop the columns/tables you added (other ACP-created data auto-cleans).

**Entities / structure**
- [ ] New columns added to a core entity via an `entity_structure` listener match the DB column
      names exactly.
- [ ] `shortName` and `table` are correct; relations use valid `entity`, `type`, `conditions`.
- [ ] Don't try to eager-`with()` a whole `TO_MANY`; use a keyed single member or lazy-load.

**Class extensions**
- [ ] Extension class `extends XFCP_<Name>` (not the real parent) and is registered in ACP.
- [ ] Overridden methods call `parent::` and return the correct type.
- [ ] Before mutating a controller reply, check `instanceof \XF\Mvc\Reply\View` (etc.).
- [ ] Match the parent method signature exactly (add the right `use` imports, e.g.
      `use XF\Mvc\FormAction;`).

**Listeners**
- [ ] Correct event ID + **hint** (so it only fires for the intended class).
- [ ] Callback is `public static` with the documented signature; `&$structure` stays by-ref.

**Templates / phrases**
- [ ] `{$var}` for output, `{{ expr }}` for expressions.
- [ ] Only `|raw` trusted content; user content stays escaped.
- [ ] Phrases used only inside template-modification replacements are created **manually**.
- [ ] `phrase('literal_name')` uses a literal; use `phrase_dynamic()` for runtime names.

**Permissions / options**
- [ ] Remember permissions default to "not granted" — grant them (or set defaults in Setup) or
      your UI/feature will appear missing.
- [ ] Option/permission/route IDs are prefixed to avoid global clashes.

**Security**
- [ ] Raw SQL always uses `?` placeholders (never string-concatenate input).
- [ ] `assertPostOnly()` / CSRF handled for state-changing actions (XF form tags handle CSRF
      automatically).
- [ ] Permission-check before showing/performing privileged actions; filter all input via
      `$this->filter()`.

**General**
- [ ] Development mode on while developing (`_output` writable, `defaultAddOn` set).
- [ ] Use a VCS; `_output`↔DB desync can lose data.
- [ ] Don't ship `_output`; do ship `_data` + `hashes.json` (the build handles this).

---

## 33. Quick reference cheat-sheet

**Minimal working add-on skeleton**

```
src/addons/Demo/Portal/
├── addon.json
├── Setup.php                         # StepRunner traits + steps
├── Listener.php                      # entity_structure / lifecycle listeners
├── Entity/FeaturedThread.php
├── Repository/FeaturedThread.php
├── Finder/FeaturedThread.php         # optional
├── Pub/Controller/Portal.php
├── XF/Pub/Controller/Forum.php       # class extension
├── XF/Service/Thread/Creator.php     # class extension
├── XF/Entity/Thread.php              # class extension
└── _output/templates/public/demo_portal_view.html
```

**Bootstrapping a new add-on**

```sh
php cmd.php xf-addon:create               # create ID/title/version + Setup
# enable dev mode + defaultAddOn in config.php
# add schema in Setup, then:
php cmd.php xf-addon:install-step Demo/Portal 1
# create listeners/extensions/routes/templates/options/permissions in ACP (dev mode)
php cmd.php xf-dev:export --addon Demo/Portal
php cmd.php xf-addon:build-release Demo/Portal
```

**Most-used API calls**

```php
\XF::finder('XF:Thread')->with('Forum', true)->where('thread_id', $id)->fetchOne();
\XF::repository('Demo\Portal:FeaturedThread')->findFeaturedThreadsForPortalView();
\XF::em()->find('XF:User', $id);
\XF::em()->create('Demo\Portal:FeaturedThread');
\XF::db()->fetchOne('SELECT ... WHERE x = ?', [$x]);
\XF::db()->getSchemaManager()->createTable('xf_...', fn(\XF\Db\Schema\Create $t) => $t->addColumn('id','int')->autoIncrement());
\XF::visitor()->hasNodePermission($nodeId, 'demoPortalFeature');
\XF::options()->demoPortalFeaturedPerPage;
\XF::phrase('demo_portal_featured');
\XF::app()->jobManager()->enqueue('Demo\Portal:RebuildFeatured', []);
\XF::extendClass('XF\\Foo\\Bar');
```

**Controller replies**

```php
return $this->view('Demo\Portal:View', 'demo_portal_view', $viewParams);
return $this->redirect($this->buildLink('demo/example'), 'Saved.');
return $this->error('Not found', 404);
return $this->message('Done!');
throw $this->exception($this->noPermission());
return $this->rerouteController(__CLASS__, 'error');
```

**Template essentials**

```html
<xf:title>{{ phrase('demo_portal') }}</xf:title>
<xf:if is="$rows is not empty">
    <xf:foreach loop="$rows" value="$row" i="$i"> ... </xf:foreach>
<xf:else />
    <div class="blockMessage">{{ phrase('no_items_found') }}</div>
</xf:if>
<xf:form action="{{ link('demo/save') }}" ajax="true">
    <xf:textboxrow name="title" value="{$title}" label="{{ phrase('title') }}" />
    <xf:submitrow submit="{{ phrase('save') }}" />
</xf:form>
<xf:pagenav page="{$page}" perpage="{$perPage}" total="{$total}" link="portal" />
```

**Doc map (official):** `devs/index`, `add-on-structure`, `development-tools`,
`general-concepts`, `template-basics`, `routing-basics`, `controller-basics`,
`entities-finders-repositories`, `criteria`, `managing-the-schema`, `handlers`,
`lets-build-an-add-on`, plus the REST API reference and the `manual/reference/template-syntax`
page for the exhaustive tag/function/filter list.

---

*Built from the official XenForo Developer Documentation (cloned source in `_docs_src/`).
Targets XenForo 2.3.x. For the exhaustive template tag/function/filter reference and the full
REST endpoint list, consult `manual/reference/template-syntax` and the API reference in that
same documentation set.*












