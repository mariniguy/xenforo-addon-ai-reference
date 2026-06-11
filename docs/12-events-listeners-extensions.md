# Events, Code Event Listeners & Class Extensions

XenForo lets you change core behavior **without editing core files** in two ways:

1. **Class extensions (XFCP)** — subclass a core class to override or add methods.
   Use when you want to change *how an object behaves*.
2. **Code event listeners** — register a callback that fires on a named event.
   Use when you want to *hook into a moment* in the request lifecycle.

> Rule of thumb: if you'd reach for "override this method," use a **class
> extension**. If you'd reach for "run my code when X happens," use a **code event
> listener**. Many add-ons use both.

---

## Part 1 — Class extensions (XFCP)

XFCP = "XenForo Class Proxy." It builds an inheritance chain so multiple add-ons
can each extend the same core class safely.

### The pattern

To extend `XF\Pub\Controller\Forum`, create a class that extends the magic
`XFCP_Forum` proxy (not the real class):

```php title="src/addons/Vendor/AddOn/XF/Pub/Controller/Forum.php"
<?php

namespace Vendor\AddOn\XF\Pub\Controller;

class Forum extends XFCP_Forum
{
    public function actionIndex(\XF\Mvc\ParameterBag $params)
    {
        $reply = parent::actionIndex($params);   // ALWAYS call parent first

        if ($reply instanceof \XF\Mvc\Reply\View)
        {
            $reply->setParam('vendorAddonExtra', 'hello');
        }

        return $reply;
    }
}
```

Key rules:

- The class name mirrors the path: `Vendor\AddOn\XF\Pub\Controller\Forum`.
  Convention is to mirror the core path under an `XF/` directory in your add-on.
- Extend `XFCP_<ShortName>` — a proxy that doesn't physically exist; XenForo
  generates it at runtime. Your IDE learns the real parent from
  `_output/extension_hint.php`.
- **Almost always call `parent::method(...)`** when overriding, and modify its
  return value rather than re-implementing. This keeps other add-ons' extensions
  in the chain working.

### Registering the extension

Add it in **Admin CP → Development → Class extensions → Add class extension**
(base class → extended class). In dev mode this exports to
`_data/class_extensions.xml`:

```xml title="_data/class_extensions.xml"
<?xml version="1.0" encoding="UTF-8"?>
<class_extensions>
    <extension from_class="XF\Pub\Controller\Forum"
               to_class="Vendor\AddOn\XF\Pub\Controller\Forum"
               addon_id="Vendor/AddOn" />
</class_extensions>
```

### What you can extend this way

| Extend… | Typical reason | Override examples |
|---|---|---|
| `XF\Pub\Controller\*` | add/modify public actions | `actionIndex`, `actionX` |
| `XF\Admin\Controller\*` | add/modify ACP actions, save extra fields | `actionEdit`, `saveProcess` |
| `XF\Entity\*` | add getters, change behavior, verify columns | `getValueX`, `_preSave` |
| `XF\Repository\*` | add query helpers, alter finders | `findXForList` |
| `XF\Service\*\*` | change creation/edit side effects | `_save`, `setX` |
| `XF\Pub\View\*` | change response rendering | `renderX` |

> You can override **any public or protected method**. To *add* a column-backed
> field or relation to a core entity, you usually combine a **schema alter**
> (`docs/11`) with the **`entity_structure` event** (below) — not a class
> extension.

### Extending an entity (getters & verification)

```php title="src/addons/Vendor/AddOn/XF/Entity/User.php"
<?php

namespace Vendor\AddOn\XF\Entity;

class User extends XFCP_User
{
    // Computed/pseudo getter — usable as $user->vendor_badge in PHP and templates
    public function getVendorBadge(): string
    {
        return $this->vendor_points >= 100 ? 'gold' : 'standard';
    }

    protected function _preSave()
    {
        parent::_preSave();

        if ($this->isChanged('vendor_points') && $this->vendor_points < 0)
        {
            $this->error(\XF::phrase('vendor_addon_points_cannot_be_negative'));
        }
    }
}
```

For a getter to be addressable as `$entity->vendor_badge`, also expose it via the
`entity_structure` event (add it to `$structure->getters`).

---

## Part 2 — Code event listeners

A listener binds one of your static methods to a named event. You create one
listener class (often `Listener.php` at the add-on root) and register each binding
in **Admin CP → Development → Code event listeners**.

### The listener class

```php title="src/addons/Vendor/AddOn/Listener.php"
<?php

namespace Vendor\AddOn;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Manager;
use XF\Mvc\Entity\Structure;

class Listener
{
    public static function entityStructure(Manager $em, Structure &$structure): void
    {
        // (see worked example below)
    }

    public static function threadPostSave(Entity $entity): void
    {
        // runs after any XF\Entity\Thread is saved (because of the hint)
    }
}
```

### Registering a listener

`_data/code_event_listeners.xml` (exported from the ACP). Note the optional
**`hint`** — for entity/content events it restricts the callback to one class so
your code doesn't run for every entity in the system:

```xml title="_data/code_event_listeners.xml"
<?xml version="1.0" encoding="UTF-8"?>
<code_event_listeners>
    <listener event_id="entity_post_save"
              callback_class="Vendor\AddOn\Listener"
              callback_method="threadPostSave"
              active="1"
              hint="XF\Entity\Thread"
              addon_id="Vendor/AddOn" />
</code_event_listeners>
```

> **The ACP "Add code event listener" page generates the exact callback stub
> (argument list and order) for the event you pick.** Treat that as the source of
> truth — event signatures occasionally differ between minor versions. The table
> below covers the most-used, stable events.

### The most useful events

| Event ID | Fires when… | Callback arguments (confident) |
|---|---|---|
| `app_setup` | container is ready, before dispatch (any app) | `(\XF\App $app)` |
| `app_pub_start` | a **public** request starts | `(\XF\Pub\App $app)` |
| `app_admin_start` | an **admin** request starts | `(\XF\Admin\App $app)` |
| `app_api_start` | an **API** request starts | `(\XF\Api\App $app)` |
| `entity_structure` | an entity's structure is built | `(Manager $em, Structure &$structure)` |
| `entity_pre_save` | before an entity saves | `(Entity $entity)` |
| `entity_post_save` | after an entity saves | `(Entity $entity)` |
| `entity_pre_delete` | before an entity deletes | `(Entity $entity)` |
| `entity_post_delete` | after an entity deletes | `(Entity $entity)` |
| `controller_pre_dispatch` | before a controller action runs | `(\XF\Mvc\Controller $c, $action, \XF\Mvc\ParameterBag $params)` |
| `controller_post_dispatch` | after a controller action runs | `(\XF\Mvc\Controller $c, $action, \XF\Mvc\ParameterBag $params, \XF\Mvc\Reply\AbstractReply &$reply)` |
| `criteria_user` | a custom user-criterion is evaluated | `($rule, array $data, \XF\Entity\User $user, &$returnValue)` |
| `criteria_page` | a custom page-criterion is evaluated | `($rule, array $data, &$returnValue)` |
| `navigation_setup` | the nav tree is built | `(\XF\Pub\Navigation\NavigationManager $manager)` |
| `templater_setup` | the templater is created | `(\XF\Template\Templater $templater)` |
| `template_hook` | a `<xf:hook>` renders | see "Injecting into templates" below |

Other events you'll meet: `templater_template_pre_render` /
`templater_template_post_render`, `templater_macro_pre_render` /
`templater_macro_post_render`, `str_formatter_setup`, `bb_code_render_*`,
`visitor_setup`, `dispatcher_match`, `job_run`, `search_*`, and the `load_class*`
family (legacy dynamic class extension — prefer Part 1 unless you must decide the
extending class at runtime).

---

## Part 3 — The `entity_structure` event (most important pattern)

This is how you add a **column-backed field**, a **relation**, or a **getter** to
a *core* entity (e.g. attach data to `XF\Entity\Thread`). Pair it with a schema
alter in `Setup.php` (see `docs/11`).

```php
public static function threadEntityStructure(Manager $em, Structure &$structure): void
{
    // 1. Declare the column you added via Setup.php alterTable()
    $structure->columns['vendor_addon_featured'] = ['type' => Entity::BOOL, 'default' => false];

    // 2. Add a relation to your own table
    $structure->relations['VendorFeature'] = [
        'entity'     => 'Vendor\AddOn:FeaturedThread',
        'type'       => Entity::TO_ONE,
        'conditions' => 'thread_id',
        'primary'    => true,
    ];

    // 3. Expose a computed getter as $thread->vendor_is_hot
    $structure->getters['vendor_is_hot'] = true;
}
```

Register it with `event_id="entity_structure"` and
`hint="XF\Entity\Thread"` so it only fires for the Thread entity. Provide the
getter body via a class extension on the entity:

```php
// in Vendor\AddOn\XF\Entity\Thread extends XFCP_Thread
public function getVendorIsHot(): bool
{
    return $this->view_count > 1000;
}
```

> Three pieces work together: **`Setup.php` alter** (the physical column) +
> **`entity_structure`** (tells the ORM about it) + optional **class extension**
> (getter/behavior). Forget the middle one and `$thread->vendor_addon_featured`
> won't be readable.

---

## Part 4 — Injecting content into templates

Two options, prefer the first:

### Template modifications (recommended, no PHP)

Admin CP → Appearance → Template modifications. A find-and-replace (literal or
regex) against a template, scoped to your add-on, exported to
`_data/template_modifications.xml`. Robust and visible to admins. Use this for
"insert my block into the thread view" type changes.

### `<xf:hook>` + `template_hook` event (programmatic)

Core templates expose named hook points: `<xf:hook name="thread_view_above" />`.
You can inject content at that point with a `template_hook` listener.

```php
// Exact argument order is shown by the ACP code-event page for `template_hook`.
// Commonly: ($templateName/$hookName, &$content, array $params, Templater $templater)
public static function templateHook($hookName, &$content, array $params, \XF\Template\Templater $templater): void
{
    if ($hookName === 'thread_view_above')
    {
        $content .= $templater->renderTemplate('public:vendor_addon_thread_banner', $params);
    }
}
```

> Confirm the precise `template_hook` signature from the ACP stub before relying
> on it. For most "insert a block" needs, **template modifications** are simpler
> and version-proof.

---

## `execute_order` and multiple add-ons

Both class extensions and listeners have an `execute_order` (default 10). Lower
runs first. When several add-ons extend the same class, the XFCP chain stacks in
order — which is exactly why you must call `parent::` so you don't break the
chain. Raise your order only when you must run after another add-on.

---

## Choosing the right tool — quick decision

- Add/override behavior of a controller, entity, repo, service → **class extension**.
- Add a DB-backed field/relation/getter to a *core* entity → **schema alter** +
  **`entity_structure`** (+ class extension for the getter body).
- Run code at a lifecycle moment (request start, after save, nav build) →
  **code event listener**.
- Insert markup into a core template → **template modification** (or `<xf:hook>`).

---

## Gotchas

- **Always `parent::` in overrides.** Skipping it silently disables other add-ons.
- **Hint your entity/content listeners.** An un-hinted `entity_post_save` fires for
  *every* save on the board — a real performance and correctness hazard.
- **XFCP classes are generated.** If your IDE complains about `XFCP_Foo`, export
  dev output (`xf-dev:export`) so `extension_hint.php` regenerates.
- **Re-export after ACP changes.** Adding the extension/listener in the ACP only
  persists to `_data/` when you run `xf-dev:export` (see `docs/14`).
- **`load_class*` events are legacy.** Use Part 1 class extensions unless you must
  pick the extending class dynamically at runtime.

**See also:** `docs/03` (entities), `docs/04` (controllers), `docs/07` (handlers &
content types), `docs/11` (schema), `docs/14` (exporting `_data`).
