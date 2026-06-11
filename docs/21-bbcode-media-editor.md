# BB Codes, Media Sites & the Editor

BB code is XenForo's markup system for formatting message text — bold, colors,
quotes, spoilers, code blocks and more. Beyond the built-in tags, admins and
add-on developers can define **custom BB codes**, register **BB code media sites**
that auto-embed external content, control **hot-linked images**, and rearrange the
**editor toolbar** through the BB code button manager.

> **Sources (official XenForo manual):**
> - https://docs.xenforo.com/manual/content/bbcode
> - https://docs.xenforo.com/manual/content/bbcode-media-sites
> - https://docs.xenforo.com/manual/content/bbcode-images
> - https://docs.xenforo.com/manual/appearance/bbcode-button-manager

---

## What BB code is

BB code is a widely-used markup system, loosely based on
[HTML](https://html.spec.whatwg.org/), that lets text be formatted with different
fonts, colors and sizes, plus abilities like emboldening or italicising. The BB
code to define bold text is `[b]`, used like this:

```bbcode
This message contains some [b]bold[/b] text.
```

It can also express more complex structures — quoting other users' messages,
adding spoilers, or blocks of code. In those cases a BB code may take an
**option**:

```bbcode
This message contains some text in [font="Helvetica"]a font called Helvetica[/font]...
```

So a BB code has up to three parts: the **tag** (`font`), an optional **option**
value (`"Helvetica"`), and the **text** between the opening and closing tags.

---

## Custom BB codes

In addition to the standard BB codes that ship with XenForo, you may define your
own. The **custom BB code manager** lists all custom BB codes available on your
forum and lets you create new ones. You can also delete or temporarily disable any
custom BB code using the toggle and delete gadgets there, and **import/export**
multiple custom BB codes using the controls at the top of the manager page.

Clicking the title of a custom BB code opens its **editor**, where you lay out
exactly how the BB code should function.

### Worked example: a colored box

The manual builds a BB code that draws a box around the tagged text and lets the
user pick a color. It is named `box`, and aims to convert this input:

```bbcode
[BOX="red"]Here is some text in a red box[/BOX]
```

into this HTML:

```html
<div style="background-color: red">Here is some text in a red box</div>
```

The editor fields used to achieve that:

| Field | Purpose | Value in the example |
|---|---|---|
| **BB code tag** | The keyword that triggers your BB code. | `BOX` |
| **Title** | Human-readable name shown in the manager. | `Colored box` |
| **Replacement mode** | *Simple replacement* (HTML template) or a *PHP callback*. | Simple replacement |
| **Supports option parameter** | Whether an option value is required. | *Yes* |
| **HTML replacement** | The HTML that replaces the BB code, using the `{option}` and `{text}` tokens. | `<div style="background-color: {option}">{text}</div>` |
| **Editor icon** | Font Awesome icon or image for a toolbar button (or *None*). | — |

### Replacement tokens

Within the **HTML replacement**, two special tokens stand in for the user-supplied
parts:

| Token | Represents |
|---|---|
| `{option}` | The value of the option (e.g. `red`). |
| `{text}` | The content between the opening and closing tags. |

### Replacement mode: simple vs PHP callback

- **Simple replacement** uses the HTML template with `{option}` / `{text}` tokens,
  as above.
- A **PHP callback** may be used for more complex replacements that involve running
  PHP code to work out what the output HTML should be. The manual notes this is
  *more of a developer option*. When used, the callback is specified through the
  **Class** and **Method** fields provided in the editor.

### Supports option parameter

This controls whether the option value is allowed or required:

- **Yes** — the BB code needs an option (the colored-box example uses this).
- **Optional** — an option may be supplied but is not required.
- **No** — the BB code takes no option.

### Editor icon and the button

If you want users to insert your BB code by clicking a button in the message
editor, specify a [Font Awesome](https://fontawesome.com/icons?d=gallery) icon or
an image to use for the button. Otherwise leave this as **None** and the BB code
will only be available by being typed directly.

You can customise where that custom button (and all other controls) appear within
the editor using the [BB code button manager](#bb-code-button-manager) — see
below.

### Example usage and output

The editor provides **Example usage** and **Example output** boxes so you can show
something that would use your custom BB code and what it would produce. The
Input/Output from the worked example above can be used here.

### Allow this BB code in signatures

Some BB codes are large and obnoxious and are not suitable for signatures. Leaving
the **Allow this BB code in signatures** box unchecked prevents the custom BB code
from being used in user signatures.

### Advanced options

The advanced options section refines a custom BB code further:

| Advanced option | What it does |
|---|---|
| **Option match regular expression** | A regular expression limiting acceptable values for the *option*. The example suggests allowing only alphanumeric characters so that only named colors can be used. |
| **Within this BB code** | Options to prevent smilie parsing, stop line breaks being converted, disable auto-parsing of hyperlinks, and stop parsing of any other BB codes within this BB code's text component. |
| **Trim line breaks after** | Prevents excess white space after the BB code. With a value of `0`, no additional line breaks are permitted — so your HTML output should account for that. |
| **HTML email and text replacements** | Alternative outputs when the final format is HTML email or plain text, defined with `{option}` and `{text}` as before. |

> **For add-on developers:** the *Within this BB code* toggles matter when you
> intend BB code (or other markup) to nest inside your tag. If you disable parsing
> of other BB codes within the text component, `{text}` is emitted as-is. Pair a
> custom BB code's HTML output with template/style work — see
> `docs/05-templates.md`.

---

## BB code media sites

**BB code media sites** convert links to content hosted on external sites — such
as YouTube or Instagram — into embedded media in users' messages automatically.
XenForo ships with a collection of predefined media sites for popular sources
including Facebook, Twitter, Flickr and Spotify.

Links posted in user messages are automatically processed and turned into embedded
media if the **Auto-embed media links** option is enabled, under the **Media
embedding** section of XenForo's options system.

### The media site manager

In the BB code media site manager you can view all available media sites,
temporarily disable each one with a single click on the toggle gadget, delete
sites, or add a new one. Clicking a site's title loads its editor.

### How a media site works

A media site works by **extracting data from the URL** the user posts and
translating that into a snippet of HTML that embeds the referenced media. Sometimes
this is simply taking a piece of the URL and inserting it into the HTML; in other
cases further steps are required to turn the URL into usable HTML.

The manual uses **Pinterest** as a relatively simple example, because Pinterest
URLs contain all the information needed to build the embed HTML.

### Match URLs

The **Match URLs** box defines all the URLs you want converted into embedded HTML.
Each URL goes on its own line and includes an `{id}` token representing the data
you care about.

You can constrain what `{id}` matches by extending it:

| Token | Matches |
|---|---|
| `{id}` | The data of interest (unconstrained). |
| `{id:digits}` | Whole numbers only. Pinterest uses this because its data is always a number. |
| `{id:alphanum}` | Numbers and letters only. |

You may also use `*` as a **wildcard** in the Match URLs to match anything.

Under **Advanced options** there is a setting that allows the Match URLs to be
**regular expressions**. If you use regular expressions, each line must define
delimiters and switches.

### Embed template

The **Embed template** box defines the HTML output when a matching URL is found.
You may use any HTML, but it's a good idea to wrap your output in the markup used
by most default XenForo sites so it picks up site styling:

```html
<div class="bbMediaWrapper"><div class="bbMediaWrapper-inner">
  <!-- your embed markup -->
</div></div>
```

Within the HTML you refer to the matched data with these tokens:

| Token | Refers to |
|---|---|
| `{$id}` | The `{id}` value fetched by the Match URL. |
| `{$idDigits}` | The value when you used the `{id:digits}` extension. |
| `{$idAlphanum}` | The value when you used the `{id:alphanum}` extension. |

For Pinterest, the important part is the `href` attribute, whose source is defined
as `https://www.pinterest.com/pin/{$idDigits}`, using the data grabbed by the Match
URLs.

### oEmbed

[oEmbed](https://oembed.com) is an open format that lets sites return information
about a URL, including embed HTML. When the embed HTML cannot be constructed
directly from the URL, you may be able to query the site for oEmbed data and get
the HTML that way.

To use oEmbed you must know:

- the site's **oEmbed API endpoint**, and
- the **format of the URLs** their API expects.

For example, Flickr's oEmbed API endpoint is
`https://www.flickr.com/services/oembed` and its URL scheme is
`https://flic.kr/p/{$id}`, where `{$id}` again represents the data matched by the
Match URLs.

Finally, you must decide whether to **execute any JavaScript** returned from the
oEmbed site along with the embed HTML. If you choose not to allow the foreign
JavaScript to run, you must handle any required initialization for the embedded
HTML with your own JavaScript routines.

> oEmbed.com publishes a regularly-updated list of sites that make use of oEmbed.

### Advanced options (matching / embedding callbacks)

Sometimes even further processing is required to get workable embed HTML. In those
cases a **PHP callback** is available for both **matching** and **embedding**
purposes.

> **For add-on developers:** the manual states it is beyond the scope of that page
> to detail exactly how the callbacks work, but notes that developers can inspect
> the code for sites in the default XenForo installation that use matching and
> embedding callbacks. Those built-in media sites are the reference implementation
> to copy from. See `docs/12-events-listeners-extensions.md` for how add-ons hook
> into and extend core behavior.

---

## BB code images (hot-linking)

While users should be encouraged to use **attachments** when embedding images,
support is in place for images to be *hot-linked* from external sources.

Users may embed a hot-linked image in two ways:

- clicking the **image icon** in the message editor toolbar and entering the URL in
  the provided text box, or
- typing the `[IMG]` BB code manually with the image URL between the tags:

```bbcode
[IMG]https://example.com/image...[/IMG]
```

### Proxying remote images

Linked images can either be loaded directly by users viewing the content, or you
may have your server **proxy** the images using XenForo's image and link proxy.

### Limiting image use

To stop users posting ridiculous numbers of hot-linked images in a message, you can
impose a limit at **Setup > Options > Messages > Maximum images per message**. A
value of `0` disables the limit.

---

## BB code button manager

When composing a message, the XenForo editor provides controls so users can format
text. The **layout** of those buttons is customisable through the BB code button
manager, in the Admin control panel at **Content > BB code > BB code button
manager**.

The main page lists several **editor toolbars** along with a variety of
**dropdowns**.

### Editor dropdowns

A **dropdown** is a button that expands when clicked to reveal additional buttons.
Click an existing dropdown, or click **Add dropdown**, to open the dropdown editor.

In the dropdown editor you must provide:

- a **command ID** (for internal system identification only),
- a **title**, and
- a [Font Awesome](https://fontawesome.com/icons?d=gallery) icon class name — such
  as [`fa-align-center`](https://fontawesome.com/icons/align-center?style=solid) —
  used as the graphical representation for the button.

To define the dropdown's contents, drag buttons from the **Available buttons** area
into the **Dropdown buttons** area and arrange them as you wish. The first icon
shown is displayed first in the dropdown. Hit **Save** to commit changes.

### Editor toolbars

Each toolbar is listed with a **size range** — the width, in pixels, of the browser
window at which that toolbar is shown. The actual ranges are subject to change, but
at the time of writing the manual documents:

| Toolbar | Shown when viewport width is |
|---|---|
| **Large** | 900 pixels or more |
| **Medium** | Below 900 pixels |
| **Small** | Below 575 pixels |
| **Extra small** | Below 420 pixels |

Clicking any toolbar opens its editor. At the top is a **pool** of *Available
buttons and dropdown menus*. Drag any of these into one of the available slots
below; buttons within a slot can be dragged into whatever order is appropriate.

Each slot has controls to define:

- the **alignment** of the group, and
- the number of **buttons visible** — how many buttons are shown for that toolbar
  group before additional buttons are pushed to the **more** toolbar, accessed via
  the vertical ellipsis control next to each button group.

A **preview** of the finished toolbar is displayed at the bottom of the page. Hit
**Save** to commit changes.

> **For add-on developers:** when a custom BB code (above) specifies an editor
> icon, its button appears in the *Available buttons* pool here, and you place it
> into the toolbars/dropdowns just like any built-in control. Defining the BB code
> and positioning its button are two separate steps.

---

## Summary for add-on developers

- **Custom BB codes** are defined in the Admin CP (tag, title, replacement mode,
  option support, HTML replacement with `{option}`/`{text}`, advanced parsing
  controls). Use a **PHP callback** (Class/Method) when simple token replacement
  isn't enough.
- **Media sites** turn external URLs into embeds via **Match URLs** (`{id}`,
  `{id:digits}`, `{id:alphanum}`, `*` wildcard, optional regex) and an **Embed
  template** (`{$id}`, `{$idDigits}`, `{$idAlphanum}`); **oEmbed** and **PHP
  matching/embedding callbacks** cover harder cases. Copy the default XenForo media
  sites as reference implementations.
- **Hot-linked images** use `[IMG]`, can be proxied, and are limited by *Maximum
  images per message*.
- **Editor layout** (toolbars + dropdowns, responsive size ranges) is arranged in
  the **BB code button manager**; custom BB code buttons appear in the Available
  buttons pool once an editor icon is set.

**See also:** `docs/05-templates.md` (templates and styling for your BB code
output), `docs/12-events-listeners-extensions.md` (extending core behavior from an
add-on).
