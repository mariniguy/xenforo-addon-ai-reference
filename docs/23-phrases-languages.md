# Phrases & Languages (Localization)

XenForo stores **all** user interface text as translatable *phrases* that belong to *languages*, so the entire UI can be localized without editing any HTML or templates. This document focuses on the localization system — the phrase model, language packs, and how add-ons ship and reference phrases — and complements `docs/08-permissions-options-phrases.md` (which covers the admin fields and developer phrase API in more depth).

> **Sources (official XenForo manual):**
> - https://docs.xenforo.com/manual/appearance/phrases
> - https://docs.xenforo.com/manual/appearance/languages
>
> The phrase/language *behavior* below is drawn from those two official pages. The
> **"For add-on developers"** specifics (`_data/phrases.xml`, `{{ phrase() }}` /
> `\XF::phrase()`) are standard XF2 add-on conventions already documented in
> `docs/08-permissions-options-phrases.md` — they are not described on the two
> manual pages above.

---

## The phrase system

All user interface text in XenForo is stored in predefined text snippets called **Phrases**. When XenForo needs to place text on a page, rather than outputting the text directly, it calls for the appropriate phrase that contains the desired text. This indirection is what makes the whole UI translatable.

### Phrase naming

For the most part, phrases are named according to their content. A phrase whose content is `Please click the 'Save' button` would most likely be stored in a phrase called `please_click_the_save_button`.

Sometimes the text in a phrase is too long to be reasonably used as the phrase name, or the phrase must serve a specific programmatic task. In those cases the phrase may have a name that *describes* its content rather than directly reflecting it. For example, the text explaining the *Background size* parameter in the Smilie editor is named `background_size_explain`.

| Situation | Naming approach | Example |
|---|---|---|
| Normal short text | Name reflects the content | `please_click_the_save_button` |
| Text too long, or programmatic role | Name describes the content | `background_size_explain` |
| Text contains an important variable | Variable shown as a letter in the name | `your_thread_x_has_been_updated` |

### Variables in phrases

When a phrase needs to include an important variable — like the name of a piece of content it is describing — the variable is represented in the *phrase name* with a letter such as `x` or `y` (for example, `your_thread_x_has_been_updated`).

Within the *phrase text* itself, the variable is represented as a word in curly braces:

```
Your thread, {name} has been updated.
```

XenForo template syntax takes care of inserting the correct value into the `{name}` variable.

> Because phrases can only contain simple data (not template logic), translating or customizing a phrase is very unlikely to break functionality — **provided every `{variable}` that existed in the original version remains present** in your customized or translated version.

---

## Editing phrases

### Phrase editor

The phrase editor is the primary tool for editing the content of phrases in any language. It is found in **Appearance > Languages & phrases > Phrases** in the Admin Control Panel.

After opening the editor and selecting the appropriate language from the drop-down menu, you see a list of editable phrases. You can filter or search the list by typing into the filter box at the top. This is a special filter box: it not only filters the phrases currently shown on screen, but also searches all other pages for matches to your filter text.

Clicking a phrase opens a page that shows the editable text **and** a copy of the *Master value* for reference. When finished:

- **Save** — saves the text but keeps you in the editor for further edits.
- **Save and exit** — saves your work and returns to the phrase list.

Changes are visible as soon as a page displaying them is refreshed.

### Mass-edit (Refine and translate)

You can edit many phrases at once on a single page:

1. Click **Refine and translate** at the top of the phrase list.
2. Enter any filters you need.
3. Set the *Phrase status* options to **Unmodified** only (leave the other options unchecked).
4. Click **Translate**.

You are then shown editable versions of all matching phrases. You can translate any of them and click its **Save** button without leaving the page, which makes for rapid progress. A **More** button at the bottom loads the next batch of matching phrases.

### Phrase inheritance

Phrases are properties of languages, so they **inherit their content from parent languages** unless they have been customized in the current language. (See *Language inheritance* below for how the parent/child relationship is established.)

---

## Languages

In XenForo, **Languages** function in a similar manner to *Styles*: they represent a collection of data grouped together for ease of management. A single XenForo installation may have multiple languages available, and visiting users can select among them.

### Pre-built languages

You can translate the entire XenForo system text yourself, but the translation you want may already exist. Installing a new language is a simple task that most administrators can manage.

If you complete your own translation, you can use the **Export** tools in the language manager and add your translation to the collection of language translations in the [XenForo Resource Manager](https://xenforo.com/community/resources/categories/translations-2-x.48/) for the benefit of other administrators.

### Importing and exporting language packs

The language manager provides **Import** and **Export** tools. Export produces a shareable language pack from your translated language; import installs a pack into your board. This is the mechanism behind the pre-built translations distributed via the Resource Manager.

### Multiple languages and the default language

When your site has multiple languages installed, you can specify which one is used for visitors who have not chosen their own preference:

1. Go to the **Appearance** section of the main XenForo options system.
2. Select the language you want using the **Default language** option.

By default, **all installed languages are selectable by your visitors**. You can prevent selection of a specific language with a toggle on the **Appearance > Languages** list page.

### Language inheritance

Like styles, languages can be arranged into **parent/child relationships**: a child language inherits all data from its parent and customizes only the items that need to change.

A simple example is UK English, which can inherit most settings from US English except for a few differences such as *color* vs *colour* and *7/28/2010* vs *28/07/2010*. (A full description of how inheritance works appears in the styles section of the official manual.)

### Language settings

Clicking a language in the language manager opens the language editor, where you define basic rules for the language:

| Setting | Purpose |
|---|---|
| Locale | The language/region locale |
| Text direction | Left-to-right or **right-to-left (RTL)** |
| Decimal point character | Character used as the decimal separator |
| Week start day | First day of the week |
| Date format | How dates are formatted |

> These settings have wide-ranging effects across the whole interface, so set them carefully.

---

## For add-on developers

Phrases are the correct (and only) way to put user-facing text on screen in a XenForo add-on. Hard-coded strings cannot be translated; phrases can.

### Shipping phrases with an add-on

Phrases authored in the Admin CP and assigned to your add-on are exported, along with the add-on's other master data, into the add-on's `_data/` directory — as `_data/phrases.xml`. On install or upgrade this master data is imported into the target board; on uninstall XenForo removes it automatically.

> XenForo automatically removes master data exported to `_data/` (options, permissions, **phrases**, routes, listeners, templates, etc.) on uninstall, so you do **not** write uninstall steps for phrases — only for schema and any data you wrote to core tables. See `docs/11-schema-migrations.md`.

### Naming conventions for add-on phrases

Follow the core convention — names reflect content, in lowercase `snake_case` — and prefix phrase names with your add-on so they do not collide with core or other add-ons. Where a phrase carries a variable, represent it in the name with a letter (`x`, `y`) exactly as core does.

```
demo_portal_featured_threads          # reflects its content
demo_item_x_has_been_updated          # variable represented as x in the name
```

(For a fuller table of conventions covering permission titles, option titles, and navigation, see `docs/08-permissions-options-phrases.md`.)

### Referencing phrases in templates

Inside a phrase's text, use `{variable}` placeholders; XenForo substitutes the value when the phrase renders.

```html
<!-- Phrase 'demo_portal_featured' -->
{{ phrase('demo_portal_featured') }}

<!-- Phrase text: "Your thread, {name} has been updated." -->
{{ phrase('demo_item_x_has_been_updated', {'name': $thread.title}) }}

<!-- As a tag attribute -->
<xf:textboxrow label="{{ phrase('demo_portal_title') }}" />
```

### Referencing phrases in PHP

```php
// Phrase object (lazy-evaluated until rendered)
$phrase = \XF::phrase('demo_portal_featured');

// With variables, matching the {name} placeholder in the phrase text
$phrase = \XF::phrase('demo_item_x_has_been_updated', ['name' => $thread->title]);
```

> See `docs/08-permissions-options-phrases.md` for the broader phrase API (`render()`, `phraseDeferred()`, `phrase_dynamic()` in templates, and accessing phrases outside a request via `\XF::app()->language(...)`).

---

## Upgrading with customized or translated phrases

Unlike templates, phrases can only contain simple data, so it is highly unlikely that customizing or translating a phrase will break functionality after a XenForo upgrade — **as long as all `{variable}` variables that existed in the original version of the phrase remain in your customized or translated version**.

For add-on authors this has a direct implication: if a later version of your add-on changes a phrase's variables, treat that as a breaking change to the phrase and update the master text accordingly, because boards that translated the phrase rely on those variable names staying stable.

---

## Quick reference

| Task | Where |
|---|---|
| Edit phrase content | Appearance > Languages & phrases > Phrases |
| Mass-translate unmodified phrases | Phrase list > Refine and translate > status *Unmodified* |
| Install / export a language pack | Language manager (Import / Export) |
| Set the default language | Appearance options > Default language |
| Hide a language from visitors | Appearance > Languages list (toggle) |
| Set locale / RTL / date format | Language manager > language editor |
| Ship phrases in an add-on | `_data/phrases.xml` (exported master data) |

**See also:** `docs/08-permissions-options-phrases.md` (phrase admin fields and developer phrase API), `docs/11-schema-migrations.md` (why phrases need no uninstall step).
