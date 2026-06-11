# Dev Tools, Building & Releasing

This covers the add-on lifecycle from "edit in dev mode" to "ship a ZIP":
`addon.json`, the version-id scheme, the `_output` vs `_data` two-tier system, the
CLI commands, `build.json`, and `hashes.json`.

> Prerequisite: development mode must be on (`docs/01`). It auto-writes
> `_output/` files and unlocks the ACP Development menu.

---

## The two-tier data system (`_output` vs `_data`)

XenForo stores your add-on's non-code data (routes, options, permissions, phrases,
templates, listeners, class extensions, widgets, …) in the **database**. To get it
into your add-on directory there are two representations:

| Directory | Format | Written by | Purpose |
|---|---|---|---|
| `_output/` | many small files (one per item) | `xf-dev:export` | **development** — diff-friendly, filesystem template editing, VCS |
| `_data/` | bundled `*.xml` master data | `xf-addon:export` (and `build-release`) | **shipping** — what installs on the user's board |

Both describe the same data. Day-to-day you work against `_output` (`xf-dev:export`
/ `xf-dev:import`); at release time `_data` is regenerated from the database.

```
ACP edit (dev mode)  ──auto──▶  _output/      ──xf-dev:import──▶  database
database  ──xf-dev:export──▶  _output/   (round-trip for VCS)
database  ──xf-addon:export──▶  _data/*.xml   (release master data)
```

> **Always export before a rebuild/upgrade.** A rebuild reads master data and can
> overwrite DB state. If you changed things in the ACP but didn't export, those
> changes can be lost. Keep the add-on under git so mistakes are recoverable.

---

## `addon.json` — full field reference

Lives at `src/addons/Vendor/AddOn/addon.json`.

```json
{
    "legacy_addon_id": "",
    "title": "Vendor AddOn",
    "description": "Adds a portal and featured threads.",
    "version_id": 1020370,
    "version_string": "1.2.3",
    "dev": "Vendor",
    "dev_url": "https://example.com",
    "faq_url": "",
    "support_url": "https://example.com/support",
    "extra_urls": [],
    "require": {
        "php": ["7.2.0", "PHP 7.2.0+"],
        "XF": [2030000, "XenForo 2.3.0+"]
    },
    "require_any": {},
    "icon": "fa-newspaper",
    "active": true
}
```

| Field | Required | Notes |
|---|---|---|
| `title` | yes | Display name |
| `version_id` | yes | Integer; see scheme below. Drives upgrades. |
| `version_string` | yes | Human version, e.g. `1.2.3` |
| `dev` | recommended | Author/vendor name |
| `description` | recommended | Shown in ACP add-on list |
| `require` | optional | Hard dependencies (PHP, XF, other add-ons) |
| `require_any` | optional | At least one of several alternatives |
| `icon` | optional | FontAwesome class or path to image |
| `dev_url`, `faq_url`, `support_url`, `extra_urls` | optional | Links |
| `legacy_addon_id` | optional | For XF1→XF2 migration mapping |

Dependency entries are `[machineValue, "Human readable"]`. Example XF requirement
`"XF": [2030000, "XenForo 2.3.0+"]`; an add-on requirement
`"SV/StandardLib": [1000000, "Standard Library 1.0.0+"]`.

> Edit `addon.json` by hand? Then run `php cmd.php xf-addon:sync-json Vendor/AddOn`
> so the database matches and the system doesn't think a destructive upgrade is
> pending. Validate with `xf-addon:validate-json`.

---

## Version-id scheme

`version_id` is an integer that must **increase** every release; it's how XenForo
decides whether an upgrade is available and which `upgrade<id>Step*` methods to run.

Formula: `major × 1_000_000 + minor × 10_000 + patch × 100 + state`

| version_string | state meaning | version_id |
|---|---|---|
| 1.0.0 Alpha | 10 = Alpha | `1000010` |
| 1.0.0 Beta 3 | 30s = Beta | `1000033` |
| 1.0.0 RC | 50s = Release Candidate | `1000050` |
| 1.0.0 (Stable) | 70 = Stable | `1000070` |
| 1.2.3 | 70 = Stable | `1020370` |
| 2.0.0 | Stable | `2000070` |

State digits: **10s Alpha, 30s Beta, 50s Release Candidate, 70 Stable** (use the
trailing digit for iterations, e.g. Beta 3 → `33`). Bump in one step:

```bash
php cmd.php xf-addon:bump-version Vendor/AddOn --version-id 1020370 --version-string "1.2.3"
```

If you pass only `--version-id` and it follows the scheme, XenForo infers the
version string automatically.

---

## CLI command reference

All commands are run from the XenForo root: `php cmd.php <command>`.

### Add-on lifecycle
```bash
xf-addon:create                              # interactive new add-on + addon.json (+ optional Setup.php)
xf-addon:export Vendor/AddOn                 # DB → _data/*.xml (release master data)
xf-addon:bump-version Vendor/AddOn --version-id 1020370 --version-string "1.2.3"
xf-addon:sync-json Vendor/AddOn              # import hand-edited addon.json safely
xf-addon:validate-json Vendor/AddOn          # check addon.json is well-formed/complete
xf-addon:build-release Vendor/AddOn          # export + zip → _releases/<id>-<version>.zip
```

### Setup step runners (need StepRunner traits)
```bash
xf-addon:install-step   Vendor/AddOn 1
xf-addon:upgrade-step   Vendor/AddOn 1000170 1
xf-addon:uninstall-step Vendor/AddOn 1
```

### Install / upgrade / rebuild / uninstall (DB operations)
```bash
xf:addon-install   Vendor/AddOn
xf:addon-upgrade   Vendor/AddOn
xf:addon-rebuild   Vendor/AddOn
xf:addon-uninstall Vendor/AddOn
```

### Development output (dev mode only)
```bash
xf-dev:export --addon Vendor/AddOn           # DB → _output/
xf-dev:import --addon Vendor/AddOn           # _output/ → DB
```

### Other useful ones
```bash
xf:rebuild-master-data                       # rebuild core caches
xf:file-check Vendor/AddOn                   # verify file hashes
xf-dev:entity-class-properties --addon Vendor/AddOn   # regenerate entity @property doc hints
```

> `build-release` automatically runs `xf-addon:export` first, so your `_data/` is
> always current in the shipped ZIP.

---

## `build.json` — customizing the build

Optional, at the add-on root. Runs during `xf-addon:build-release`.

```json
{
    "additional_files": [
        "js/vendor/addon"
    ],
    "minify": [
        "js/vendor/addon/a.js",
        "js/vendor/addon/b.js"
    ],
    "rollup": {
        "js/vendor/addon/ab-rollup.js": [
            "js/vendor/addon/a.min.js",
            "js/vendor/addon/b.min.js"
        ]
    },
    "exec": [
        "echo '{title} {version_string} ({version_id}) built' > 'src/addons/Vendor/AddOn/_build/built.txt'"
    ]
}
```

- **`additional_files`** — copy files/dirs that live outside the add-on directory
  (e.g. front-end JS served from web root) into the build. During dev you can keep
  them in the add-on's `_files/` directory; the builder checks there first.
- **`minify`** — list of JS files, or `"*"` to minify everything under `js/`.
  Minified files get a `.min.js` suffix; originals are kept.
- **`rollup`** — concatenate several JS files into one (key = output file).
- **`exec`** — arbitrary shell commands run just before packaging. Placeholders
  like `{title}`, `{version_string}`, `{version_id}` are filled from `addon.json`.

---

## What the release ZIP contains

`xf-addon:build-release` collects files into a temporary `_build/` directory, then
writes `_releases/<ADDON_ID>-<VERSION_STRING>.zip`. The ZIP includes:

- Your PHP under `src/addons/Vendor/AddOn/...`
- `_data/*.xml` master data
- `addon.json`
- **`hashes.json`** — a generated map of file → hash used by `xf:file-check` to
  detect tampering/corruption on the user's server.

It does **not** ship `_output/`, `_build/`, `_releases/`, or anything excluded by
your build config.

---

## Git workflow for add-ons

Recommended `.gitignore` inside the add-on:

```gitignore
_build/
_releases/
```

**Commit:** `src/addons/Vendor/AddOn/` source, `addon.json`, `build.json`,
`_data/`, and `_output/` (both representations help reviewers and let
collaborators import without a database). **Ignore:** `_build/` and `_releases/`
(generated). Tag releases to match `version_string`.

Typical release flow:

```bash
# 1. make changes in ACP (dev mode) + PHP
php cmd.php xf-dev:export --addon Vendor/AddOn     # capture ACP changes to _output
git add -A && git commit -m "feat: add featured threads"

# 2. bump version
php cmd.php xf-addon:bump-version Vendor/AddOn --version-id 1010070 --version-string "1.1.0"

# 3. build
php cmd.php xf-addon:build-release Vendor/AddOn    # → _releases/Vendor-AddOn-1.1.0.zip

# 4. tag + upload the ZIP to your distribution channel
git tag v1.1.0 && git push --tags
```

---

## Debugging utilities

```php
\XF::dump($var);        // interactive Symfony VarDumper (collapsible HTML)
\XF::dumpSimple($var);  // plain var_dump-style, wrapped in <pre>

// Inspect the SQL a finder will run, without executing it:
\XF::dumpSimple($finder->getQuery());
```

In debug mode, the footer bar shows execution time, query count, and memory;
hovering reveals controller/action/template, and clicking lists every query with
stack traces.

---

## Checklist before releasing

- [ ] `version_id` increased and matches `version_string` (run `bump-version`).
- [ ] `xf-addon:validate-json` passes.
- [ ] `xf-dev:export` run so `_output`/DB are in sync; changes committed.
- [ ] Install **and** uninstall tested on a clean board (`Setup.php` reversible).
- [ ] Upgrade path tested from the previous released `version_id`.
- [ ] `require` constraints (PHP, XF) are correct.
- [ ] `build-release` produces a ZIP with `hashes.json` and no dev cruft.

**See also:** `docs/02` (add-on structure), `docs/11` (Setup.php steps & version
gating), `docs/01` (dev/debug mode setup).
