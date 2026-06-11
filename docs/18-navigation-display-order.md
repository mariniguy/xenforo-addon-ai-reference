# Navigation & Display Order

How XenForo's **public navigation** works (the link strip under the header) and the
**display order** concept used to sort it — and almost every other ordered list in
XenForo.

> **Sources (official XenForo documentation, verified against the live site):**
> - Navigation: <https://docs.xenforo.com/manual/appearance/navigation>
> - Display order: <https://docs.xenforo.com/manual/common-concepts/display-order>
>
> Every fact below is drawn from those pages.

---

## Public navigation

The strip of links beneath the logo is the **Public navigation**, managed from the
**Setup** section of the control panel.

### Structure

- **Top-level navigation** — the items shown in the top strip of the header.
- **Sub-navigation** — shown beneath a top-level item when it is selected.
  Visiting different areas of the site automatically switches the selected
  top-level item so the sub-navigation is contextually correct.
- Sub-navigation items may themselves have **children**, displayed in pop-up menus
  when their parent is selected.
- Sub-navigation items with **no parent** are shown in the header when no other
  top-level item is selected.

### Default navigation

The default layout has top-level items **Home**, **Forums**, **What's new** and
**Members**, each containing links appropriate to that area (e.g. Forums → "find
threads with your posts"). **Home** is special: it has no sub-navigation and links
to the *Home page URL* option in the main XenForo options.

> The default top-level items control important functionality. You may rename,
> reorder, and reorganise their sub-items, but you should **not** remove them or
> change their type.

### Editing & adding items

- The simplest edit is toggling items on/off in the navigation list. **Turning off
  a parent also turns off its children.**
- Click an item's name to open the editor and change its name, display order, or
  type.

Editor fields:

| Field | Notes |
|---|---|
| **Navigation ID** | Unique internal name. **Cannot be changed once set** — choose a descriptive value. |
| **Title** | The display name (concise, descriptive). |
| **Parent navigation entry** | Changing it makes the item a sub-item of the chosen parent. |
| **Display order** | A number; higher displays after lower among items with the same parent (see below). |
| **Type** | Changes which extra controls appear (see types). |

### Navigation types

- **Basic** — a simple link, or a parent of other links. Shows a *Link* field;
  enter either a full URL (`http://example.com/path/to/my-page.html`) or an
  internal expression like `{{ link('whats-new/posts') }}`.
- **Node** — shows the node you select (plus a pop-up menu of nodes contained
  within it). Useful for surfacing forums that might not otherwise be discovered.
  **Permissions are applied** — users who can't view the node won't see it here.
- **Callback** — advanced: calls the PHP **class** and **method** specified in the
  *Callback* fields. Intended for add-on developers.

### For add-on developers

- Navigation entries you create in the ACP export to `_data/navigation.xml` and
  ship with the add-on — the cleanest way to add a tab/link (see `docs/15`
  Recipe 9) without extending a controller.
- For a **Basic** entry, prefer the internal `{{ link('your-route') }}` form so
  links stay correct across board URLs (`docs/04`).
- A **Callback** entry's class/method returns the entry dynamically — use it when
  the link/visibility must be computed at runtime.

---

## Display order

**Display order** is used throughout XenForo (navigation, options, nodes, fields,
widgets, permissions, …). Each item has a display-order value — an arbitrary whole
number — and items with **higher numbers display after** items with lower numbers.

```
Blue   (5)
Purple (7)
Red    (29)
Green  (1578)
```

Display order applies **only among items that share the same (or no) parent**. A
child item is ordered only relative to its **siblings** — never against its parent
or against children of a different parent:

```
Colors     (1000)
   Blue    (5)
   Purple  (7)
   Red     (29)
   Green   (1578)
Animals    (2000)
   Badger  (100)
   Hedgehog(101)
   Otter   (102)
Fast food  (10000)
   Hamburger    (30)
   Hot dog      (50)
   Fried chicken(75)
```

Here the top-level items (Colors 1000, Animals 2000, Fast food 10000) are ordered
independently of their children; each child group sorts only within itself.

> **Tip:** leave gaps between values (10, 20, 30 …) so you can insert items later
> without renumbering everything.

**See also:** `docs/15` (Recipe 9 — add a nav tab), `docs/04` (routes & `link()`),
`docs/13` (widgets also use display order within a position).
