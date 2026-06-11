# Widgets & Widget Positions

Widgets are reusable content blocks (recent threads, online users, a custom panel)
that admins place into **widget positions** around a page. As a developer you
provide two things: **widget positions** (where widgets can go) and, optionally,
**widget definitions** (a renderer class that produces dynamic content).

A widget has four parts (from the admin's view):

1. A **widget definition** (the PHP renderer + its options) — *developer supplies*.
2. A unique **widget key** — *admin chooses when deploying*.
3. A **widget position** it attaches to — *developer supplies the position*.
4. **Parameters/options** for the data shown — *admin fills in*.

---

## 1. Widget positions

A position is a named slot. You **declare it in a template** with `<xf:widgetpos>`
and **register it** (ACP → Development → Widget positions), which exports to
`_data/widget_positions.xml`.

```xml title="_data/widget_positions.xml"
<?xml version="1.0" encoding="UTF-8"?>
<widget_positions>
    <position id="vendor_addon_sidebar"
              addon_id="Vendor/AddOn">
        <title>Vendor AddOn: Sidebar</title>
        <description>Widgets shown in the sidebar of the add-on page.</description>
    </position>

    <position id="vendor_addon_below_content"
              addon_id="Vendor/AddOn">
        <title>Vendor AddOn: Below Content</title>
        <description>Widgets shown below the main content block.</description>
    </position>
</widget_positions>
```

Render the position in your template wherever widgets should appear:

```html
<div class="block-container">
    <xf:widgetpos id="vendor_addon_sidebar" />
</div>
```

- `id` must match the registered position id.
- A position renders **every** widget an admin has attached to it, ordered by
  display order.
- You can pass context to widgets at the position: `<xf:widgetpos id="..."
  position="..." />` and supply params the renderers can read (see context below).

> To drop widgets into a **core** page (e.g. the forum list sidebar), you usually
> don't need a new position — core templates already expose positions like
> `forum_list_sidebar`. Use a **template modification** (`docs/12`) only if no
> suitable position exists.

---

## 2. Widget definitions (the renderer)

A widget definition ties a **renderer class** to a definition id plus default
options. Create the definition in **ACP → Development → Widget definitions**
(exports to `_data/widget_definitions.xml`). The renderer class does the work.

### The renderer class

```php title="src/addons/Vendor/AddOn/Widget/RecentItems.php"
<?php

namespace Vendor\AddOn\Widget;

use XF\Widget\AbstractWidget;

class RecentItems extends AbstractWidget
{
    // Default options merged with whatever the admin configures
    protected $defaultOptions = [
        'limit' => 5,
        'style' => 'simple',
    ];

    public function render()
    {
        $limit = (int) $this->options['limit'];

        /** @var \Vendor\AddOn\Repository\Item $repo */
        $repo = $this->app->repository('Vendor\AddOn:Item');
        $items = $repo->findItemsForList()
            ->limit($limit)
            ->fetch();

        // Don't render an empty block
        if (!$items->count())
        {
            return '';
        }

        $viewParams = [
            'title' => $this->getTitle() ?: \XF::phrase('vendor_addon_recent_items'),
            'items' => $items,
            'style' => $this->options['style'],
        ];
        return $this->renderer('vendor_addon_widget_recent_items', $viewParams);
    }

    // Admin-side options form shown in the widget editor
    public function getOptionsTemplate()
    {
        return 'admin:vendor_addon_widget_recent_items_options';
    }

    // Validate/normalize admin input before it is saved
    public function verifyOptions(\XF\Http\Request $request, array &$options, &$error = null)
    {
        $options = $request->filter([
            'limit' => 'uint',
            'style' => 'str',
        ]);

        if ($options['limit'] < 1)
        {
            $options['limit'] = 5;
        }
        return true;
    }
}
```

Key `AbstractWidget` members:

| Member | Purpose |
|---|---|
| `$this->options` | merged admin options (+ `$defaultOptions`) |
| `$this->app` | the app container (`->repository()`, `->finder()`, …) |
| `$this->contextParams` | params passed in from the position/page |
| `$this->renderer($template, $params)` | render the widget body template (returns string/array) |
| `getTitle()` | the admin-set widget title |
| `render()` | **required** — return rendered HTML, or `''` to show nothing |
| `getOptionsTemplate()` | admin template for the options form (optional) |
| `verifyOptions(...)` | validate/normalize options on save (optional) |

> Return `''` from `render()` when there's nothing to show — XenForo will omit the
> block entirely rather than render an empty container.

### The widget body template

`templates/admin/` and `templates/public/` aside, the **widget body** is a normal
public template. By default the renderer wraps it in a standard block; use
`<xf:widget>`'s wrapping or set the widget to "advanced mode" to control markup.

```html title="vendor_addon_widget_recent_items"
<xf:widget key="$widget.widget_key" definition="vendor_addon_recent_items"
           title="{$title}">
    <div class="block-body">
        <xf:foreach loop="$items" value="$item">
            <a href="{{ link('vendor-addon/item', $item) }}" class="block-row">
                {$item.title}
            </a>
        </xf:foreach>
    </div>
</xf:widget>
```

In practice, `render()` returns the inner content and XenForo supplies the block
wrapper; keep the body focused on the rows.

### The definition XML

```xml title="_data/widget_definitions.xml"
<?xml version="1.0" encoding="UTF-8"?>
<widget_definitions>
    <definition definition_id="vendor_addon_recent_items"
                definition_class="Vendor\AddOn\Widget\RecentItems"
                addon_id="Vendor/AddOn">
        <options><![CDATA[{"limit":5,"style":"simple"}]]></options>
    </definition>
</widget_definitions>
```

> Create the definition through the ACP rather than hand-writing this XML — the
> ACP validates the class and exports the correct format on `xf-dev:export`.

---

## 3. Rendering a specific widget anywhere

Besides positions, you can render a configured widget by key, or an ad-hoc widget
by definition, directly in any template:

```html
<!-- Render a deployed widget by its key -->
<xf:widget key="vendor_addon_recent_block" />

<!-- Render an ad-hoc widget from a definition with inline options -->
<xf:widget definition="vendor_addon_recent_items"
           positionCode="vendor_addon_sidebar"
           limit="10" style="detailed" />
```

---

## 4. Context params

Pages can pass data to the widgets in their positions (e.g. the current node or
user). The position forwards `contextParams`, which your renderer reads:

```html
<!-- In the page template -->
<xf:widgetpos id="vendor_addon_sidebar" position="vendor_addon_sidebar"
              widget-context="{$category}" />
```

```php
// In the renderer
public function render()
{
    $category = $this->contextParams['category'] ?? null;
    // scope your query to $category ...
}
```

---

## Workflow

```bash
# 1. Write the renderer class under Widget/.
# 2. ACP → Development → Widget positions → add your position(s).
# 3. ACP → Development → Widget definitions → add your definition (points to the class).
# 4. Add <xf:widgetpos id="..."/> to the template(s) where widgets should appear.
# 5. Export so _data/ updates:
php cmd.php xf-dev:export --addon Vendor/AddOn
```

---

## Gotchas

- **Position id must match** between the registered position, the
  `<xf:widgetpos>` tag, and any `positionCode` you reference.
- **`render()` must return a string** (or array). Returning `''` hides the block.
- **Don't run heavy queries unconditionally** — widgets render on many page loads;
  cache where sensible and respect the admin's `limit` option.
- **Two built-in definitions exist** for admins: the **HTML widget** (raw template
  snippet) and the **PHP callback widget** (class + method) — you don't need a
  full definition for one-off custom HTML.
- **Re-export after ACP changes**, or your positions/definitions won't ship in
  `_data/` (see `docs/14`).

**See also:** `docs/05` (templates & `<xf:...>` tags), `docs/12` (template
modifications for injecting into core pages), `docs/03` (repositories/finders the
renderer queries).
