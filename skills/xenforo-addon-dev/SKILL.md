---
name: xenforo-addon-dev
description: >-
  Authoritative reference for building XenForo 2.3+ add-ons in PHP. Use this
  whenever the task involves XenForo: writing or editing add-on code, entities,
  finders, repositories, controllers, routes, templates (.html / <xf:> tags),
  services, jobs, cron, code event listeners, class extensions (XFCP), handlers,
  content types, permissions, options, phrases, criteria, widgets, styles and
  Less/CSS templates, style properties, template modifications, navigation,
  Setup.php schema migrations, addon.json, the _data XML files,
  building/releasing an add-on, or the XenForo REST API. Activate on mentions of
  XenForo, XF2, XFCP, cmd.php, xf-addon, xf-dev, xf-designer, or files under
  src/addons/.
license: MIT
metadata:
  version: 1.2.0
  targets: XenForo 2.3.x
---

# XenForo 2.3+ Add-on Development

You are working with **XenForo 2.x** (a PHP forum platform). This skill gives you
an accurate, version-correct reference so you do **not** hallucinate APIs.
XenForo 2.x is **completely incompatible** with XenForo 1.x — ignore any XF1
patterns (`library/`, `DataWriter`, `_Model` suffixes, Zend-style classes). If
example code uses `\XF::`, `XFCP_`, `getStructure()`, or `<xf:...>` tags, it is XF2.

## How to use this reference

The full reference lives next to this skill. The **reference root** is the
directory that contains `xenforo.md` (when installed as a plugin it is
`${CLAUDE_PLUGIN_ROOT}`; in the source repo it is the repo root).

**Workflow — always do this before writing XenForo code:**

1. Identify the subsystem the task touches (see the index below).
2. **Read the matching `docs/NN-*.md` file in full** before implementing — these
   contain working, fact-checked code you should adapt rather than invent.
3. Keep the matching cheatsheet open for exact method/column names.
4. For anything not covered in `docs/`, fall back to `xenforo.md` (the 4,000-line
   single-file mega-reference) and search it.
5. Mirror the structure of `examples/demo-portal/` for a complete, real add-on.

## Index — task → file to read

| If the task involves… | Read |
|---|---|
| Dev environment, config.php, debug/dev mode, CLI | `docs/01-setup-environment.md` |
| addon.json, directory layout, `xf-addon:create`, namespaces | `docs/02-addon-structure.md` |
| Database rows, `getStructure()`, Finders, Repositories | `docs/03-entities-finders-repositories.md` |
| Controllers, actions, `actionX()`, routing, reply types | `docs/04-controllers-routing.md` |
| Templates, `<xf:...>` tags, template syntax, forms | `docs/05-templates.md` |
| Services, Jobs (queue), Cron entries | `docs/06-services-jobs-cron.md` |
| Handlers, content types, alerts/reports/approval queue | `docs/07-handlers-content-types.md` |
| Permissions, options, phrases | `docs/08-permissions-options-phrases.md` |
| User/content criteria | `docs/09-criteria-system.md` |
| REST API endpoints, API keys, webhooks | `docs/10-rest-api.md` |
| Setup.php, create/alter tables, upgrade/uninstall steps | `docs/11-schema-migrations.md` |
| Code event listeners vs class extensions (XFCP), events | `docs/12-events-listeners-extensions.md` |
| Widgets, widget positions, WidgetRenderer | `docs/13-widgets.md` |
| `xf-dev:export`, `xf-addon:build-release`, hashes, shipping | `docs/14-build-release-devtools.md` |
| "How do I…" concrete tasks (tabs, alerts, columns, nav) | `docs/15-cookbook-recipes.md` |
| Styles, Less/CSS templates, inheritance, Designer mode | `docs/16-styles-less-designer-mode.md` |
| Style properties (`@xf-`), template modifications | `docs/17-style-properties-template-modifications.md` |
| Public navigation, nav types, display order | `docs/18-navigation-display-order.md` |
| Quick PHP API lookup (`\XF::`, finder/repo/service methods) | `cheatsheets/php-api.md` |
| Quick template-tag lookup | `cheatsheets/template-tags.md` |
| Entity column types and options | `cheatsheets/entity-column-types.md` |

## Golden rules (apply even before reading)

- **The `\XF` facade is your entry point:** `\XF::app()`, `\XF::db()`, `\XF::em()`,
  `\XF::finder('Vendor\AddOn:Thing')`, `\XF::repository(...)`, `\XF::service(...)`,
  `\XF::visitor()` (guest = `user_id` 0), `\XF::phrase(...)`, `\XF::options()`.
- **Short class names** use a colon: `Vendor\AddOn:Thing` maps to
  `Vendor\AddOn\Entity\Thing` (or `Repository\`, `Finder\`, etc. by context).
- **Never `new` an XF class directly** when it may be extended — use the factory
  (`\XF::service()`, `\XF::app()->...`) or `\XF::extendClass()`.
- **Extend core code two ways:** *class extension* (XFCP, subclass behavior) or
  *code event listener* (hook into named events). Pick per `docs/12`.
- **Persisted data is two-layer:** PHP `Entity`/`Repository`/`Finder` for runtime,
  plus exported `_data/*.xml` master data (routes, options, permissions, phrases,
  templates, listeners, class extensions) that ships in the add-on.
- **Never edit `_output/`** by hand and never ship it — it is dev-mode export.
- **Build before shipping:** `php cmd.php xf-addon:build-release Vendor/AddOn`.
- Target **XF 2.3.x**, PHP 7.2+ (8.1+ recommended). If unsure of an exact method
  or column option, look it up in the file above instead of guessing.

## Minimal mental model

```
Request → Route (_data/routes.xml) → Controller action (Pub/ or Admin/Controller)
        → Repository/Finder builds query → Entity rows
        → $this->view('Vendor\AddOn:X', 'template_name', $params)
        → Template (.html, <xf:...>) renders → Reply
Background: Service (business logic) · Job (queue) · Cron (scheduled)
Extension:  Class extension (XFCP) · Code event listener
Install:    Setup.php (schema) + _data/*.xml (master data)
```

When in doubt, open `examples/demo-portal/` — it is a complete, working add-on that
demonstrates the entity → repository → controller → template → _data flow end to end.
