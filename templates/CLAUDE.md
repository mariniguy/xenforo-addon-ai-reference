# CLAUDE.md — XenForo 2.3+ add-on project

> Drop this file into the root of your XenForo add-on project. Claude Code reads
> it automatically and uses it as standing context. If you also installed the
> `xenforo-addon-dev` skill (see the reference repo's README), Claude will pull
> in the deep per-topic docs on demand.

## What this project is

A **XenForo 2.x** add-on (PHP). XenForo 2 is **completely incompatible** with
XenForo 1.x. Never use XF1 patterns: no `library/`, no `DataWriter`, no `_Model`
classes, no Zend. XF2 code uses the `\XF::` facade, `XFCP_` class extensions,
entity `getStructure()`, and `<xf:...>` template tags.

## Project conventions

- **Add-on ID / namespace:** `Vendor\AddOn` (edit to match `addon.json`).
- Source lives under `src/addons/Vendor/AddOn/`.
- Ship `_data/*.xml` master data; never hand-edit or ship `_output/`.
- Build releases with `php cmd.php xf-addon:build-release Vendor/AddOn`.
- Target XenForo 2.3.x, PHP 7.2+ (8.1+ recommended).

## The mental model

```
Request → Route (_data/routes.xml) → Controller action → Repository/Finder → Entity
        → $this->view('Vendor\AddOn:X', 'template', $params) → Template (<xf:...>)
Background: Service · Job (queue) · Cron
Extend core: Class extension (XFCP) OR Code event listener
Install/upgrade/uninstall: Setup.php (schema) + _data/*.xml (master data)
```

## The `\XF` facade (use everywhere)

```php
\XF::app() \XF::db() \XF::em()
\XF::finder('Vendor\AddOn:Thing')      // build a query
\XF::repository('Vendor\AddOn:Thing')  // shared read/query logic
\XF::service('Vendor\AddOn:Thing\Creator', ...args)  // business logic with side effects
\XF::visitor()      // current user entity; user_id 0 = guest
\XF::options()      // board options
\XF::phrase('key')  // translatable phrase
```

## Rules for Claude

1. Before writing code for a subsystem, **read the matching reference doc** (the
   `xenforo-addon-dev` skill indexes them). Don't guess method or column names.
2. Mirror `examples/demo-portal/` for structure.
3. Keep entities, repositories, finders, controllers, and templates in their
   correct directories (`Entity/`, `Repository/`, `Finder/`, `Pub/Controller/`,
   `Admin/Controller/`, `templates/`).
4. When extending a core class, decide explicitly between a **class extension**
   (XFCP) and a **code event listener**, and register it in `_data/`.
5. After schema changes, update `Setup.php` with matching install **and**
   uninstall steps (and a versioned upgrade step if the add-on is already released).
