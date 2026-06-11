# Schema & Migrations (Setup.php)

Every add-on that stores data ships a `Setup.php` class. It runs schema changes
and data seeding during **install**, **upgrade**, and **uninstall**. This is the
XenForo equivalent of database migrations.

> **Golden rule:** every install/upgrade step that *adds* something must have an
> uninstall step that *removes* it. If you create a table on install, drop it on
> uninstall. If you add a column, drop it on uninstall.

---

## The Setup class skeleton

`Setup.php` lives at the add-on root (`src/addons/Vendor/AddOn/Setup.php`),
extends `AbstractSetup`, and uses the three step-runner traits. The traits let you
write numbered `installStepN()`, `upgrade<versionId>StepN()`, and
`uninstallStepN()` methods that XenForo discovers and runs in order.

```php
<?php

namespace Vendor\AddOn;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1(): void
    {
        $this->schemaManager()->createTable('xf_vendor_addon_item', function (Create $table) {
            $table->addColumn('item_id', 'int')->autoIncrement();
            $table->addColumn('user_id', 'int')->setDefault(0);
            $table->addColumn('title', 'varchar', 150);
            $table->addColumn('message', 'text');
            $table->addColumn('created_date', 'int')->setDefault(0);
            $table->addColumn('is_active', 'tinyint', 3)->setDefault(1);
            $table->addKey('user_id');
        });
    }

    public function uninstallStep1(): void
    {
        $this->schemaManager()->dropTable('xf_vendor_addon_item');
    }
}
```

- `installStep1`, `installStep2`, … run sequentially on first install.
- `schemaManager()` is a shortcut available inside `AbstractSetup`. Outside Setup,
  use `\XF::db()->getSchemaManager()`.
- Steps should be **small and idempotent-friendly** — if a later step fails the
  user can re-run from that step.

---

## Creating tables — `\XF\Db\Schema\Create`

```php
$this->schemaManager()->createTable('xf_vendor_addon_item', function (Create $table) {
    $table->addColumn('item_id', 'int')->autoIncrement();      // becomes PRIMARY KEY automatically
    $table->addColumn('user_id', 'int')->setDefault(0);
    $table->addColumn('node_id', 'int')->setDefault(0);
    $table->addColumn('title', 'varchar', 150);                // length as 3rd arg
    $table->addColumn('message', 'mediumtext');
    $table->addColumn('views', 'int')->setDefault(0);
    $table->addColumn('is_active', 'tinyint', 3)->setDefault(1);
    $table->addColumn('extra_data', 'blob')->nullable();

    $table->addKey('user_id');
    $table->addKey(['node_id', 'is_active'], 'node_active');    // compound, named index
    $table->addUniqueKey('title', 'unique_title');
});
```

Automatic behavior you can rely on (and override):

| Default applied | Override |
|---|---|
| Integers are **UNSIGNED** | `->unsigned(false)` to allow negatives |
| Columns are **NOT NULL** | `->nullable()` / `->nullable(true)` |
| Auto-increment column becomes the **primary key** | `$table->addPrimaryKey('col')` to set explicitly |
| `int` length defaults to 10 | pass a length: `addColumn('x', 'int', 5)` |
| Storage engine **InnoDB** | `$table->engine('MyISAM')` (rarely needed) |

> **You MUST set a default** on every column (`->setDefault(...)`) or make it
> nullable. Querying a column with no default and no value throws errors. The only
> exception is the auto-increment primary key.

### Common column types

| Type | Use for | Notes |
|---|---|---|
| `int` | IDs, timestamps, counts | unsigned by default |
| `tinyint` | booleans / small enums | `tinyint(3)` + `setDefault(0/1)` for bool |
| `smallint`, `mediumint`, `bigint` | sized integers | |
| `varchar` | short strings | 2nd-ish arg is length: `addColumn('x','varchar',255)` |
| `char` | fixed-length codes | |
| `text`, `mediumtext`, `longtext` | long content | cannot have a literal default |
| `blob`, `mediumblob` | binary / serialized | |
| `decimal` | money / precise numbers | `->scale(2)->precision(10)` |
| `enum` | fixed value set | `addColumn('state','enum',['a','b'])->setDefault('a')` |
| `float`, `double` | approximate numbers | |

> For `text`/`blob` columns, MySQL does not allow a literal `DEFAULT`. XenForo
> handles this — don't call `setDefault()` on them; the Entity layer supplies `''`.

---

## Altering existing tables — `\XF\Db\Schema\Alter`

Use this both to modify **your own** tables in an upgrade and to add columns to
**core** XenForo tables (e.g. add a field to `xf_user` or `xf_forum`).

```php
$this->schemaManager()->alterTable('xf_forum', function (Alter $table) {
    $table->addColumn('vendor_addon_auto_feature', 'tinyint', 3)->setDefault(0);
});
```

When **changing** an existing column you only specify what changes — the rest of
the definition is retained automatically:

```php
$this->schemaManager()->alterTable('xf_vendor_addon_item', function (Alter $table) {
    $table->changeColumn('title')->length(250);             // only the length changes
    $table->changeColumn('old_name')->renameTo('new_name'); // rename
    $table->addColumn('reaction_score', 'int')->setDefault(0);
    $table->dropColumns(['legacy_flag']);                   // remove columns
    $table->addKey('created_date');
});
```

Useful `Alter` methods: `addColumn`, `changeColumn`, `renameColumn` /
`changeColumn(...)->renameTo(...)`, `dropColumns([...])`, `addKey`,
`addUniqueKey`, `dropIndexes([...])`, `addPrimaryKey`, `dropPrimaryKey`.

---

## Upgrade steps (for already-released add-ons)

When your add-on is already installed on someone's board and you ship a new
version, **install steps do not re-run**. You add upgrade steps keyed by the
**previous** `version_id` you are upgrading *from*. The method name is
`upgrade<versionId>Step<N>`.

```php
// addon.json went from version_id 1000170 -> 1000270.
// This runs for anyone upgrading from <= 1000170.
public function upgrade1000170Step1(): void
{
    $this->schemaManager()->alterTable('xf_vendor_addon_item', function (Alter $table) {
        $table->addColumn('reaction_score', 'int')->setDefault(0);
    });
}

public function upgrade1000170Step2(): void
{
    // backfill / data migration after the schema change
    $this->db()->query("
        UPDATE xf_vendor_addon_item
        SET reaction_score = 0
        WHERE reaction_score IS NULL
    ");
}
```

> `version_id` is the integer in `addon.json`. The conventional scheme is
> `aabbbccs` → `1.2.3` ≈ `1020370` (see `docs/14-build-release-devtools.md`).
> Always add the **new** column on install too, so fresh installs match upgraded
> ones — keep `installStep*` and the cumulative upgrade steps consistent.

---

## Uninstall steps

Reverse everything, in a safe order (drop child/foreign data before parents).

```php
public function uninstallStep1(): void
{
    $this->schemaManager()->dropTable('xf_vendor_addon_item');
}

public function uninstallStep2(): void
{
    $this->schemaManager()->alterTable('xf_forum', function (Alter $table) {
        $table->dropColumns(['vendor_addon_auto_feature']);
    });
}
```

XenForo automatically removes master data exported to `_data/` (options,
permissions, phrases, routes, listeners, templates, etc.) on uninstall — you only
need to reverse **schema** and any **denormalized data you wrote to core tables**.

---

## Seeding and migrating data

Inside any step you have `$this->db()` (the adapter) and `$this->app` available, so
you can seed rows or run data migrations. Prefer the entity layer for anything
that has an entity (it fires behaviors); use raw SQL for bulk backfills.

```php
public function installStep3(): void
{
    // Seed a default row via the entity manager (fires entity behaviors)
    $item = \XF::em()->create('Vendor\AddOn:Item');
    $item->title = 'Welcome';
    $item->user_id = 1;
    $item->created_date = \XF::$time;
    $item->save();
}

public function installStep4(): void
{
    // Bulk insert via the adapter (fast, no behaviors) — always parameterize
    $this->db()->insert('xf_vendor_addon_item', [
        'title'        => 'Seeded',
        'user_id'      => 0,
        'created_date' => \XF::$time,
        'is_active'    => 1,
    ]);
}
```

Adapter helpers: `insert($table, $data)`, `update($table, $data, $where, $params)`,
`delete($table, $where, $params)`, plus the fetch methods (`fetchRow`, `fetchOne`,
`fetchAll`, `fetchAllKeyed`, `fetchAllColumn`, `fetchPairs`). **Always** use `?`
placeholders for user-derived values to avoid SQL injection.

---

## Conditional / defensive steps

Tables and columns may already exist (re-run after a failure, or a column another
add-on added). Guard with the schema manager's existence checks.

```php
public function installStep5(): void
{
    $sm = $this->schemaManager();

    if (!$sm->tableExists('xf_vendor_addon_item'))
    {
        $sm->createTable('xf_vendor_addon_item', function (Create $table) {
            $table->addColumn('item_id', 'int')->autoIncrement();
        });
    }

    // Only add the column if it isn't there yet
    $columns = $sm->getColumnDefinition('xf_vendor_addon_item', 'priority');
    if (!$columns)
    {
        $sm->alterTable('xf_vendor_addon_item', function (Alter $table) {
            $table->addColumn('priority', 'int')->setDefault(0);
        });
    }
}
```

Useful checks: `tableExists($name)`, `getColumnDefinition($table, $column)`,
`getTableColumns($table)`, `getIndexDefinition($table, $index)`.

---

## `postInstall`, `postUpgrade`, `postRebuild`

Beyond numbered steps, `AbstractSetup` lets you override hooks that run once after
the stepped phase completes. Use them to rebuild caches.

```php
public function postInstall(array &$stateChanges): void
{
    \XF::repository('XF:Style')->updateAllStylesLastModifiedDateLater();
}

public function postUpgrade($previousVersion, array &$stateChanges): void
{
    if ($previousVersion < 1000270)
    {
        // one-time cache rebuild after a structural change
        $this->app->jobManager()->enqueueUnique(
            'vendorAddonRebuild',
            'Vendor\AddOn:Rebuild',
            []
        );
    }
}
```

---

## Workflow

```bash
# After editing Setup.php, (re)run a specific install step in dev:
php cmd.php xf-addon:install-step Vendor/AddOn 1

# Run an upgrade step:
php cmd.php xf-addon:upgrade-step Vendor/AddOn 1000170 1

# Full uninstall (runs your uninstallStep*):
php cmd.php xf-addon:uninstall Vendor/AddOn
```

> Test your **uninstall** path as carefully as install. The most common add-on
> bug is leaving orphan columns on `xf_user`/`xf_forum` after uninstall because an
> `uninstallStep` was forgotten.

---

## Checklist

- [ ] Every new table has a matching `dropTable` in uninstall.
- [ ] Every column added to a core table is dropped in uninstall.
- [ ] Every non-text column has `setDefault(...)` (or `nullable()`).
- [ ] Fresh-install steps and cumulative upgrade steps produce the **same** schema.
- [ ] Bulk SQL uses `?` placeholders (no string interpolation of user data).
- [ ] Entity short name in `getStructure()` matches the table you create here.

**See also:** `docs/03-entities-finders-repositories.md` (the Entity that wraps
these rows), `docs/14-build-release-devtools.md` (version_id scheme & building).
