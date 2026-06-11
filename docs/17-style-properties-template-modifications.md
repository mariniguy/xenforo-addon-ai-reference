# Style Properties & Template Modifications

Two systems that let you adjust appearance without rewriting templates:
**style properties** (named, inheritable styling values referenced as `@xf-…`) and
**template modifications** (find/replace patches applied to templates, the safest
way to inject markup into core or another add-on's templates).

> **Sources (official XenForo documentation, verified against the live site):**
> - Style properties: <https://docs.xenforo.com/manual/appearance/style-properties>
> - Template modifications: <https://docs.xenforo.com/manual/appearance/template-modifications>
>
> Every fact below is drawn from those pages.

---

## Part 1 — Style properties

Style properties let administrators adjust colors, sizes and fonts quickly. Each
property's value is **inherited from the parent style(s)** unless customized in
the current style (same inheritance model as templates — see `docs/16`). On the
*Style properties* manager, properties are arranged into logical **groups**;
clicking a group opens its editor.

### Property types

**Single-value** properties hold one value:

- Colors
- Text values
- Numbers with units (like `10px`)
- Numbers without units
- On/off switches
- Multiple-choice options

**CSS-type** properties hold a *collection* of values that together define the
styling of one interface element. They can include:

- Text color, size, font and style
- Background style
- Border size, style, color and radius
- Padding and margin
- Extra CSS rules

### The Color palette

The *Color palette* group defines the complete palette XenForo uses to build its
interface. Changing a palette color replaces every instance of that color
throughout the system.

It also includes a **Light / Dark** style-type switch. This controls how colors
are transformed when the system needs to mix, intensify or diminish them:

- **Light** style → "intensify" **darkens** the color.
- **Dark** style → "intensify" **lightens** the color.

### Basic colors & referencing properties (`@xf-`)

The *Basic colors* group assigns palette colors to roles such as *Text color* and
*Content background color*. You reference a style property using the prefix
`@xf-` followed by its **unique id**:

```less
// Reference the palette property "Neutral 3" (id: paletteNeutral3)
color: @xf-paletteNeutral3;

// The Text color property (textColor) in the Default style points at it:
//   textColor  =  @xf-paletteNeutral3
.myAddOnBlock {
    color: @xf-textColor;
    background: @xf-contentBg;
}
```

> Use `@xf-…` references in your add-on's `.less` templates rather than hard-coded
> colors, so your styling automatically tracks whatever style/palette is active.

### Advanced grouped properties

More complex areas expose nested groups. For example the *Header and navigation*
group controls the top of every page (header + public navigation, `docs/18`);
within it, the *Header/logo row* group styles the main header section (e.g. the
Default style sets header text to palette *Color 2* = `@xf-paletteColor2` and the
background to *Color 5* = `@xf-paletteColor5`). Single-value properties (like a
*Header adjustment color*) sit alongside these grouped CSS properties. Building up
customized properties this way can dramatically change a site's appearance.

### For add-on developers

- Ship your own style properties (and groups) so admins can tune your add-on's
  look from the ACP; they export to `_data/style_properties.xml` and
  `_data/style_property_groups.xml`.
- Read them in `.less` templates with `@xf-yourPropertyId`.
- Property values are inherited; an admin overriding yours in a child style is
  expected behavior.

---

## Part 2 — Template modifications

A **template modification** picks a template, specifies text to *find*, and
inserts/replaces it — an alternative to customizing the template directly. In the
template-modification manager, all active modifications are listed **grouped by
the add-on** that defined them, each with a toggle to disable/enable it.

### Editor fields

| Field | Purpose |
|---|---|
| **Modification key** | Unique id for the modification. Letters, numbers and underscores only. |
| **Description** | Short summary, e.g. *"Adds a new tab to the member profile page"*. |
| **Template contents** | Reference only — the full text of the target template. |
| **Search type** | *Simple replacement*, *Regular expression*, or *PHP callback* (advanced). |
| **Find** | The text to match (must suit the search type). |
| **Replace** | The HTML/template code to substitute in. |
| **Execution order** | When several mods target one template, **lower runs first**. |

### Find — using XF placeholders

Many XenForo templates include special placeholders at useful locations:

```html
<!--[XF:name_goes_here]-->
```

These let you enter the token as the *Find* text and reliably anchor your
modification to a stable insertion point (more upgrade-resistant than matching
arbitrary markup).

### Replace — keeping matched text & captures

The *Replace* value is inserted into the template, so you can use full XenForo
**template syntax** in it. Two tokens help you keep matched content:

- `$0` — re-insert the entire text that *Find* matched (so you can wrap/append to
  it instead of deleting it).
- `$1`, `$2`, … — for a **regular expression** search type, re-insert capture
  groups from the match.

```text
Search type: Simple replacement
Find:        <!--[XF:thread_view_above]-->
Replace:     $0
             <div class="block">{{ phrase('my_addon_banner') }}</div>
```

### Execution order & upgrade safety

When multiple modifications attach to the same template, *Execution order*
determines the sequence (lower numbers first). Per the official docs: although
template modifications are the **safest** way to customize templates, after a
XenForo upgrade you should still double-check that your modifications haven't
blocked or broken new/altered functionality in the templates they attach to.

### For add-on developers

- Template modifications you create in the ACP export to
  `_data/template_modifications.xml` and ship with your add-on.
- Prefer a template modification over a **class extension** when all you need is to
  inject or tweak markup in a core/other template — it's less brittle and visible
  to admins (see `docs/12` for when to choose each).
- Anchor on `<!--[XF:…]-->` placeholders where available; otherwise keep *Find*
  strings minimal and specific.

**See also:** `docs/16` (styles, Less, templates), `docs/12` (template_hook &
class extensions), `docs/05` (template syntax), `docs/18` (navigation).
