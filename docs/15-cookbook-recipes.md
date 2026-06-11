# Cookbook — Common Add-on Recipes

Task-oriented recipes. Each one lists the **files to touch** and the **code**.
They use the add-on id `Vendor/AddOn` (namespace `Vendor\AddOn`, table prefix
`xf_vendor_addon_`). Adapt names to your add-on. Deeper explanations are linked
per recipe.

> Convention reminders: short class names use a colon (`Vendor\AddOn:Item`),
> templates are referenced as `public:template_name` / `admin:template_name`, and
> any ACP-created data must be exported with `xf-dev:export` (see `docs/14`).

---

## Recipe 1 — A new public page

**Goal:** `index.php?vendor-addon` lists items from your table.

**Files:**
- `_data/routes.xml` (route, created in ACP → Development → Routes)
- `Pub/Controller/Item.php`
- `templates/public/vendor_addon_list.html`
- a phrase `vendor_addon_title`

```php title="Pub/Controller/Item.php"
<?php
namespace Vendor\AddOn\Pub\Controller;

use XF\Pub\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Item extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $page = $this->filterPage();
        $perPage = 20;

        $finder = $this->finder('Vendor\AddOn:Item')->order('created_date', 'DESC');

        $viewParams = [
            'items'   => $finder->limitByPage($page, $perPage)->fetch(),
            'total'   => $finder->total(),
            'page'    => $page,
            'perPage' => $perPage,
        ];
        return $this->view('Vendor\AddOn:Item\List', 'vendor_addon_list', $viewParams);
    }
}
```

```html title="templates/public/vendor_addon_list.html"
<xf:title>{{ phrase('vendor_addon_title') }}</xf:title>

<xf:foreach loop="$items" value="$item">
    <div class="block-row">
        <h3><a href="{{ link('vendor-addon/item', $item) }}">{$item.title}</a></h3>
        <span class="u-muted"><xf:date time="{$item.created_date}" /></span>
    </div>
<xf:else />
    <div class="blockMessage">{{ phrase('vendor_addon_no_items') }}</div>
</xf:foreach>

<xf:pagenav page="{$page}" perpage="{$perPage}" total="{$total}"
            link="vendor-addon" wrapperclass="block-outer-opposite" />
```

Route (ACP → Development → Routes): prefix `vendor-addon`, controller
`Vendor\AddOn:Item`. See `docs/04`.

---

## Recipe 2 — Add a DB-backed field to a core entity

**Goal:** add `vendor_points` to every user.

**Files:** `Setup.php`, `Listener.php`, `XF/Entity/User.php`, register
`entity_structure` listener in `_data/`.

```php title="Setup.php (install + uninstall)"
public function installStep1(): void
{
    $this->schemaManager()->alterTable('xf_user', function (\XF\Db\Schema\Alter $t) {
        $t->addColumn('vendor_points', 'int')->setDefault(0);
    });
}
public function uninstallStep1(): void
{
    $this->schemaManager()->alterTable('xf_user', function (\XF\Db\Schema\Alter $t) {
        $t->dropColumns(['vendor_points']);
    });
}
```

```php title="Listener.php — entity_structure, hint XF\Entity\User"
public static function userEntityStructure(
    \XF\Mvc\Entity\Manager $em, \XF\Mvc\Entity\Structure &$structure): void
{
    $structure->columns['vendor_points'] = ['type' => \XF\Mvc\Entity\Entity::UINT, 'default' => 0];
    $structure->getters['vendor_tier'] = true;
}
```

```php title="XF/Entity/User.php (class extension, getter body)"
<?php
namespace Vendor\AddOn\XF\Entity;

class User extends XFCP_User
{
    public function getVendorTier(): string
    {
        return $this->vendor_points >= 100 ? 'gold' : 'standard';
    }
}
```

Now `$user->vendor_points` and `$user->vendor_tier` work everywhere (PHP +
templates). Full pattern: `docs/12` (entity_structure) + `docs/11` (schema).

---

## Recipe 3 — Send an alert to a user

**Goal:** notify a user that their item was featured.

```php
/** @var \XF\Repository\UserAlert $alertRepo */
$alertRepo = \XF::repository('XF:UserAlert');

$alertRepo->alert(
    $user,                      // recipient entity
    \XF::visitor()->user_id,    // sender user id
    \XF::visitor()->username,   // sender username
    'user',                     // content type of the alert's content
    $user->user_id,             // content id
    'vendor_featured',          // action (maps to an alert phrase/template)
    ['item_title' => $item->title]  // extra params for the alert template
);
```

The alert template is `alert_<contenttype>_<action>` (e.g.
`alert_user_vendor_featured`) — create it as a template. See `docs/07` (handlers &
content types) for registering custom alert content types.

---

## Recipe 4 — Add a board option (ACP setting) and read it

**Goal:** an admin-configurable integer `vendorAddonMaxItems`.

**Files:** option + option group created in ACP → Setup → Options (exported to
`_data/options.xml` / `_data/option_groups.xml`), plus a phrase.

Read it anywhere:

```php
$max = \XF::options()->vendorAddonMaxItems;   // option_id is the property
```

In templates:

```html
<xf:if is="$xf.options.vendorAddonMaxItems">
    ... {$xf.options.vendorAddonMaxItems} ...
</xf:if>
```

For a choice/array option, the value is an array. See `docs/08`
(permissions/options/phrases) for option types and validation callbacks.

---

## Recipe 5 — Add and check a permission

**Goal:** gate an action behind a custom permission.

Create the permission in ACP → Development → Permission definitions (exported to
`_data/permissions.xml`), in a permission group (e.g. `vendorAddon`), id
`viewItems`. Check it:

```php
// In a controller action
if (!\XF::visitor()->hasPermission('vendorAddon', 'viewItems'))
{
    return $this->noPermission();
}
```

```html
<!-- In a template -->
<xf:if is="{$xf.visitor.hasPermission('vendorAddon', 'viewItems')}">
    ...
</xf:if>
```

Permission types: flag (yes/no), integer (e.g. max items, `-1` = unlimited).
See `docs/08`.

---

## Recipe 6 — Schedule a cron job

**Goal:** run cleanup nightly.

**Files:** `Cron/Cleanup.php`, cron entry in ACP → Setup → Cron entries
(exported to `_data/cron.xml`).

```php title="Cron/Cleanup.php"
<?php
namespace Vendor\AddOn\Cron;

class Cleanup
{
    public static function runCleanup(): void
    {
        $cutoff = \XF::$time - (30 * 86400);
        \XF::db()->delete('xf_vendor_addon_item', 'created_date < ?', $cutoff);
    }
}
```

Register the entry pointing at `Vendor\AddOn:Cleanup::runCleanup` with the schedule
(e.g. daily at 00:00). See `docs/06` (services, jobs, cron).

---

## Recipe 7 — Queue a background Job

**Goal:** do heavy work off the request (e.g. rebuild counters).

```php title="Job/Rebuild.php"
<?php
namespace Vendor\AddOn\Job;

use XF\Job\AbstractJob;

class Rebuild extends AbstractJob
{
    public function run($maxRunTime): \XF\Job\JobResult
    {
        $start = microtime(true);

        $items = \XF::finder('Vendor\AddOn:Item')
            ->where('item_id', '>', $this->data['lastId'] ?? 0)
            ->order('item_id')
            ->limit(100)
            ->fetch();

        if (!$items->count())
        {
            return $this->complete();
        }

        foreach ($items as $item)
        {
            // ... do work ...
            $this->data['lastId'] = $item->item_id;

            if (microtime(true) - $start >= $maxRunTime)
            {
                break;
            }
        }
        return $this->resume();
    }

    public function getStatusMessage(): string
    {
        return 'Rebuilding items...';
    }

    public function canCancel(): bool { return true; }
    public function canTriggerByChoice(): bool { return true; }
}
```

Enqueue it:

```php
\XF::app()->jobManager()->enqueueUnique('vendorAddonRebuild', 'Vendor\AddOn:Rebuild', [
    'lastId' => 0,
]);
```

See `docs/06`.

---

## Recipe 8 — React to content being saved

**Goal:** do something whenever a thread is created.

Register an `entity_post_save` listener **hinted to `XF\Entity\Thread`** (so it
doesn't fire for every entity):

```php title="Listener.php"
public static function threadPostSave(\XF\Mvc\Entity\Entity $entity): void
{
    // Only act on brand-new threads
    if (!$entity->isInsert())
    {
        return;
    }

    /** @var \XF\Entity\Thread $entity */
    if ($entity->Forum && $entity->Forum->vendor_addon_auto_feature)
    {
        \XF::repository('Vendor\AddOn:Item')->featureThread($entity);
    }
}
```

`isInsert()` distinguishes create from update; `isChanged('col')` checks a specific
field. See `docs/12` (events) — **always hint entity listeners.**

---

## Recipe 9 — Add a tab/link to a core page

**Goal:** show a "Portal" link in the main navigation.

Two clean options:

1. **Navigation entry (no code):** ACP → Appearance → Navigation → add an entry
   under the top navigation, linking to your route. Exported to `_data/navigation.xml`.
   Best for a simple tab.

2. **Template modification:** ACP → Appearance → Template modifications, target a
   core template (e.g. `thread_view`) with a find/replace to insert your block.
   Exported to `_data/template_modifications.xml`. Best for injecting markup into
   an existing page. See `docs/12`.

Prefer these over class-extending a controller just to add a link.

---

## Recipe 10 — Add an ACP management page (CRUD)

**Goal:** admins manage your items in the ACP.

**Files:** `Admin/Controller/Item.php`, admin route prefix `items` under your
add-on, admin templates, admin navigation entry.

```php title="Admin/Controller/Item.php"
<?php
namespace Vendor\AddOn\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Item extends AbstractController
{
    public function actionIndex()
    {
        $items = $this->finder('Vendor\AddOn:Item')->order('title')->fetch();
        return $this->view('Vendor\AddOn:Item\Listing', 'vendor_addon_item_list', [
            'items' => $items,
        ]);
    }

    public function actionEdit(ParameterBag $params)
    {
        $item = $this->assertItemExists($params->item_id);
        return $this->itemEditView($item);
    }

    public function actionSave(ParameterBag $params)
    {
        $this->assertPostOnly();
        $item = $params->item_id
            ? $this->assertItemExists($params->item_id)
            : $this->em()->create('Vendor\AddOn:Item');

        $input = $this->filter([
            'title'     => 'str',
            'is_active' => 'bool',
        ]);
        $item->bulkSet($input);
        $item->save();

        return $this->redirect($this->buildLink('items'));
    }

    protected function itemEditView(\Vendor\AddOn\Entity\Item $item)
    {
        return $this->view('Vendor\AddOn:Item\Edit', 'vendor_addon_item_edit', ['item' => $item]);
    }

    protected function assertItemExists($id, $with = null, $phraseKey = null)
    {
        return $this->assertRecordExists('Vendor\AddOn:Item', $id, $with, $phraseKey);
    }
}
```

Add an admin route (prefix `items`, section), an admin navigation entry, and the
two admin templates. Full ACP CRUD walkthrough: `docs/04` and `xenforo.md`.

---

## Recipe 11 — Read input safely in a controller

```php
// Single value with a filter type
$page  = $this->filter('page', 'uint');
$query = $this->filter('q', 'str');

// Multiple at once
$input = $this->filter([
    'title'     => 'str',
    'tags'      => 'array-str',
    'node_id'   => 'uint',
    'is_active' => 'bool',
    'price'     => 'float',
]);

// File uploads
$upload = $this->request->getFile('upload_field');
```

Common filter types: `str`, `uint`, `int`, `bool`, `float`, `array-str`,
`array-uint`, `datetime`, `json-array`. **Never** read `$_GET`/`$_POST` directly —
always filter. See `docs/04` and `cheatsheets/php-api.md`.

---

## Recipe 12 — Link & URL building

```php
// In PHP (controllers/services)
$url = $this->buildLink('vendor-addon/item', $item, ['page' => 2]);
$url = \XF::app()->router('public')->buildLink('vendor-addon/item', $item);
```

```html
<!-- In templates -->
<a href="{{ link('vendor-addon/item', $item) }}">{$item.title}</a>
<a href="{{ link('vendor-addon/item/edit', $item) }}">Edit</a>
```

The route's `build_link` setting controls how the entity is turned into a slug
(e.g. `data_id` → `vendor-addon/item.5`). See `docs/04`.

---

**See also:** `docs/03` (entities/finders/repositories — the data layer every
recipe builds on), `cheatsheets/php-api.md` (one-line method lookups), and
`examples/demo-portal/` (a complete working add-on tying these together).
