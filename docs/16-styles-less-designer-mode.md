# Styles, Less & Designer Mode

How XenForo styling works and how to edit it as a developer: what a style is, style
inheritance, Less/CSS templates, and **Designer mode** (the CLI workflow for
editing style templates on the filesystem under version control).

> **Sources (official XenForo documentation, verified against the live site):**
> - Designing styles (dev): <https://docs.xenforo.com/devs/designing-styles>
> - Styling (manual): <https://docs.xenforo.com/manual/appearance/styles>
> - Templates (manual): <https://docs.xenforo.com/manual/appearance/templates>
>
> Every fact below is drawn from those pages. For style **properties** and
> **template modifications** see `docs/17`.

---

## What a style is

All of XenForo's styling tools are bundled into collections of data called
**Styles** (sometimes called *skins* or *themes*). A style consists of three
things:

1. **HTML templates** — the markup that presents your data.
2. **CSS/LESS templates** — styling rules (`.less` / `.css` extension, no HTML).
3. **Style properties** — named values (colors, sizes, fonts) injected into
   templates (see `docs/17`).

A fresh install ships a single style named *Default style*. You can edit it
freely, or add additional styles and switch between them to preview changes.

---

## Style inheritance

When you add a style you choose whether it has a parent:

- **No parent** → it inherits everything from the invisible **Master style**.
- **A parent** (e.g. *Default style*) → it inherits all data from that parent,
  which in turn inherits from *Master*.

A child shows the parent's value for any item **unless the child has customized
that item itself**. Changing a value in the parent instantly propagates to
children that haven't overridden it.

```
(1) Default style
   └─ (2) My style          ← inherits textColor from Default until overridden
```

- **Inherited vs. customized:** if *My style* shows `blue` for `textColor` only
  because *Default style* sets it, removing the parent removes the `blue`.
- **Overriding:** set `textColor` to `red` *in My style* and it stops inheriting —
  its own value wins.

Inheritance lets you build trees where child styles need only the small changes
that differ from their parents.

---

## Templates

The final output of every page is controlled by **templates**, which contain
HTML or CSS/LESS plus XenForo *template syntax* (so data is manipulated in the
template instead of in PHP).

| Template kind | Extension | Contains |
|---|---|---|
| HTML templates | (none) | markup + `{$var}`, `<xf:tag ...>`, `{{ fn(...) }}` |
| Style/CSS templates | `.less` or `.css` | styling rules, **no HTML** |

- **Less/CSS templates** reference style properties using the
  `@xf-stylePropertyName` system (e.g. `@xf-textColor`). They are containers for
  rules built from style properties (see `docs/17`).
- **Inheritance:** templates are inherited from parent styles unless customized in
  the current style — exactly like style properties.
- **Customizing & reverting:** edits made in the template editor are saved and
  won't be overwritten; a customized template can be **reverted** to its original
  with a couple of clicks.

> **Upgrade caution (from the official docs):** during a XenForo upgrade, no
> customized templates are overwritten. After upgrading, check that your
> customizations are still compatible and that they support any new functionality
> the updated template introduced. For simple edits, prefer **template
> modifications** (`docs/17`) over directly customizing templates.

For the template-inclusion tags and full `<xf:...>` syntax, see `docs/05` and
`cheatsheets/template-tags.md`.

---

## Designer mode (filesystem style editing for developers)

XF2 introduced **Designer mode** — a set of CLI tools that let you modify a
style's templates directly on the filesystem and output metadata about style
properties, which is ideal for version control and collaboration.

### Enable it

In `config.php`:

```php title="src/config.php"
$config['designer']['enabled'] = true;
```

Optionally relocate the output directory (default shown):

```php title="src/config.php"
$config['designer']['basePath'] = 'src/styles';
```

### Enable Designer mode for a specific style

Designer mode is enabled **per style**. Pick the style's id and choose a
"designer mode id" (the handle you'll use in later commands):

```sh title="Terminal"
php cmd.php xf-designer:enable [style_id] [designer_mode_id]
```

The style's currently-modified components are exported to
`[basePath]/[designer_mode_id]`. If that directory already exists you're asked
whether to overwrite the directory from the style, or the style from the
directory.

### What is output, and where

A style only consists of **what is modified in that style** — so Designer mode
output contains only the components modified in *this* style (not those modified
in a parent).

| Output | Location | Format | Editable on disk? |
|---|---|---|---|
| Templates | `[base]/[id]/templates/<type>/` (admin, email, public) | HTML | **Yes** — imported & compiled when the template loads |
| Style properties | `[base]/[id]/style_properties/` | JSON | No — for VCS monitoring only |
| Style property groups | `[base]/[id]/style_property_groups/` | JSON | No — for VCS monitoring only |

> Editing a template file on disk is imported automatically. Editing the JSON
> style-property files directly is **not** auto-imported — don't rely on it.

### Modifying / creating templates from the CLI

Templates only appear on disk once they're *modified in this style*. Mark a
template as modified (copies it from a parent/master style into this one):

```sh title="Terminal"
php cmd.php xf-designer:touch-template [designer_mode_id] [template_type:template_title]
```

Create an entirely custom template that exists in no other style:

```sh title="Terminal"
php cmd.php xf-designer:touch-template [designer_mode_id] [template_type:template_title] --custom
```

You can also just edit a template in the Admin CP — with Designer mode on it's
written to disk automatically.

### Other Designer-mode commands

```sh title="Terminal"
# Overwrite the filesystem copy from the database (auto-run on enable):
php cmd.php xf-designer:export [designer_mode_id]
# (type-specific variant, e.g.) xf-designer:export-templates

# Overwrite the database copy from the filesystem:
php cmd.php xf-designer:import [designer_mode_id]
# (type-specific variant, e.g.) xf-designer:import-templates

# Import only templates whose metadata changed, recompiling + bumping versions:
php cmd.php xf-designer:sync-templates [designer_mode_id]

# Revert a template (delete the custom version from this style):
php cmd.php xf-designer:revert-template [designer_mode_id] [template_type:template_title]
#   (also triggered by deleting the template file from disk)

# Disable Designer mode for a style (keeps the on-disk copy):
php cmd.php xf-designer:disable [designer_mode_id]
#   add --clear to also delete the on-disk data:
php cmd.php xf-designer:disable [designer_mode_id] --clear
```

---

## How this fits add-on development

- **Pre-built styles** are distributed via the official Resource Manager
  (<https://xenforo.com/community/resources/categories/styles-2-x.45/>). Applying
  one takes only a few minutes.
- Add-ons that ship styling do so as **templates** (including `.less` CSS
  templates) and **style properties**, which obey the same inheritance rules.
- Reference style properties from Less with `@xf-propertyId` so your CSS adapts to
  whatever style is active.
- Designer mode is the recommended way to keep style/template work in git while
  developing.

**See also:** `docs/17` (style properties & template modifications), `docs/05`
(template syntax & tags), `docs/14` (dev tools & build).
